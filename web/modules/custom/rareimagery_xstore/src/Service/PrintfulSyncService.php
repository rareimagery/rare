<?php

namespace Drupal\rareimagery_xstore\Service;

use Drupal\commerce_price\Price;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service for syncing products and orders with Printful API.
 *
 * Handles:
 * - Product catalog sync from Printful to Commerce products
 * - Order fulfillment push to Printful
 * - Shipping rate calculation
 * - Webhook processing for order status updates
 */
class PrintfulSyncService {

  /**
   * Printful API base URL.
   */
  protected const API_BASE = 'https://api.printful.com';

  /**
   * The HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * The logger.
   */
  protected $logger;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a PrintfulSyncService.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    protected ConfigFactoryInterface $configFactory,
    protected StoreManagerService $storeManager,
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('rareimagery_xstore');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Builds authorization headers for Printful API.
   */
  protected function getRequestOptions(?string $store_api_key = NULL): array {
    $api_key = $store_api_key ?: $this->configFactory->get('rareimagery_xstore.settings')->get('printful.api_key');
    return [
      'headers' => [
        'Authorization' => 'Bearer ' . $api_key,
        'Content-Type' => 'application/json',
      ],
      'timeout' => 30,
    ];
  }

  /**
   * Syncs products from a Printful store to Commerce.
   *
   * @param string $printful_store_id
   *   The Printful store ID.
   * @param int $store_nid
   *   The x_creator_store node ID to associate products with.
   *
   * @return int
   *   Number of products synced.
   */
  public function syncProducts(string $printful_store_id, int $store_nid): int {
    $this->logger->info('Printful sync initiated for store @id', ['@id' => $printful_store_id]);
    $synced = 0;

    try {
      $options = $this->getRequestOptions();
      $options['headers']['X-PF-Store-Id'] = $printful_store_id;

      $response = $this->httpClient->request('GET', self::API_BASE . '/store/products', $options);
      $result = json_decode($response->getBody()->getContents(), TRUE);

      if (empty($result['result'])) {
        return 0;
      }

      $store_node = $this->entityTypeManager->getStorage('node')->load($store_nid);
      if (!$store_node) {
        $this->logger->error('Store node @nid not found', ['@nid' => $store_nid]);
        return 0;
      }

      $sku_prefix = $this->storeManager->generateSkuPrefix($store_node->get('field_x_handle')->value);
      $commerce_store = $this->storeManager->getCommerceStore($store_node);

      $product_storage = $this->entityTypeManager->getStorage('commerce_product');
      $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');

      foreach ($result['result'] as $pf_product) {
        $pf_product_id = (string) $pf_product['id'];

        // Fetch full product details including variants.
        $detail_response = $this->httpClient->request('GET', self::API_BASE . '/store/products/' . $pf_product_id, $options);
        $detail = json_decode($detail_response->getBody()->getContents(), TRUE);

        if (empty($detail['result'])) {
          continue;
        }

        $sync_product = $detail['result']['sync_product'];
        $sync_variants = $detail['result']['sync_variants'] ?? [];

        // Find or create the Commerce product.
        $existing = $product_storage->loadByProperties([
          'type' => 'physical_pod',
          'field_printful_product_id' => $pf_product_id,
          'field_store' => $store_nid,
        ]);

        if ($existing) {
          $product = reset($existing);
        }
        else {
          $product = $product_storage->create([
            'type' => 'physical_pod',
            'title' => $sync_product['name'],
            'field_store' => $store_nid,
            'field_printful_product_id' => $pf_product_id,
            'stores' => $commerce_store ? [$commerce_store->id()] : [],
            'status' => TRUE,
          ]);
        }

        $product->setTitle($sync_product['name']);

        // Process variants.
        $variations = [];
        foreach ($sync_variants as $pf_variant) {
          $variant_id = (string) $pf_variant['id'];
          $sku = $sku_prefix . '-POD-' . $pf_product_id . '-' . $variant_id;

          $existing_var = $variation_storage->loadByProperties([
            'type' => 'pod_variation',
            'field_printful_variant_id' => $variant_id,
          ]);

          if ($existing_var) {
            $variation = reset($existing_var);
          }
          else {
            $variation = $variation_storage->create([
              'type' => 'pod_variation',
              'sku' => $sku,
              'field_printful_variant_id' => $variant_id,
              'status' => TRUE,
            ]);
          }

          $retail_price = $pf_variant['retail_price'] ?? '0.00';
          $currency = $pf_variant['currency'] ?? 'USD';
          $variation->setPrice(new Price($retail_price, $currency));
          $variation->setSku($sku);
          $variation->save();
          $variations[] = $variation;
        }

        $product->setVariations($variations);
        $product->save();
        $synced++;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Printful sync failed: @message', ['@message' => $e->getMessage()]);
    }

    $this->logger->info('Printful sync completed: @count products synced', ['@count' => $synced]);
    return $synced;
  }

  /**
   * Pushes an order to Printful for fulfillment.
   *
   * @param int $order_id
   *   The Commerce order ID.
   *
   * @return bool
   *   TRUE if the order was successfully submitted to Printful.
   */
  public function submitOrder(int $order_id): bool {
    $this->logger->info('Printful order submission for order @id', ['@id' => $order_id]);

    try {
      $order = $this->entityTypeManager->getStorage('commerce_order')->load($order_id);
      if (!$order) {
        $this->logger->error('Order @id not found', ['@id' => $order_id]);
        return FALSE;
      }

      // Build recipient from order profiles.
      $profiles = $order->collectProfiles();
      $shipping_profile = $profiles['shipping'] ?? $profiles['billing'] ?? NULL;
      if (!$shipping_profile) {
        $this->logger->error('No shipping address for order @id', ['@id' => $order_id]);
        return FALSE;
      }

      $address = $shipping_profile->get('address')->first();
      $recipient = [
        'name' => $address->getGivenName() . ' ' . $address->getFamilyName(),
        'address1' => $address->getAddressLine1(),
        'address2' => $address->getAddressLine2(),
        'city' => $address->getLocality(),
        'state_code' => $address->getAdministrativeArea(),
        'country_code' => $address->getCountryCode(),
        'zip' => $address->getPostalCode(),
        'email' => $order->getEmail(),
      ];

      // Build line items.
      $items = [];
      foreach ($order->getItems() as $order_item) {
        $variation = $order_item->getPurchasedEntity();
        if (!$variation || $variation->bundle() !== 'pod_variation') {
          continue;
        }

        $printful_variant_id = $variation->get('field_printful_variant_id')->value;
        if (!$printful_variant_id) {
          continue;
        }

        $items[] = [
          'sync_variant_id' => (int) $printful_variant_id,
          'quantity' => (int) $order_item->getQuantity(),
          'retail_price' => $order_item->getUnitPrice()->getNumber(),
        ];
      }

      if (empty($items)) {
        $this->logger->warning('No POD items in order @id', ['@id' => $order_id]);
        return FALSE;
      }

      // Determine Printful store from first item's product.
      $first_item = $order->getItems()[0];
      $product = $first_item->getPurchasedEntity()->getProduct();
      $store_node = $product->get('field_store')->entity;
      $printful_store_id = $store_node ? $store_node->get('field_x_printful_store_id')->value : NULL;

      $options = $this->getRequestOptions();
      if ($printful_store_id) {
        $options['headers']['X-PF-Store-Id'] = $printful_store_id;
      }

      $options['json'] = [
        'recipient' => $recipient,
        'items' => $items,
        'external_id' => (string) $order_id,
      ];

      $response = $this->httpClient->request('POST', self::API_BASE . '/orders', $options);
      $result = json_decode($response->getBody()->getContents(), TRUE);

      if (!empty($result['result']['id'])) {
        $this->logger->info('Printful order @pf_id created for order @id', [
          '@pf_id' => $result['result']['id'],
          '@id' => $order_id,
        ]);
        return TRUE;
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Printful order submission failed for order @id: @message', [
        '@id' => $order_id,
        '@message' => $e->getMessage(),
      ]);
    }

    return FALSE;
  }

  /**
   * Gets shipping rates from Printful for a cart.
   *
   * @param array $items
   *   Array of cart item data.
   * @param array $address
   *   The shipping address.
   *
   * @return array
   *   Array of shipping rate options.
   */
  public function getShippingRates(array $items, array $address): array {
    try {
      $options = $this->getRequestOptions();
      $options['json'] = [
        'recipient' => $address,
        'items' => $items,
      ];

      $response = $this->httpClient->request('POST', self::API_BASE . '/shipping/rates', $options);
      $result = json_decode($response->getBody()->getContents(), TRUE);

      return $result['result'] ?? [];
    }
    catch (\Exception $e) {
      $this->logger->error('Printful shipping rate fetch failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Processes a Printful webhook event.
   *
   * @param string $event_type
   *   The webhook event type.
   * @param array $data
   *   The webhook payload data.
   */
  public function processWebhook(string $event_type, array $data): void {
    $this->logger->info('Printful webhook received: @type', ['@type' => $event_type]);

    switch ($event_type) {
      case 'package_shipped':
        $this->handlePackageShipped($data);
        break;

      case 'package_returned':
        $this->logger->warning('Printful package returned: @data', [
          '@data' => json_encode($data),
        ]);
        break;

      case 'order_failed':
        $this->logger->error('Printful order failed: @data', [
          '@data' => json_encode($data),
        ]);
        break;

      default:
        $this->logger->notice('Unhandled Printful webhook event: @type', [
          '@type' => $event_type,
        ]);
    }
  }

  /**
   * Handles the package_shipped webhook event.
   */
  protected function handlePackageShipped(array $data): void {
    $shipment = $data['shipment'] ?? [];
    $tracking_number = $shipment['tracking_number'] ?? '';

    $order_data = $data['order'] ?? [];
    $external_id = $order_data['external_id'] ?? '';

    if (!$external_id) {
      $this->logger->warning('No external_id in package_shipped webhook');
      return;
    }

    $order = $this->entityTypeManager->getStorage('commerce_order')->load($external_id);
    if (!$order) {
      $this->logger->warning('Order @id not found for shipped webhook', ['@id' => $external_id]);
      return;
    }

    $state = $order->getState();
    $transitions = $state->getTransitions();
    if (isset($transitions['fulfill'])) {
      $state->applyTransition($transitions['fulfill']);
      $order->save();
      $this->logger->info('Order @id marked as fulfilled. Tracking: @tracking', [
        '@id' => $external_id,
        '@tracking' => $tracking_number,
      ]);
    }
  }

}

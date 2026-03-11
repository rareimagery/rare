<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a current user's stores REST resource.
 *
 * @RestResource(
 *   id = "current_user_store",
 *   label = @Translation("Current User Stores"),
 *   uri_paths = {
 *     "canonical" = "/api/dashboard/my-stores"
 *   }
 * )
 */
class CurrentUserStoreResource extends ResourceBase {

  protected StoreManagerService $storeManager;
  protected AccountProxyInterface $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    StoreManagerService $store_manager,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storeManager = $store_manager;
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('rareimagery_xstore.store_manager'),
      $container->get('current_user'),
    );
  }

  public function get(): ResourceResponse {
    $uid = $this->currentUser->id();
    $stores = $this->storeManager->getUserStores($uid);

    $data = ['stores' => []];
    foreach ($stores as $store_node) {
      $commerce_store = $this->storeManager->getCommerceStore($store_node);

      $data['stores'][] = [
        'nodeId' => (int) $store_node->id(),
        'uuid' => $store_node->uuid(),
        'handle' => $store_node->get('field_x_handle')->value,
        'storeName' => $store_node->getTitle(),
        'commerceStoreId' => $commerce_store ? (int) $commerce_store->id() : NULL,
        'stripeAccountId' => $store_node->hasField('field_stripe_account_id') ? $store_node->get('field_stripe_account_id')->value : NULL,
        'printfulStoreId' => $store_node->hasField('field_x_printful_store_id') ? $store_node->get('field_x_printful_store_id')->value : NULL,
        'subscriptionStatus' => $store_node->hasField('field_subscription_status') ? $store_node->get('field_subscription_status')->value : NULL,
        'stripeCustomerId' => $store_node->hasField('field_stripe_customer_id') ? $store_node->get('field_stripe_customer_id')->value : NULL,
      ];
    }

    $response = new ResourceResponse($data);
    $response->getCacheableMetadata()->addCacheContexts(['user']);
    return $response;
  }

}

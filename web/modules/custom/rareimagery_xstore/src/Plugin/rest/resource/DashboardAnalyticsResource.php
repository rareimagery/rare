<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Provides dashboard analytics REST resource.
 *
 * @RestResource(
 *   id = "dashboard_analytics",
 *   label = @Translation("Dashboard Analytics"),
 *   uri_paths = {
 *     "canonical" = "/api/dashboard/stores/{store_nid}/analytics"
 *   }
 * )
 */
class DashboardAnalyticsResource extends ResourceBase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected StoreManagerService $storeManager;
  protected AccountProxyInterface $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    StoreManagerService $store_manager,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
      $container->get('rareimagery_xstore.store_manager'),
      $container->get('current_user'),
    );
  }

  public function get(int $store_nid): ResourceResponse {
    // Verify ownership.
    if (!$this->storeManager->userOwnsStore($this->currentUser->id(), $store_nid)) {
      throw new AccessDeniedHttpException('You do not own this store.');
    }

    $commerce_store = $this->storeManager->getCommerceStoreByNodeId($store_nid);
    if (!$commerce_store) {
      return new ResourceResponse([
        'total_orders' => 0,
        'total_revenue' => ['number' => '0.00', 'currency_code' => 'USD'],
        'orders_by_status' => [],
      ]);
    }

    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $order_ids = $order_storage->getQuery()
      ->condition('store_id', $commerce_store->id())
      ->condition('state', 'draft', '!=')
      ->accessCheck(FALSE)
      ->execute();

    $total_orders = count($order_ids);
    $total_revenue = 0;
    $orders_by_status = [];

    if ($order_ids) {
      $orders = $order_storage->loadMultiple($order_ids);
      foreach ($orders as $order) {
        $state = $order->getState()->getId();
        $orders_by_status[$state] = ($orders_by_status[$state] ?? 0) + 1;
        $total_price = $order->getTotalPrice();
        if ($total_price) {
          $total_revenue += (float) $total_price->getNumber();
        }
      }
    }

    $data = [
      'total_orders' => $total_orders,
      'total_revenue' => [
        'number' => number_format($total_revenue, 2, '.', ''),
        'currency_code' => 'USD',
      ],
      'orders_by_status' => $orders_by_status,
    ];

    $response = new ResourceResponse($data);
    $response->getCacheableMetadata()->addCacheContexts(['user']);
    return $response;
  }

}

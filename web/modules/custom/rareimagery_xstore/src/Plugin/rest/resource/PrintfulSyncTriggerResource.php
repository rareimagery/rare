<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rareimagery_xstore\Service\PrintfulSyncService;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides Printful sync trigger REST resource.
 *
 * @RestResource(
 *   id = "printful_sync_trigger",
 *   label = @Translation("Printful Sync Trigger"),
 *   uri_paths = {
 *     "create" = "/api/dashboard/stores/{store_nid}/printful-sync"
 *   }
 * )
 */
class PrintfulSyncTriggerResource extends ResourceBase {

  protected PrintfulSyncService $printfulSync;
  protected StoreManagerService $storeManager;
  protected AccountProxyInterface $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    PrintfulSyncService $printful_sync,
    StoreManagerService $store_manager,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->printfulSync = $printful_sync;
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
      $container->get('rareimagery_xstore.printful_sync'),
      $container->get('rareimagery_xstore.store_manager'),
      $container->get('current_user'),
    );
  }

  public function post(int $store_nid): ModifiedResourceResponse {
    if (!$this->storeManager->userOwnsStore($this->currentUser->id(), $store_nid)) {
      throw new AccessDeniedHttpException('You do not own this store.');
    }

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $store_node = $node_storage->load($store_nid);

    if (!$store_node || $store_node->bundle() !== 'x_creator_store') {
      throw new NotFoundHttpException('Store not found.');
    }

    $printful_store_id = $store_node->get('field_x_printful_store_id')->value ?? '';
    if (empty($printful_store_id)) {
      return new ModifiedResourceResponse(['error' => 'No Printful store ID configured.'], 400);
    }

    $synced_count = $this->printfulSync->syncProducts($printful_store_id, $store_nid);

    return new ModifiedResourceResponse(['synced_count' => $synced_count]);
  }

}

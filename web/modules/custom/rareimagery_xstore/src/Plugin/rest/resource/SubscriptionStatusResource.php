<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns subscription status for a store.
 *
 * @RestResource(
 *   id = "subscription_status",
 *   label = @Translation("Subscription Status"),
 *   uri_paths = {
 *     "canonical" = "/api/dashboard/stores/{store_nid}/subscription/status"
 *   }
 * )
 */
class SubscriptionStatusResource extends ResourceBase {

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

  public function get(int $store_nid): ResourceResponse {
    if (!$this->storeManager->userOwnsStore($this->currentUser->id(), $store_nid)) {
      throw new AccessDeniedHttpException('You do not own this store.');
    }

    $storeNode = \Drupal::entityTypeManager()->getStorage('node')->load($store_nid);

    $data = [
      'status' => $storeNode->get('field_subscription_status')->value ?? 'pending',
      'subscriptionId' => $storeNode->get('field_stripe_subscription_id')->value,
      'customerId' => $storeNode->get('field_stripe_customer_id')->value,
    ];

    $response = new ResourceResponse($data);
    $response->getCacheableMetadata()->addCacheContexts(['user']);
    return $response;
  }

}

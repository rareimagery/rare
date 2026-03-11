<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\rareimagery_xstore\Service\SubscriptionManagerService;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Creates a Stripe Checkout Session for store subscription.
 *
 * @RestResource(
 *   id = "subscription_checkout",
 *   label = @Translation("Subscription Checkout"),
 *   uri_paths = {
 *     "create" = "/api/store/{store_nid}/subscription/checkout"
 *   }
 * )
 */
class SubscriptionCheckoutResource extends ResourceBase {

  protected StoreManagerService $storeManager;
  protected SubscriptionManagerService $subscriptionManager;
  protected AccountProxyInterface $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    StoreManagerService $store_manager,
    SubscriptionManagerService $subscription_manager,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storeManager = $store_manager;
    $this->subscriptionManager = $subscription_manager;
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
      $container->get('rareimagery_xstore.subscription_manager'),
      $container->get('current_user'),
    );
  }

  public function post(int $store_nid, Request $request): ModifiedResourceResponse {
    if (!$this->storeManager->userOwnsStore($this->currentUser->id(), $store_nid)) {
      throw new AccessDeniedHttpException('You do not own this store.');
    }

    $body = json_decode($request->getContent(), TRUE);
    $successUrl = $body['success_url'] ?? '';
    $cancelUrl = $body['cancel_url'] ?? '';

    if (empty($successUrl) || empty($cancelUrl)) {
      throw new BadRequestHttpException('success_url and cancel_url are required.');
    }

    $storeNode = \Drupal::entityTypeManager()->getStorage('node')->load($store_nid);

    // Don't allow checkout if already active.
    $status = $storeNode->get('field_subscription_status')->value;
    if ($status === 'active') {
      throw new BadRequestHttpException('Store already has an active subscription.');
    }

    try {
      $url = $this->subscriptionManager->createCheckoutSession($storeNode, $successUrl, $cancelUrl);
      return new ModifiedResourceResponse(['url' => $url]);
    }
    catch (\Exception $e) {
      $this->logger->error('Subscription checkout failed: @message', ['@message' => $e->getMessage()]);
      return new ModifiedResourceResponse(['error' => 'Failed to create checkout session.'], 500);
    }
  }

}

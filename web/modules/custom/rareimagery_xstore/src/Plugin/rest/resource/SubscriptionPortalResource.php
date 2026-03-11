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
 * Creates a Stripe Customer Portal session for billing management.
 *
 * @RestResource(
 *   id = "subscription_portal",
 *   label = @Translation("Subscription Portal"),
 *   uri_paths = {
 *     "create" = "/api/dashboard/stores/{store_nid}/subscription/portal"
 *   }
 * )
 */
class SubscriptionPortalResource extends ResourceBase {

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
    $returnUrl = $body['return_url'] ?? '';

    if (empty($returnUrl)) {
      throw new BadRequestHttpException('return_url is required.');
    }

    $storeNode = \Drupal::entityTypeManager()->getStorage('node')->load($store_nid);

    if (empty($storeNode->get('field_stripe_customer_id')->value)) {
      throw new BadRequestHttpException('No billing account found. Please subscribe first.');
    }

    try {
      $url = $this->subscriptionManager->createPortalSession($storeNode, $returnUrl);
      return new ModifiedResourceResponse(['url' => $url]);
    }
    catch (\Exception $e) {
      $this->logger->error('Subscription portal failed: @message', ['@message' => $e->getMessage()]);
      return new ModifiedResourceResponse(['error' => 'Failed to create billing portal session.'], 500);
    }
  }

}

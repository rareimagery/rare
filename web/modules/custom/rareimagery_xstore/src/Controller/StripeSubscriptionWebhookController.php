<?php

namespace Drupal\rareimagery_xstore\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rareimagery_xstore\Service\SubscriptionManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles incoming Stripe subscription webhook events.
 */
class StripeSubscriptionWebhookController extends ControllerBase {

  protected SubscriptionManagerService $subscriptionManager;

  public function __construct(SubscriptionManagerService $subscription_manager) {
    $this->subscriptionManager = $subscription_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rareimagery_xstore.subscription_manager'),
    );
  }

  /**
   * Processes a Stripe subscription webhook request.
   */
  public function handle(Request $request): JsonResponse {
    $payload = $request->getContent();
    $sigHeader = $request->headers->get('Stripe-Signature', '');

    if (empty($payload) || empty($sigHeader)) {
      return new JsonResponse(['error' => 'Missing payload or signature'], 400);
    }

    try {
      $this->subscriptionManager->handleWebhookEvent($payload, $sigHeader);
      return new JsonResponse(['status' => 'ok']);
    }
    catch (\Stripe\Exception\SignatureVerificationException $e) {
      return new JsonResponse(['error' => 'Invalid signature'], 400);
    }
    catch (\Exception $e) {
      \Drupal::logger('rareimagery_xstore')->error('Subscription webhook error: @message', [
        '@message' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Webhook processing failed'], 500);
    }
  }

}

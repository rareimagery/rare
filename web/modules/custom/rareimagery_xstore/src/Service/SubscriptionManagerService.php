<?php

namespace Drupal\rareimagery_xstore\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages store owner subscriptions via Stripe Billing.
 */
class SubscriptionManagerService {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('rareimagery_xstore');
  }

  /**
   * Creates a Stripe Checkout Session for store subscription.
   *
   * Charges a one-time setup fee immediately and starts a 30-day trial
   * before the monthly recurring charge begins.
   *
   * @return string
   *   The Stripe Checkout Session URL.
   */
  public function createCheckoutSession(NodeInterface $storeNode, string $successUrl, string $cancelUrl): string {
    $this->initStripe();
    $settings = $this->getSubscriptionSettings();

    $customerId = $this->getOrCreateStripeCustomer($storeNode);

    $session = \Stripe\Checkout\Session::create([
      'mode' => 'subscription',
      'customer' => $customerId,
      'line_items' => [
        [
          'price' => $settings['stripe_price_id'],
          'quantity' => 1,
        ],
        [
          'price_data' => [
            'currency' => 'usd',
            'product_data' => ['name' => 'Store Setup Fee'],
            'unit_amount' => (int) $settings['setup_fee_cents'],
          ],
          'quantity' => 1,
        ],
      ],
      'subscription_data' => [
        'trial_period_days' => 30,
        'metadata' => [
          'store_node_id' => (string) $storeNode->id(),
        ],
      ],
      'success_url' => $successUrl,
      'cancel_url' => $cancelUrl,
    ]);

    return $session->url;
  }

  /**
   * Creates a Stripe Customer Portal session.
   *
   * @return string
   *   The portal URL.
   */
  public function createPortalSession(NodeInterface $storeNode, string $returnUrl): string {
    $this->initStripe();

    $customerId = $storeNode->get('field_stripe_customer_id')->value;
    if (empty($customerId)) {
      throw new \RuntimeException('Store has no Stripe customer ID.');
    }

    $portalSession = \Stripe\BillingPortal\Session::create([
      'customer' => $customerId,
      'return_url' => $returnUrl,
    ]);

    return $portalSession->url;
  }

  /**
   * Handles an incoming Stripe webhook event.
   */
  public function handleWebhookEvent(string $payload, string $sigHeader): void {
    $settings = $this->getSubscriptionSettings();
    $webhookSecret = $settings['webhook_signing_secret'];

    if (empty($webhookSecret)) {
      throw new \RuntimeException('Subscription webhook secret not configured.');
    }

    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);

    match ($event->type) {
      'checkout.session.completed' => $this->onCheckoutCompleted($event),
      'invoice.paid' => $this->onInvoicePaid($event),
      'invoice.payment_failed' => $this->onInvoicePaymentFailed($event),
      'customer.subscription.updated' => $this->onSubscriptionUpdated($event),
      'customer.subscription.deleted' => $this->onSubscriptionDeleted($event),
      default => $this->logger->info('Unhandled subscription webhook event: @type', ['@type' => $event->type]),
    };
  }

  /**
   * Handles checkout.session.completed — activates the store.
   */
  protected function onCheckoutCompleted(\Stripe\Event $event): void {
    $session = $event->data->object;

    if ($session->mode !== 'subscription') {
      return;
    }

    $storeNodeId = $session->subscription
      ? ($session->metadata->store_node_id ?? NULL)
      : NULL;

    // Try metadata from subscription_data if not on session directly.
    if (empty($storeNodeId) && !empty($session->subscription)) {
      $this->initStripe();
      $subscription = \Stripe\Subscription::retrieve($session->subscription);
      $storeNodeId = $subscription->metadata->store_node_id ?? NULL;
    }

    if (empty($storeNodeId)) {
      $this->logger->warning('checkout.session.completed missing store_node_id metadata.');
      return;
    }

    $storeNode = $this->loadStoreNode((int) $storeNodeId);
    if (!$storeNode) {
      $this->logger->error('Store node @nid not found for checkout session.', ['@nid' => $storeNodeId]);
      return;
    }

    $storeNode->set('field_stripe_subscription_id', $session->subscription);
    $storeNode->set('field_stripe_customer_id', $session->customer);
    $storeNode->set('field_subscription_status', 'active');
    $storeNode->setPublished();
    $storeNode->save();

    $this->logger->info('Store @nid activated via subscription @sub.', [
      '@nid' => $storeNodeId,
      '@sub' => $session->subscription,
    ]);
  }

  /**
   * Handles invoice.paid — confirms subscription is active.
   */
  protected function onInvoicePaid(\Stripe\Event $event): void {
    $invoice = $event->data->object;

    if (empty($invoice->subscription)) {
      return;
    }

    $storeNode = $this->findStoreBySubscriptionId($invoice->subscription);
    if (!$storeNode) {
      return;
    }

    $currentStatus = $storeNode->get('field_subscription_status')->value;
    if ($currentStatus !== 'active') {
      $storeNode->set('field_subscription_status', 'active');
      $storeNode->setPublished();
      $storeNode->save();

      $this->logger->info('Store @nid reactivated after payment.', ['@nid' => $storeNode->id()]);
    }
  }

  /**
   * Handles invoice.payment_failed — unpublishes the store.
   */
  protected function onInvoicePaymentFailed(\Stripe\Event $event): void {
    $invoice = $event->data->object;

    if (empty($invoice->subscription)) {
      return;
    }

    $storeNode = $this->findStoreBySubscriptionId($invoice->subscription);
    if (!$storeNode) {
      return;
    }

    $storeNode->set('field_subscription_status', 'past_due');
    $storeNode->setUnpublished();
    $storeNode->save();

    $this->logger->warning('Store @nid unpublished due to failed payment.', ['@nid' => $storeNode->id()]);
  }

  /**
   * Handles customer.subscription.updated — syncs status.
   */
  protected function onSubscriptionUpdated(\Stripe\Event $event): void {
    $subscription = $event->data->object;

    $storeNode = $this->findStoreBySubscriptionId($subscription->id);
    if (!$storeNode) {
      return;
    }

    $statusMap = [
      'active' => 'active',
      'trialing' => 'active',
      'past_due' => 'past_due',
      'canceled' => 'canceled',
      'unpaid' => 'past_due',
    ];

    $newStatus = $statusMap[$subscription->status] ?? 'past_due';
    $storeNode->set('field_subscription_status', $newStatus);

    if (in_array($newStatus, ['active'])) {
      $storeNode->setPublished();
    }
    else {
      $storeNode->setUnpublished();
    }

    $storeNode->save();
  }

  /**
   * Handles customer.subscription.deleted — cancels and unpublishes.
   */
  protected function onSubscriptionDeleted(\Stripe\Event $event): void {
    $subscription = $event->data->object;

    $storeNode = $this->findStoreBySubscriptionId($subscription->id);
    if (!$storeNode) {
      return;
    }

    $storeNode->set('field_subscription_status', 'canceled');
    $storeNode->setUnpublished();
    $storeNode->save();

    $this->logger->info('Store @nid canceled and unpublished.', ['@nid' => $storeNode->id()]);
  }

  /**
   * Returns the current subscription status for a store.
   */
  public function getSubscriptionStatus(NodeInterface $storeNode): ?string {
    return $storeNode->get('field_subscription_status')->value;
  }

  /**
   * Returns TRUE if the store has an active subscription.
   */
  public function isStoreActive(NodeInterface $storeNode): bool {
    return $this->getSubscriptionStatus($storeNode) === 'active';
  }

  /**
   * Gets or creates a Stripe Customer for the store owner.
   */
  protected function getOrCreateStripeCustomer(NodeInterface $storeNode): string {
    $existingId = $storeNode->get('field_stripe_customer_id')->value;
    if (!empty($existingId)) {
      return $existingId;
    }

    $owner = $storeNode->getOwner();
    $customer = \Stripe\Customer::create([
      'email' => $owner->getEmail(),
      'metadata' => [
        'drupal_uid' => (string) $owner->id(),
        'store_node_id' => (string) $storeNode->id(),
      ],
    ]);

    $storeNode->set('field_stripe_customer_id', $customer->id);
    $storeNode->save();

    return $customer->id;
  }

  /**
   * Finds a store node by Stripe subscription ID.
   */
  protected function findStoreBySubscriptionId(string $subscriptionId): ?NodeInterface {
    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'x_creator_store')
      ->condition('field_stripe_subscription_id', $subscriptionId)
      ->range(0, 1)
      ->execute();

    if (empty($nids)) {
      return NULL;
    }

    return $this->entityTypeManager->getStorage('node')->load(reset($nids));
  }

  /**
   * Loads a store node by ID.
   */
  protected function loadStoreNode(int $nid): ?NodeInterface {
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    if (!$node || $node->bundle() !== 'x_creator_store') {
      return NULL;
    }
    return $node;
  }

  /**
   * Returns subscription settings from config.
   */
  protected function getSubscriptionSettings(): array {
    return $this->configFactory->get('rareimagery_xstore.settings')->get('subscription') ?? [];
  }

  /**
   * Initializes the Stripe API key.
   */
  protected function initStripe(): void {
    $stripeSecret = getenv('STRIPE_SECRET_KEY');
    if (empty($stripeSecret)) {
      throw new \RuntimeException('STRIPE_SECRET_KEY environment variable is not set.');
    }
    \Stripe\Stripe::setApiKey($stripeSecret);
  }

}

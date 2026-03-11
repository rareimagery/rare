<?php

namespace Drupal\rareimagery_xstore\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Stripe Connect transfer_data to payment intents.
 *
 * For each order, looks up the creator's Stripe Connected Account ID
 * and adds application_fee_amount + transfer_data.destination so that
 * the platform retains its fee and the creator receives the remainder.
 */
class StripeConnectSubscriber implements EventSubscriberInterface {

  /**
   * Platform fee amounts in cents, keyed by order type.
   */
  protected const PLATFORM_FEES = [
    'pod_order' => 100,
    'custom_order' => 100,
    'digital_order' => 5,
  ];

  /**
   * Constructs a StripeConnectSubscriber.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StoreManagerService $storeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Listen for the Stripe payment intent creation event.
    // Event class: Drupal\commerce_stripe\Event\PaymentIntentCreateEvent
    return [
      'commerce_stripe.payment_intent_create' => 'onPaymentIntentCreate',
    ];
  }

  /**
   * Adds transfer_data to the PaymentIntent for Stripe Connect.
   */
  public function onPaymentIntentCreate($event) {
    $order = $event->getOrder();
    $connected_account_id = $this->resolveConnectedAccountId($order);

    if (!$connected_account_id) {
      return;
    }

    $intent_attributes = $event->getIntentAttributes();
    $fee_cents = self::PLATFORM_FEES[$order->bundle()] ?? 0;

    if ($fee_cents > 0) {
      $intent_attributes['application_fee_amount'] = $fee_cents;
      $intent_attributes['transfer_data'] = [
        'destination' => $connected_account_id,
      ];
      $event->setIntentAttributes($intent_attributes);
    }
  }

  /**
   * Resolves the Stripe Connected Account ID from order items.
   *
   * Traces: order -> order items -> purchased entity -> product
   * -> field_store (node) -> field_stripe_account_id.
   */
  protected function resolveConnectedAccountId(OrderInterface $order): ?string {
    foreach ($order->getItems() as $order_item) {
      $purchased_entity = $order_item->getPurchasedEntity();
      if (!$purchased_entity) {
        continue;
      }

      $product = $purchased_entity->getProduct();
      if (!$product || $product->get('field_store')->isEmpty()) {
        continue;
      }

      $store_node = $product->get('field_store')->entity;
      if (!$store_node || $store_node->get('field_stripe_account_id')->isEmpty()) {
        continue;
      }

      return $store_node->get('field_stripe_account_id')->value;
    }

    return NULL;
  }

}

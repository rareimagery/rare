<?php

namespace Drupal\rareimagery_xstore\EventSubscriber;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\commerce_price\Price;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds the RareImagery platform fee to orders based on order type.
 *
 * Fee structure:
 * - $1.00 per physical order (pod_order or custom_order)
 * - $0.05 per digital order (digital_order)
 */
class PlatformFeeSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      OrderEvents::ORDER_PRESAVE => 'addPlatformFee',
    ];
  }

  /**
   * Adds the platform fee adjustment to the order.
   *
   * Only applies to new draft orders to avoid duplicate fees.
   */
  public function addPlatformFee(OrderEvent $event) {
    $order = $event->getOrder();

    // Only apply to new draft orders to prevent duplicate fees.
    if (!$order->isNew() || $order->getState()->getId() !== 'draft') {
      return;
    }

    $fee = new Price('0.00', 'USD');
    $type = $order->bundle();

    if (in_array($type, ['pod_order', 'custom_order'])) {
      $fee = new Price('1.00', 'USD');
    }
    elseif ($type === 'digital_order') {
      $fee = new Price('0.05', 'USD');
    }

    if ($fee->getNumber() > 0) {
      $order->addAdjustment(new Adjustment([
        'type' => 'fee',
        'label' => 'RareImagery Platform Fee',
        'amount' => $fee,
        'included' => FALSE,
        'locked' => TRUE,
      ]));
    }
  }

}

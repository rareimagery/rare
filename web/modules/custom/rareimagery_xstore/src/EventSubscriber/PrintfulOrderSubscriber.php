<?php

namespace Drupal\rareimagery_xstore\EventSubscriber;

use Drupal\rareimagery_xstore\Service\PrintfulSyncService;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Submits POD orders to Printful when they are placed.
 */
class PrintfulOrderSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a PrintfulOrderSubscriber.
   */
  public function __construct(
    protected PrintfulSyncService $printfulSync,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_order.place.post_transition' => 'onOrderPlace',
    ];
  }

  /**
   * Submits the order to Printful if it is a POD order.
   */
  public function onOrderPlace(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    if ($order->bundle() !== 'pod_order') {
      return;
    }

    $this->printfulSync->submitOrder((int) $order->id());
  }

}

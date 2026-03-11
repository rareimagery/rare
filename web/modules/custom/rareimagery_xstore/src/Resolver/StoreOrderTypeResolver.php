<?php

namespace Drupal\rareimagery_xstore\Resolver;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_order\Resolver\OrderTypeResolverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Resolves the order type based on the product variation type.
 *
 * This enables mixed-cart splitting: items with different variation types
 * are routed to separate order types (and thus separate carts).
 *
 * Mapping:
 * - pod_variation    → pod_order
 * - custom_variation → custom_order
 * - digital_variation → digital_order
 */
class StoreOrderTypeResolver implements OrderTypeResolverInterface {

  /**
   * The variation type to order type mapping.
   */
  protected const VARIATION_ORDER_MAP = [
    'pod_variation' => 'pod_order',
    'custom_variation' => 'custom_order',
    'digital_variation' => 'digital_order',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new StoreOrderTypeResolver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OrderItemInterface $order_item) {
    $purchased_entity = $order_item->getPurchasedEntity();

    if ($purchased_entity === NULL) {
      return NULL;
    }

    $variation_type = $purchased_entity->bundle();

    if (isset(self::VARIATION_ORDER_MAP[$variation_type])) {
      $order_type_id = self::VARIATION_ORDER_MAP[$variation_type];
      $order_type_storage = $this->entityTypeManager->getStorage('commerce_order_type');
      $order_type = $order_type_storage->load($order_type_id);

      if ($order_type) {
        return $order_type_id;
      }
    }

    return NULL;
  }

}

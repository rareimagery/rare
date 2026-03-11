<?php

namespace Drupal\rareimagery_xstore\Service;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service for managing X Creator Store operations.
 *
 * Provides helper methods for loading stores, validating ownership,
 * and managing store lifecycle.
 */
class StoreManagerService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a StoreManagerService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Loads a store node by X handle.
   *
   * @param string $handle
   *   The X handle (without @ prefix).
   *
   * @return \Drupal\node\NodeInterface|null
   *   The store node, or NULL if not found.
   */
  public function loadByHandle(string $handle): ?NodeInterface {
    $handle = ltrim($handle, '@');

    $nodes = $this->entityTypeManager
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'x_creator_store',
        'field_x_handle' => $handle,
        'status' => 1,
      ]);

    return $nodes ? reset($nodes) : NULL;
  }

  /**
   * Checks if a handle already has a registered store.
   *
   * @param string $handle
   *   The X handle to check.
   *
   * @return bool
   *   TRUE if a store exists for this handle.
   */
  public function handleExists(string $handle): bool {
    $handle = ltrim($handle, '@');

    $count = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'x_creator_store')
      ->condition('field_x_handle', $handle)
      ->count()
      ->execute();

    return $count > 0;
  }

  /**
   * Checks if a user owns a specific store.
   *
   * @param int $uid
   *   The user ID.
   * @param int $store_nid
   *   The store node ID.
   *
   * @return bool
   *   TRUE if the user owns the store.
   */
  public function userOwnsStore(int $uid, int $store_nid): bool {
    $node = $this->entityTypeManager->getStorage('node')->load($store_nid);

    if (!$node || $node->bundle() !== 'x_creator_store') {
      return FALSE;
    }

    return (int) $node->getOwnerId() === $uid;
  }

  /**
   * Gets all stores owned by a user.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return \Drupal\node\NodeInterface[]
   *   Array of store nodes.
   */
  public function getUserStores(int $uid): array {
    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'x_creator_store')
      ->condition('uid', $uid)
      ->sort('created', 'DESC')
      ->execute();

    if (empty($nids)) {
      return [];
    }

    return $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
  }

  /**
   * Generates a SKU prefix for a store.
   *
   * @param string $handle
   *   The X handle.
   *
   * @return string
   *   The SKU prefix (e.g., "RAREIMAGERY").
   */
  public function generateSkuPrefix(string $handle): string {
    return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $handle));
  }

  /**
   * Gets the Commerce store entity for a creator store node.
   *
   * @param \Drupal\node\NodeInterface $store_node
   *   The x_creator_store node.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The linked commerce_store, or NULL if not set.
   */
  public function getCommerceStore(NodeInterface $store_node): ?StoreInterface {
    if ($store_node->bundle() !== 'x_creator_store') {
      return NULL;
    }
    if ($store_node->get('field_commerce_store')->isEmpty()) {
      return NULL;
    }
    $entity = $store_node->get('field_commerce_store')->entity;
    return $entity instanceof StoreInterface ? $entity : NULL;
  }

  /**
   * Gets the Commerce store entity by X handle.
   *
   * @param string $handle
   *   The X handle (without @ prefix).
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The commerce_store, or NULL if not found.
   */
  public function getCommerceStoreByHandle(string $handle): ?StoreInterface {
    $node = $this->loadByHandle($handle);
    return $node ? $this->getCommerceStore($node) : NULL;
  }

  /**
   * Gets the Commerce store entity by store node ID.
   *
   * @param int $store_nid
   *   The store node ID.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The commerce_store, or NULL if not found.
   */
  public function getCommerceStoreByNodeId(int $store_nid): ?StoreInterface {
    $node = $this->entityTypeManager->getStorage('node')->load($store_nid);
    if (!$node || $node->bundle() !== 'x_creator_store') {
      return NULL;
    }
    return $this->getCommerceStore($node);
  }

}

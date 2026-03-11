<?php

namespace Drupal\rareimagery_xstore\Resolver;

use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_store\Resolver\StoreResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\rareimagery_xstore\Service\StoreManagerService;

/**
 * Resolves the Commerce store from the current X creator store page context.
 *
 * When a visitor is on /store/{x_handle}, this resolver loads the
 * corresponding commerce_store entity linked from the x_creator_store node.
 */
class CreatorStoreResolver implements StoreResolverInterface {

  /**
   * Constructs a CreatorStoreResolver.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected StoreManagerService $storeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function resolve(): ?StoreInterface {
    if ($this->routeMatch->getRouteName() !== 'rareimagery_xstore.store_page') {
      return NULL;
    }

    $x_handle = $this->routeMatch->getParameter('x_handle');
    if (!$x_handle) {
      return NULL;
    }

    return $this->storeManager->getCommerceStoreByHandle($x_handle);
  }

}

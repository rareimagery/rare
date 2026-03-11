<?php

namespace Drupal\rareimagery_xstore\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controller for the /store/{x_handle} storefront page.
 *
 * Serves the React storefront app with initial store data hydrated
 * via drupalSettings to avoid loading spinners.
 */
class StorePageController extends ControllerBase {

  /**
   * Renders the React storefront app for a given X handle.
   *
   * @param string $x_handle
   *   The X (Twitter) handle.
   *
   * @return array
   *   A render array for the storefront React app.
   */
  public function view(string $x_handle): array {
    $store_node = $this->loadStoreByHandle($x_handle);

    if (!$store_node) {
      throw new NotFoundHttpException();
    }

    $profile_data = $this->buildProfileData($store_node);

    return [
      '#theme' => 'storefront_app',
      '#attached' => [
        'library' => ['rareimagery/storefront-app'],
        'drupalSettings' => [
          'rareimagery_storefront' => $profile_data,
        ],
      ],
      '#cache' => [
        'tags' => $store_node->getCacheTags(),
      ],
    ];
  }

  /**
   * Returns the page title for the store.
   */
  public function title(string $x_handle): string {
    $store_node = $this->loadStoreByHandle($x_handle);

    if ($store_node) {
      return '@' . $store_node->get('field_x_handle')->value . ' Store';
    }

    return 'Store Not Found';
  }

  /**
   * Builds the store profile data array for drupalSettings.
   */
  protected function buildProfileData($store_node): array {
    $avatar_url = '';
    if (!$store_node->get('field_x_avatar')->isEmpty()) {
      $avatar_file = $store_node->get('field_x_avatar')->entity;
      if ($avatar_file instanceof File) {
        $avatar_url = \Drupal::service('file_url_generator')->generateAbsoluteString($avatar_file->getFileUri());
      }
    }

    $banner_url = '';
    if (!$store_node->get('field_x_banner')->isEmpty()) {
      $banner_file = $store_node->get('field_x_banner')->entity;
      if ($banner_file instanceof File) {
        $banner_url = \Drupal::service('file_url_generator')->generateAbsoluteString($banner_file->getFileUri());
      }
    }

    $logo_url = '';
    if ($store_node->hasField('field_store_logo') && !$store_node->get('field_store_logo')->isEmpty()) {
      $logo_file = $store_node->get('field_store_logo')->entity;
      if ($logo_file instanceof File) {
        $logo_url = \Drupal::service('file_url_generator')->generateAbsoluteString($logo_file->getFileUri());
      }
    }

    $commerce_store = NULL;
    if ($store_node->hasField('field_commerce_store') && !$store_node->get('field_commerce_store')->isEmpty()) {
      $commerce_store = $store_node->get('field_commerce_store')->entity;
    }

    return [
      'nodeId' => (int) $store_node->id(),
      'uuid' => $store_node->uuid(),
      'handle' => $store_node->get('field_x_handle')->value,
      'bio' => $store_node->get('field_x_bio')->value ?? '',
      'avatarUrl' => $avatar_url,
      'bannerUrl' => $banner_url,
      'logoUrl' => $logo_url,
      'about' => $store_node->hasField('field_about') ? ($store_node->get('field_about')->value ?? '') : '',
      'followers' => (int) ($store_node->get('field_x_followers')->value ?? 0),
      'verified' => (bool) ($store_node->get('field_x_verified')->value ?? FALSE),
      'brandColor' => $store_node->get('field_x_brand_color')->value ?? '#000000',
      'tagline' => $store_node->get('field_x_tagline')->value ?? '',
      'commerceStoreId' => $commerce_store ? (int) $commerce_store->id() : NULL,
      'commerceStoreUuid' => $commerce_store ? $commerce_store->uuid() : NULL,
      'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
      'theme' => $this->loadCreatorTheme($store_node),
    ];
  }

  /**
   * Loads the creator profile theme for a store node.
   *
   * @return array|null
   *   Theme data array, or NULL if no published theme exists.
   */
  protected function loadCreatorTheme($store_node): ?array {
    /** @var \Drupal\rareimagery_xstore\Service\CreatorThemeService $theme_service */
    $theme_service = \Drupal::service('rareimagery_xstore.creator_theme');
    return $theme_service->loadByCreatorStore($store_node);
  }

  /**
   * Loads a store node by X handle.
   */
  protected function loadStoreByHandle(string $handle) {
    $nodes = $this->entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'x_creator_store',
        'field_x_handle' => $handle,
        'status' => 1,
      ]);

    return $nodes ? reset($nodes) : NULL;
  }

}

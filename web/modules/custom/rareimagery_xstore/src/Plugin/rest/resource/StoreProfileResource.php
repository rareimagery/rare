<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\rareimagery_xstore\Service\CreatorThemeService;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a store profile REST resource.
 *
 * @RestResource(
 *   id = "store_profile",
 *   label = @Translation("Store Profile"),
 *   uri_paths = {
 *     "canonical" = "/api/store/{handle}/profile"
 *   }
 * )
 */
class StoreProfileResource extends ResourceBase {

  protected StoreManagerService $storeManager;
  protected FileUrlGeneratorInterface $fileUrlGenerator;
  protected CreatorThemeService $creatorTheme;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    StoreManagerService $store_manager,
    FileUrlGeneratorInterface $file_url_generator,
    CreatorThemeService $creator_theme,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->storeManager = $store_manager;
    $this->fileUrlGenerator = $file_url_generator;
    $this->creatorTheme = $creator_theme;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('rareimagery_xstore.store_manager'),
      $container->get('file_url_generator'),
      $container->get('rareimagery_xstore.creator_theme'),
    );
  }

  public function get(string $handle): ResourceResponse {
    $store_node = $this->storeManager->loadByHandle($handle);

    if (!$store_node) {
      throw new NotFoundHttpException("Store @{$handle} not found.");
    }

    $avatar_url = '';
    if ($store_node->hasField('field_x_avatar') && !$store_node->get('field_x_avatar')->isEmpty()) {
      $file = $store_node->get('field_x_avatar')->entity;
      if ($file) {
        $avatar_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    $banner_url = '';
    if ($store_node->hasField('field_x_banner') && !$store_node->get('field_x_banner')->isEmpty()) {
      $file = $store_node->get('field_x_banner')->entity;
      if ($file) {
        $banner_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    $logo_url = '';
    if ($store_node->hasField('field_store_logo') && !$store_node->get('field_store_logo')->isEmpty()) {
      $file = $store_node->get('field_store_logo')->entity;
      if ($file) {
        $logo_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    $commerce_store = $this->storeManager->getCommerceStore($store_node);

    $data = [
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
      'stripeAccountId' => $store_node->hasField('field_stripe_account_id') ? $store_node->get('field_stripe_account_id')->value : NULL,
      'printfulStoreId' => $store_node->hasField('field_x_printful_store_id') ? $store_node->get('field_x_printful_store_id')->value : NULL,
      'theme' => $this->creatorTheme->loadByCreatorStore($store_node),
      'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
    ];

    $response = new ResourceResponse($data);
    $response->addCacheableDependency($store_node);
    return $response;
  }

}

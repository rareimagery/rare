<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list of all published creator stores.
 *
 * @RestResource(
 *   id = "store_list",
 *   label = @Translation("Store List"),
 *   uri_paths = {
 *     "canonical" = "/api/stores"
 *   }
 * )
 */
class StoreListResource extends ResourceBase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entity_type_manager,
    FileUrlGeneratorInterface $file_url_generator,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityTypeManager = $entity_type_manager;
    $this->fileUrlGenerator = $file_url_generator;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
    );
  }

  public function get(): ResourceResponse {
    $storage = $this->entityTypeManager->getStorage('node');

    $nids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'x_creator_store')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->execute();

    $stores = [];

    if ($nids) {
      $nodes = $storage->loadMultiple($nids);

      foreach ($nodes as $node) {
        $avatar_url = '';
        if ($node->hasField('field_x_avatar') && !$node->get('field_x_avatar')->isEmpty()) {
          $file = $node->get('field_x_avatar')->entity;
          if ($file) {
            $avatar_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          }
        }

        $banner_url = '';
        if ($node->hasField('field_x_banner') && !$node->get('field_x_banner')->isEmpty()) {
          $file = $node->get('field_x_banner')->entity;
          if ($file) {
            $banner_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          }
        }

        $logo_url = '';
        if ($node->hasField('field_store_logo') && !$node->get('field_store_logo')->isEmpty()) {
          $file = $node->get('field_store_logo')->entity;
          if ($file) {
            $logo_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
          }
        }

        $stores[] = [
          'handle' => $node->get('field_x_handle')->value,
          'bio' => $node->get('field_x_bio')->value ?? '',
          'avatarUrl' => $avatar_url,
          'bannerUrl' => $banner_url,
          'logoUrl' => $logo_url,
          'followers' => (int) ($node->get('field_x_followers')->value ?? 0),
          'verified' => (bool) ($node->get('field_x_verified')->value ?? FALSE),
          'brandColor' => $node->get('field_x_brand_color')->value ?? '#000000',
          'tagline' => $node->get('field_x_tagline')->value ?? '',
        ];
      }
    }

    $response = new ResourceResponse($stores);
    // Add list cache tag so response invalidates when stores change.
    $cache_metadata = new \Drupal\Core\Cache\CacheableMetadata();
    $cache_metadata->addCacheTags(['node_list:x_creator_store']);
    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

}

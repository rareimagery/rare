<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\rareimagery_xstore\Service\XProfileScraperService;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Previews X profile data without creating a store.
 *
 * @RestResource(
 *   id = "x_profile_preview",
 *   label = @Translation("X Profile Preview"),
 *   uri_paths = {
 *     "canonical" = "/api/x-profile/{handle}/preview"
 *   }
 * )
 */
class XProfilePreviewResource extends ResourceBase {

  /**
   * Constructs an XProfilePreviewResource.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected XProfileScraperService $scraper,
    protected StoreManagerService $storeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('rareimagery_xstore.x_profile_scraper'),
      $container->get('rareimagery_xstore.store_manager'),
    );
  }

  /**
   * Responds to GET requests.
   *
   * @param string $handle
   *   The X handle to preview.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response with scraped profile data.
   */
  public function get(string $handle): ResourceResponse {
    $handle = ltrim($handle, '@');

    if (!$this->scraper->validateHandle($handle)) {
      throw new BadRequestHttpException('Invalid X handle format. Use 1-15 characters: letters, numbers, underscores.');
    }

    $profileData = $this->scraper->scrapeProfile($handle);

    $response = new ResourceResponse([
      'handle' => $profileData['handle'],
      'displayName' => $profileData['display_name'],
      'bio' => $profileData['bio'],
      'avatarUrl' => $profileData['avatar_url'],
      'bannerUrl' => $profileData['banner_url'],
      'followers' => $profileData['followers'],
      'verified' => $profileData['verified'],
      'handleTaken' => $this->storeManager->handleExists($handle),
      'error' => $profileData['error'],
    ]);

    // Short cache since this is a preview.
    $cache_metadata = new \Drupal\Core\Cache\CacheableMetadata();
    $cache_metadata->setCacheMaxAge(60);
    $response->addCacheableDependency($cache_metadata);

    return $response;
  }

}

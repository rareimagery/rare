<?php

namespace Drupal\rareimagery_xstore\Plugin\rest\resource;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rareimagery_xstore\Service\StoreManagerService;
use Drupal\rareimagery_xstore\Service\XProfileScraperService;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Creates a new store from an X profile.
 *
 * @RestResource(
 *   id = "store_create",
 *   label = @Translation("Store Create"),
 *   uri_paths = {
 *     "create" = "/api/store/create"
 *   }
 * )
 */
class StoreCreateResource extends ResourceBase {

  /**
   * Constructs a StoreCreateResource.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected XProfileScraperService $scraper,
    protected StoreManagerService $storeManager,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileUrlGeneratorInterface $fileUrlGenerator,
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
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
    );
  }

  /**
   * Responds to POST requests.
   *
   * Creates a new x_creator_store node with scraped X profile data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response with the created store data.
   */
  public function post(Request $request): ModifiedResourceResponse {
    if ($this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException('Authentication required to create a store.');
    }

    $body = json_decode($request->getContent(), TRUE);
    if (empty($body) || empty($body['handle'])) {
      throw new BadRequestHttpException('Request body must include a "handle" field.');
    }

    $handle = ltrim($body['handle'], '@');

    if (!$this->scraper->validateHandle($handle)) {
      throw new BadRequestHttpException('Invalid X handle format.');
    }

    if ($this->storeManager->handleExists($handle)) {
      throw new BadRequestHttpException("A store for @{$handle} already exists.");
    }

    // Scrape the X profile.
    $profileData = $this->scraper->scrapeProfile($handle);

    if ($profileData['error'] === 'not_found') {
      throw new BadRequestHttpException("X profile @{$handle} not found.");
    }

    // Download avatar image.
    $avatarFile = NULL;
    if (!empty($profileData['avatar_url'])) {
      $avatarFile = $this->scraper->downloadImage(
        $profileData['avatar_url'],
        'public://x-stores/avatars',
        $handle
      );
    }

    // Download banner image.
    $bannerFile = NULL;
    if (!empty($profileData['banner_url'])) {
      $bannerFile = $this->scraper->downloadImage(
        $profileData['banner_url'],
        'public://x-stores/banners',
        $handle
      );
    }

    // Determine field values (user overrides take precedence).
    $bio = $body['bio'] ?? $profileData['bio'] ?? '';
    $tagline = $body['tagline'] ?? '';
    $brandColor = $body['brandColor'] ?? '#000000';
    $about = $body['about'] ?? '';
    $displayName = $profileData['display_name'] ?: '@' . $handle;

    // Create the x_creator_store node.
    try {
      $nodeStorage = $this->entityTypeManager->getStorage('node');

      $nodeData = [
        'type' => 'x_creator_store',
        'title' => $displayName,
        'uid' => $this->currentUser->id(),
        'status' => 0,
        'field_x_handle' => $handle,
        'field_subscription_status' => 'pending',
        'field_x_bio' => $bio,
        'field_x_followers' => $profileData['followers'] ?? 0,
        'field_x_verified' => $profileData['verified'] ?? FALSE,
        'field_x_brand_color' => $brandColor,
        'field_x_tagline' => $tagline,
        'field_about' => $about,
      ];

      if ($avatarFile) {
        $nodeData['field_x_avatar'] = ['target_id' => $avatarFile->id()];
      }

      if ($bannerFile) {
        $nodeData['field_x_banner'] = ['target_id' => $bannerFile->id()];
      }

      $node = $nodeStorage->create($nodeData);
      $node->save();

      // Reload to get the commerce_store reference (set by hook_node_insert).
      $node = $nodeStorage->load($node->id());

    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create store for @handle: @message', [
        '@handle' => $handle,
        '@message' => $e->getMessage(),
      ]);
      throw new HttpException(500, 'Failed to create store. Please try again.');
    }

    // Build response data (same shape as StoreProfileResource).
    $avatarUrl = '';
    if ($node->hasField('field_x_avatar') && !$node->get('field_x_avatar')->isEmpty()) {
      $file = $node->get('field_x_avatar')->entity;
      if ($file) {
        $avatarUrl = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    $bannerUrl = '';
    if ($node->hasField('field_x_banner') && !$node->get('field_x_banner')->isEmpty()) {
      $file = $node->get('field_x_banner')->entity;
      if ($file) {
        $bannerUrl = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    $commerceStore = $this->storeManager->getCommerceStore($node);

    $data = [
      'nodeId' => (int) $node->id(),
      'uuid' => $node->uuid(),
      'handle' => $node->get('field_x_handle')->value,
      'bio' => $node->get('field_x_bio')->value ?? '',
      'avatarUrl' => $avatarUrl,
      'bannerUrl' => $bannerUrl,
      'logoUrl' => '',
      'about' => $node->get('field_about')->value ?? '',
      'followers' => (int) ($node->get('field_x_followers')->value ?? 0),
      'verified' => (bool) ($node->get('field_x_verified')->value ?? FALSE),
      'brandColor' => $node->get('field_x_brand_color')->value ?? '#000000',
      'tagline' => $node->get('field_x_tagline')->value ?? '',
      'commerceStoreId' => $commerceStore ? (int) $commerceStore->id() : NULL,
      'commerceStoreUuid' => $commerceStore ? $commerceStore->uuid() : NULL,
      'subscriptionStatus' => $node->get('field_subscription_status')->value ?? 'pending',
    ];

    return new ModifiedResourceResponse($data, 201);
  }

}

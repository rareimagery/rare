<?php

namespace Drupal\rareimagery_xstore\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Scrapes public X (Twitter) profile pages for store creation.
 */
class XProfileScraperService {

  /**
   * Cache TTL for scraped profile data (5 minutes).
   */
  protected const CACHE_TTL = 300;

  /**
   * Constructs an XProfileScraperService.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected CacheBackendInterface $cache,
    protected FileSystemInterface $fileSystem,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Validates that a handle matches X's syntax rules.
   *
   * @param string $handle
   *   The X handle to validate (without @ prefix).
   *
   * @return bool
   *   TRUE if the handle is syntactically valid.
   */
  public function validateHandle(string $handle): bool {
    $handle = ltrim($handle, '@');
    return (bool) preg_match('/^[a-zA-Z0-9_]{1,15}$/', $handle);
  }

  /**
   * Scrapes an X profile and extracts profile data via fxtwitter API.
   *
   * Uses the public fxtwitter API (FixTweet) as a reliable proxy for X
   * profile data, since X.com itself is a client-side SPA that doesn't
   * expose profile metadata in server-rendered HTML.
   *
   * @param string $handle
   *   The X handle (without @ prefix).
   *
   * @return array
   *   Associative array with keys: handle, display_name, bio, avatar_url,
   *   banner_url, followers, verified, error. The 'error' key is NULL on
   *   success, or a string code on failure (not_found, private, suspended,
   *   scrape_failed).
   */
  public function scrapeProfile(string $handle): array {
    $handle = ltrim($handle, '@');
    $logger = $this->loggerFactory->get('rareimagery_xstore');

    // Check cache first.
    $cid = 'x_profile:' . strtolower($handle);
    $cached = $this->cache->get($cid);
    if ($cached) {
      return $cached->data;
    }

    $result = [
      'handle' => $handle,
      'display_name' => '',
      'bio' => '',
      'avatar_url' => '',
      'banner_url' => '',
      'followers' => 0,
      'verified' => FALSE,
      'error' => NULL,
    ];

    try {
      $response = $this->httpClient->request('GET', 'https://api.fxtwitter.com/' . $handle, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (compatible; RareImagery/1.0)',
          'Accept' => 'application/json',
        ],
        'timeout' => 10,
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      $body = (string) $response->getBody();
      $data = json_decode($body, TRUE);

      if ($statusCode === 404 || (isset($data['code']) && $data['code'] === 404)) {
        $result['error'] = 'not_found';
        $this->cache->set($cid, $result, time() + 60);
        return $result;
      }

      if ($statusCode === 403) {
        $result['error'] = 'suspended';
        $this->cache->set($cid, $result, time() + 60);
        return $result;
      }

      if ($statusCode !== 200 || !$data) {
        $result['error'] = 'scrape_failed';
        $logger->warning('fxtwitter API returned HTTP @code for @handle', [
          '@code' => $statusCode,
          '@handle' => $handle,
        ]);
        $this->cache->set($cid, $result, time() + 60);
        return $result;
      }

      // The fxtwitter API nests user data under the 'user' key
      // when hitting the profile endpoint (/{handle}).
      $user = $data['user'] ?? $data;

      if (empty($user['name']) && empty($user['screen_name'])) {
        $result['error'] = 'not_found';
        $this->cache->set($cid, $result, time() + 60);
        return $result;
      }

      $result['handle'] = $user['screen_name'] ?? $handle;
      $result['display_name'] = $user['name'] ?? '';
      $result['bio'] = $user['description'] ?? '';
      $result['followers'] = (int) ($user['followers'] ?? 0);

      // Avatar URL — upgrade to high-res by removing _normal suffix.
      $avatarUrl = $user['avatar_url'] ?? '';
      if ($avatarUrl && str_contains($avatarUrl, 'pbs.twimg.com')) {
        $avatarUrl = preg_replace(
          '/_(?:normal|bigger|200x200|mini|[0-9]+x[0-9]+)\./',
          '_400x400.',
          $avatarUrl
        );
      }
      $result['avatar_url'] = $avatarUrl;

      // Banner URL — append high-res dimensions.
      $bannerUrl = $user['banner_url'] ?? '';
      if ($bannerUrl && !str_contains($bannerUrl, '/1500x500')) {
        $bannerUrl .= '/1500x500';
      }
      $result['banner_url'] = $bannerUrl;

      // Verified status — fxtwitter may return this in various fields.
      $result['verified'] = !empty($user['verified']) || !empty($user['is_blue_verified']);

    }
    catch (GuzzleException $e) {
      $result['error'] = 'scrape_failed';
      $logger->error('Failed to fetch X profile @handle via fxtwitter: @message', [
        '@handle' => $handle,
        '@message' => $e->getMessage(),
      ]);
    }

    // Cache the result.
    $ttl = $result['error'] ? 60 : static::CACHE_TTL;
    $this->cache->set($cid, $result, time() + $ttl);

    return $result;
  }

  /**
   * Downloads an image from a URL and saves it as a Drupal file entity.
   *
   * @param string $url
   *   The image URL to download.
   * @param string $directory
   *   The destination directory URI (e.g., 'public://x-stores/avatars').
   * @param string $filename
   *   The filename to save as (e.g., 'handle.jpg').
   *
   * @return \Drupal\file\FileInterface|null
   *   The saved file entity, or NULL on failure.
   */
  public function downloadImage(string $url, string $directory, string $filename): ?\Drupal\file\FileInterface {
    $logger = $this->loggerFactory->get('rareimagery_xstore');

    if (empty($url)) {
      return NULL;
    }

    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (compatible; RareImagery/1.0)',
        ],
        'timeout' => 15,
        'http_errors' => FALSE,
      ]);

      if ($response->getStatusCode() !== 200) {
        $logger->warning('Failed to download image from @url: HTTP @code', [
          '@url' => $url,
          '@code' => $response->getStatusCode(),
        ]);
        return NULL;
      }

      $contentType = $response->getHeaderLine('Content-Type');
      if (!str_starts_with($contentType, 'image/')) {
        $logger->warning('Downloaded content from @url is not an image: @type', [
          '@url' => $url,
          '@type' => $contentType,
        ]);
        return NULL;
      }

      $data = (string) $response->getBody();
      if (empty($data)) {
        return NULL;
      }

      // Determine file extension from content type.
      $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
      ];
      $ext = $extensions[$contentType] ?? 'jpg';
      $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $ext;

      // Prepare directory.
      $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      // Save file data.
      $destination = $directory . '/' . $filename;
      $uri = $this->fileSystem->saveData($data, $destination, FileSystemInterface::EXISTS_REPLACE);

      if (!$uri) {
        $logger->error('Failed to save image data to @destination', [
          '@destination' => $destination,
        ]);
        return NULL;
      }

      // Create file entity.
      /** @var \Drupal\file\FileInterface $file */
      $file = $this->entityTypeManager->getStorage('file')->create([
        'uri' => $uri,
        'filename' => $filename,
        'filemime' => $contentType,
        'status' => 1,
      ]);
      $file->save();

      return $file;

    }
    catch (GuzzleException $e) {
      $logger->error('Error downloading image from @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Parses a follower count string like "1.5M" or "12,345" into an integer.
   *
   * @param string $count
   *   The follower count string.
   *
   * @return int
   *   The parsed integer count.
   */
  protected function parseFollowerCount(string $count): int {
    $count = str_replace(',', '', $count);
    $multiplier = 1;

    if (str_ends_with(strtoupper($count), 'K')) {
      $multiplier = 1000;
      $count = substr($count, 0, -1);
    }
    elseif (str_ends_with(strtoupper($count), 'M')) {
      $multiplier = 1000000;
      $count = substr($count, 0, -1);
    }
    elseif (str_ends_with(strtoupper($count), 'B')) {
      $multiplier = 1000000000;
      $count = substr($count, 0, -1);
    }

    return (int) round((float) $count * $multiplier);
  }

}

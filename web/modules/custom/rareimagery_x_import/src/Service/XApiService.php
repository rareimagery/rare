<?php

namespace Drupal\rareimagery_x_import\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for fetching X (Twitter) profile data via the X API v2.
 */
class XApiService {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected $logger;

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('rareimagery_x_import');
  }

  /**
   * Get the Bearer token for X API authentication.
   */
  protected function getBearerToken(): string {
    $config = $this->configFactory->get('rareimagery_x_import.settings');
    $token = $config->get('x_api_bearer_token');
    if (empty($token)) {
      // Try direct Bearer Token env var first (from X Developer Portal).
      $token = getenv('X_BEARER_TOKEN') ?: '';
    }
    if (empty($token)) {
      // Fall back to OAuth2 client credentials flow.
      $key = getenv('X_CONSUMER_KEY');
      $secret = getenv('X_CONSUMER_SECRET');
      if ($key && $secret) {
        $token = $this->requestBearerToken($key, $secret);
      }
    }
    return $token ?: '';
  }

  /**
   * Request a Bearer token using OAuth2 client credentials.
   */
  protected function requestBearerToken(string $key, string $secret): string {
    try {
      $credentials = base64_encode(urlencode($key) . ':' . urlencode($secret));
      $response = $this->httpClient->request('POST', 'https://api.x.com/oauth2/token', [
        'headers' => [
          'Authorization' => 'Basic ' . $credentials,
          'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
        ],
        'body' => 'grant_type=client_credentials',
        'timeout' => 15,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      if (!empty($data['access_token'])) {
        // Cache the token in config.
        $this->configFactory->getEditable('rareimagery_x_import.settings')
          ->set('x_api_bearer_token', $data['access_token'])
          ->save();
        return $data['access_token'];
      }
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to get Bearer token: @error', ['@error' => $e->getMessage()]);
    }
    return '';
  }

  /**
   * Fetch X user profile by username.
   *
   * Tries X API v2 first, falls back to fxtwitter public API.
   */
  public function getUserProfile(string $username): ?array {
    $token = $this->getBearerToken();

    // Try X API v2 if we have a token.
    if (!empty($token)) {
      try {
        $response = $this->httpClient->request('GET', 'https://api.x.com/2/users/by/username/' . urlencode($username), [
          'headers' => [
            'Authorization' => 'Bearer ' . $token,
          ],
          'query' => [
            'user.fields' => 'name,description,profile_image_url,public_metrics,created_at',
          ],
          'timeout' => 15,
        ]);

        $data = json_decode($response->getBody()->getContents(), TRUE);
        if (!empty($data['data'])) {
          return $data['data'];
        }

        $this->logger->warning('No data returned from X API v2 for @username', ['@username' => $username]);
      }
      catch (RequestException $e) {
        $this->logger->warning('X API v2 failed for @username, trying fxtwitter fallback: @error', [
          '@username' => $username,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // Fallback: use fxtwitter public API (no auth required).
    return $this->getUserProfileViaFxTwitter($username);
  }

  /**
   * Fetches profile data via the public fxtwitter API as a fallback.
   *
   * Returns data in the same shape as X API v2 for compatibility.
   */
  protected function getUserProfileViaFxTwitter(string $username): ?array {
    try {
      $response = $this->httpClient->request('GET', 'https://api.fxtwitter.com/' . urlencode($username), [
        'headers' => [
          'User-Agent' => 'Mozilla/5.0 (compatible; RareImagery/1.0)',
          'Accept' => 'application/json',
        ],
        'timeout' => 10,
        'http_errors' => FALSE,
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode !== 200) {
        $this->logger->error('fxtwitter returned HTTP @code for @username', [
          '@code' => $statusCode,
          '@username' => $username,
        ]);
        return NULL;
      }

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $user = $data['user'] ?? $data;

      if (empty($user['name']) && empty($user['screen_name'])) {
        return NULL;
      }

      // Map fxtwitter response to X API v2 shape.
      return [
        'id' => $user['id'] ?? $username,
        'name' => $user['name'] ?? $username,
        'username' => $user['screen_name'] ?? $username,
        'description' => $user['description'] ?? '',
        'profile_image_url' => $user['avatar_url'] ?? '',
        'public_metrics' => [
          'followers_count' => (int) ($user['followers'] ?? 0),
          'following_count' => (int) ($user['following'] ?? 0),
          'tweet_count' => (int) ($user['tweets'] ?? 0),
        ],
      ];
    }
    catch (RequestException $e) {
      $this->logger->error('fxtwitter fallback failed for @username: @error', [
        '@username' => $username,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Fetch recent tweets for a user by their X user ID.
   */
  public function getUserTweets(string $user_id, int $max_results = 10): array {
    $token = $this->getBearerToken();
    if (empty($token)) {
      return [];
    }

    try {
      $response = $this->httpClient->request('GET', 'https://api.x.com/2/users/' . $user_id . '/tweets', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
        ],
        'query' => [
          'max_results' => min($max_results, 100),
          'tweet.fields' => 'public_metrics,created_at,text',
        ],
        'timeout' => 15,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['data'] ?? [];
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to fetch tweets for user @id: @error', [
        '@id' => $user_id,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Fetch followers for a user by their X user ID.
   */
  public function getUserFollowers(string $user_id, int $max_results = 8): array {
    $token = $this->getBearerToken();
    if (empty($token)) {
      return [];
    }

    try {
      $response = $this->httpClient->request('GET', 'https://api.x.com/2/users/' . $user_id . '/followers', [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
        ],
        'query' => [
          'max_results' => min($max_results, 100),
          'user.fields' => 'name,username,profile_image_url,public_metrics',
        ],
        'timeout' => 15,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['data'] ?? [];
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to fetch followers for user @id: @error', [
        '@id' => $user_id,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

}

<?php

namespace Drupal\rareimagery_x_import\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for interacting with Grok (xAI) API.
 */
class GrokService {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected $logger;

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('rareimagery_x_import');
  }

  /**
   * Get the xAI API key.
   */
  protected function getApiKey(): string {
    $config = $this->configFactory->get('rareimagery_x_import.settings');
    $key = $config->get('xai_api_key');
    if (empty($key)) {
      $key = getenv('XAI_API_KEY') ?: '';
    }
    return $key;
  }

  /**
   * Analyze tweets and generate engagement metrics using Grok.
   */
  public function analyzeProfile(array $profile, array $tweets): ?array {
    $api_key = $this->getApiKey();
    if (empty($api_key)) {
      $this->logger->error('No xAI API key configured.');
      return NULL;
    }

    $tweets_text = '';
    foreach (array_slice($tweets, 0, 10) as $i => $tweet) {
      $likes = $tweet['public_metrics']['like_count'] ?? 0;
      $retweets = $tweet['public_metrics']['retweet_count'] ?? 0;
      $replies = $tweet['public_metrics']['reply_count'] ?? 0;
      $tweets_text .= ($i + 1) . ". \"{$tweet['text']}\" (Likes: {$likes}, Retweets: {$retweets}, Replies: {$replies})\n";
    }

    $followers = $profile['public_metrics']['followers_count'] ?? 'unknown';
    $following = $profile['public_metrics']['following_count'] ?? 'unknown';
    $name = $profile['name'] ?? '';
    $bio = $profile['description'] ?? '';

    $prompt = <<<PROMPT
Analyze this X (Twitter) creator's profile for an e-commerce marketplace. Return a JSON object only, no markdown.

Creator: {$name}
Bio: {$bio}
Followers: {$followers}
Following: {$following}

Recent tweets:
{$tweets_text}

Return this exact JSON structure:
{
  "top_posts": [array of up to 8 best tweets by engagement, each with "text", "likes", "retweets", "replies", "score"],
  "engagement_score": number 0-100,
  "audience_quality": "high" | "medium" | "low",
  "content_themes": [array of 3-5 main content themes],
  "recommended_products": [array of 3-5 product types this creator could sell],
  "summary": "1-2 sentence profile summary for store bio"
}
PROMPT;

    try {
      $response = $this->httpClient->request('POST', 'https://api.x.ai/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'grok-3-mini',
          'messages' => [
            ['role' => 'system', 'content' => 'You are a data analyst. Always respond with valid JSON only, no markdown formatting.'],
            ['role' => 'user', 'content' => $prompt],
          ],
          'temperature' => 0.3,
        ],
        'timeout' => 60,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $content = $data['choices'][0]['message']['content'] ?? '';

      // Strip any markdown code fences if present.
      $content = preg_replace('/^```json\s*/', '', $content);
      $content = preg_replace('/\s*```$/', '', $content);

      $result = json_decode($content, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->logger->error('Grok returned invalid JSON: @content', ['@content' => $content]);
        return NULL;
      }

      return $result;
    }
    catch (RequestException $e) {
      $this->logger->error('Grok API error: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

}

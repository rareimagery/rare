<?php

namespace Drupal\ai_provider\Provider;

use Drupal\ai_provider\Model\AiResponse;

/**
 * Contract for AI provider implementations.
 *
 * Every provider (Grok, Claude, future additions) implements this.
 * The AiProviderManager only interacts through this interface.
 */
interface AiProviderInterface {

  /**
   * Unique provider key.
   *
   * @return string
   *   e.g., 'grok', 'claude'.
   */
  public function getKey(): string;

  /**
   * Whether this provider is configured and reachable.
   *
   * Checks that the API key is set. Does NOT make a network call.
   *
   * @return bool
   */
  public function isAvailable(): bool;

  /**
   * Execute an AI generation request.
   *
   * @param string $task_type
   *   One of: 'theme_generation', 'page_builder', 'profile_import',
   *   'content_generation'.
   * @param array $prompt_data
   *   Task-specific payload. Always contains:
   *     - 'user_input' (string): The creator's prompt/request.
   *   May also contain:
   *     - 'subculture' (string): Active theme subculture key.
   *     - 'existing_theme' (string): Current theme JSON config.
   *     - 'profile_data' (array): X profile data (Grok only).
   * @param array $options
   *   Optional overrides:
   *     - 'temperature' (float): Default 1.
   *     - 'max_tokens' (int): Default provider-specific.
   *
   * @return \Drupal\ai_provider\Model\AiResponse
   *
   * @throws \Drupal\ai_provider\Exception\AiGenerationException
   */
  public function generate(string $task_type, array $prompt_data, array $options = []): AiResponse;

}

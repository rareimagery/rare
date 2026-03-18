<?php

namespace Drupal\ai_provider;

use Drupal\ai_provider\Model\AiResponse;
use Drupal\ai_provider\Provider\AiProviderInterface;
use Drupal\ai_provider\Exception\AiGenerationException;
use Drupal\ai_provider\Exception\ProviderUnavailableException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Routes AI requests to the correct provider with automatic failover.
 *
 * Routing priority:
 *   1. Per-task env var (e.g., AI_PROVIDER_PAGE_BUILDER_PRIMARY=claude)
 *   2. Hardcoded routing map
 *   3. Global default env vars
 */
class AiProviderManager {

  /**
   * Registered providers keyed by provider key.
   *
   * @var array<string, \Drupal\ai_provider\Provider\AiProviderInterface>
   */
  private array $providers = [];

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  public function __construct(
    AiProviderInterface $grok,
    AiProviderInterface $claude,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->providers[$grok->getKey()] = $grok;
    $this->providers[$claude->getKey()] = $claude;
    $this->logger = $loggerFactory->get('ai_provider');
  }

  /**
   * Dispatch a request with automatic failover.
   *
   * @param string $task_type
   *   Task identifier: 'page_builder', 'theme_generation', etc.
   * @param array $prompt_data
   *   Task-specific data passed to the provider.
   * @param array $options
   *   Optional overrides (temperature, max_tokens).
   *
   * @return \Drupal\ai_provider\Model\AiResponse
   *
   * @throws \Drupal\ai_provider\Exception\AiGenerationException
   *   When all providers fail.
   */
  public function dispatch(string $task_type, array $prompt_data, array $options = []): AiResponse {
    $route = $this->resolveRoute($task_type);

    $primary_key = $route['primary'];
    $fallback_key = $route['fallback'];

    // --- Attempt primary provider ---
    if (isset($this->providers[$primary_key])) {
      try {
        $primary = $this->providers[$primary_key];

        if (!$primary->isAvailable()) {
          throw new ProviderUnavailableException($primary_key);
        }

        $this->logger->info('AI dispatch: @task -> @provider (primary)', [
          '@task' => $task_type,
          '@provider' => $primary_key,
        ]);

        return $primary->generate($task_type, $prompt_data, $options);
      }
      catch (\Throwable $e) {
        $this->logger->warning('AI failover: @task — @primary failed (@error), trying @fallback', [
          '@task' => $task_type,
          '@primary' => $primary_key,
          '@error' => $e->getMessage(),
          '@fallback' => $fallback_key ?? 'none',
        ]);
      }
    }

    // --- Attempt fallback provider ---
    if ($fallback_key && isset($this->providers[$fallback_key])) {
      try {
        $fallback = $this->providers[$fallback_key];

        if (!$fallback->isAvailable()) {
          throw new ProviderUnavailableException($fallback_key);
        }

        $this->logger->info('AI dispatch: @task -> @provider (fallback)', [
          '@task' => $task_type,
          '@provider' => $fallback_key,
        ]);

        return $fallback->generate($task_type, $prompt_data, $options);
      }
      catch (\Throwable $e) {
        $this->logger->error('AI fallback also failed: @task — @fallback (@error)', [
          '@task' => $task_type,
          '@fallback' => $fallback_key,
          '@error' => $e->getMessage(),
        ]);
      }
    }

    // --- Both failed ---
    throw new AiGenerationException(
      "All providers failed for task '{$task_type}'. Check logs for details."
    );
  }

  /**
   * Resolve primary/fallback providers for a task type.
   *
   * @param string $task_type
   *   The task being dispatched.
   *
   * @return array{primary: string, fallback: ?string}
   */
  private function resolveRoute(string $task_type): array {
    // Check for per-task env var override.
    // e.g., AI_PROVIDER_PAGE_BUILDER_PRIMARY=claude
    $env_key = 'AI_PROVIDER_' . strtoupper($task_type) . '_PRIMARY';
    $env_primary = getenv($env_key) ?: NULL;

    // Hardcoded defaults.
    $map = [
      'theme_generation'   => ['primary' => 'grok', 'fallback' => 'claude'],
      'page_builder'       => ['primary' => 'grok', 'fallback' => 'claude'],
      'profile_import'     => ['primary' => 'grok', 'fallback' => NULL],
      'content_generation' => ['primary' => 'grok', 'fallback' => 'claude'],
    ];

    $route = $map[$task_type] ?? [
      'primary' => getenv('AI_PROVIDER_DEFAULT_PRIMARY') ?: 'grok',
      'fallback' => getenv('AI_PROVIDER_DEFAULT_FALLBACK') ?: 'claude',
    ];

    // Apply per-task env override if set.
    if ($env_primary && isset($this->providers[$env_primary])) {
      $route['primary'] = $env_primary;
      // Auto-flip fallback to the other provider.
      $route['fallback'] = ($env_primary === 'claude') ? 'grok' : 'claude';
    }

    return $route;
  }

}

<?php

namespace Drupal\rareimagery_ai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * AI Agent that routes requests to the configured provider.
 */
class AiAgent {

  protected ClaudeClient $claudeClient;
  protected XaiClient $xaiClient;
  protected ToolRegistry $toolRegistry;
  protected ConfigFactoryInterface $configFactory;
  protected $logger;

  public function __construct(
    ClaudeClient $claude_client,
    XaiClient $xai_client,
    ToolRegistry $tool_registry,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->claudeClient = $claude_client;
    $this->xaiClient = $xai_client;
    $this->toolRegistry = $tool_registry;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('rareimagery_ai');
  }

  /**
   * Send a chat message to the configured AI provider.
   */
  public function chat(string $message, string $provider = '', array $conversation = []): array {
    if (empty($provider)) {
      $provider = $this->configFactory->get('rareimagery_ai.settings')->get('default_provider') ?: 'claude';
    }

    $this->logger->info('AI chat (@provider): @msg', [
      '@provider' => $provider,
      '@msg' => mb_substr($message, 0, 200),
    ]);

    return match ($provider) {
      'claude' => $this->claudeClient->chat($message, $conversation),
      'xai', 'grok' => $this->xaiClient->chat($message, $conversation),
      default => ['error' => "Unknown provider: $provider"],
    };
  }

  /**
   * Execute a tool directly (without AI).
   */
  public function executeTool(string $tool_name, array $input): array {
    return $this->toolRegistry->execute($tool_name, $input);
  }

  /**
   * Get available tools.
   */
  public function getTools(): array {
    return $this->toolRegistry->getToolDefinitions();
  }

}

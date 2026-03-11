<?php

namespace Drupal\rareimagery_ai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;

/**
 * Claude API client with tool-use support.
 */
class ClaudeClient {

  protected ConfigFactoryInterface $configFactory;
  protected ToolRegistry $toolRegistry;
  protected $logger;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    ToolRegistry $tool_registry,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->configFactory = $config_factory;
    $this->toolRegistry = $tool_registry;
    $this->logger = $logger_factory->get('rareimagery_ai');
  }

  /**
   * Send a message to Claude with tool-use and execute tool calls in a loop.
   */
  public function chat(string $user_message, array $conversation = []): array {
    $config = $this->configFactory->get('rareimagery_ai.settings');
    $api_key = $config->get('claude.api_key');

    if (empty($api_key)) {
      return ['error' => 'Claude API key is not configured.'];
    }

    $model = $config->get('claude.model') ?: 'claude-sonnet-4-6';
    $system_prompt = $config->get('system_prompt') ?: '';

    $tools = array_map(function ($tool) {
      return [
        'name' => $tool['name'],
        'description' => $tool['description'],
        'input_schema' => $tool['input_schema'],
      ];
    }, $this->toolRegistry->getToolDefinitions());

    $messages = $conversation;
    $messages[] = ['role' => 'user', 'content' => $user_message];

    $client = new Client();
    $max_iterations = 10;
    $tool_results = [];

    for ($i = 0; $i < $max_iterations; $i++) {
      $payload = [
        'model' => $model,
        'max_tokens' => 4096,
        'system' => $system_prompt,
        'tools' => $tools,
        'messages' => $messages,
      ];

      $response = $client->post('https://api.anthropic.com/v1/messages', [
        'headers' => [
          'x-api-key' => $api_key,
          'anthropic-version' => '2023-06-01',
          'content-type' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 120,
      ]);

      $result = json_decode($response->getBody()->getContents(), TRUE);
      $stop_reason = $result['stop_reason'] ?? 'end_turn';

      // Add assistant message to conversation.
      $messages[] = ['role' => 'assistant', 'content' => $result['content']];

      if ($stop_reason !== 'tool_use') {
        // Extract text response.
        $text = '';
        foreach ($result['content'] as $block) {
          if ($block['type'] === 'text') {
            $text .= $block['text'];
          }
        }
        return [
          'response' => $text,
          'tool_results' => $tool_results,
          'messages' => $messages,
          'provider' => 'claude',
          'model' => $model,
        ];
      }

      // Process tool calls.
      $tool_use_results = [];
      foreach ($result['content'] as $block) {
        if ($block['type'] === 'tool_use') {
          $tool_output = $this->toolRegistry->execute($block['name'], $block['input'] ?? []);
          $tool_results[] = [
            'tool' => $block['name'],
            'input' => $block['input'],
            'output' => $tool_output,
          ];
          $tool_use_results[] = [
            'type' => 'tool_result',
            'tool_use_id' => $block['id'],
            'content' => json_encode($tool_output),
          ];
        }
      }

      $messages[] = ['role' => 'user', 'content' => $tool_use_results];
    }

    return [
      'response' => 'Max tool iterations reached.',
      'tool_results' => $tool_results,
      'messages' => $messages,
      'provider' => 'claude',
      'model' => $model,
    ];
  }

}

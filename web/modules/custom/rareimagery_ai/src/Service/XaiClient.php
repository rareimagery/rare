<?php

namespace Drupal\rareimagery_ai\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Client;

/**
 * xAI/Grok API client with tool-use support (OpenAI-compatible).
 */
class XaiClient {

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
   * Send a message to xAI/Grok with tool-use and execute tool calls in a loop.
   */
  public function chat(string $user_message, array $conversation = []): array {
    $config = $this->configFactory->get('rareimagery_ai.settings');
    $xai_config = $this->configFactory->get('rareimagery_xstore.settings');
    $api_key = $config->get('xai.api_key') ?: $xai_config->get('xai.api_key');

    if (empty($api_key)) {
      return ['error' => 'xAI API key is not configured.'];
    }

    $model = $config->get('xai.model') ?: 'grok-3-fast';
    $system_prompt = $config->get('system_prompt') ?: '';

    // Convert tools to OpenAI function-calling format.
    $tools = array_map(function ($tool) {
      return [
        'type' => 'function',
        'function' => [
          'name' => $tool['name'],
          'description' => $tool['description'],
          'parameters' => $tool['input_schema'],
        ],
      ];
    }, $this->toolRegistry->getToolDefinitions());

    $messages = [];
    if ($system_prompt) {
      $messages[] = ['role' => 'system', 'content' => $system_prompt];
    }
    foreach ($conversation as $msg) {
      $messages[] = $msg;
    }
    $messages[] = ['role' => 'user', 'content' => $user_message];

    $client = new Client();
    $max_iterations = 10;
    $tool_results = [];

    for ($i = 0; $i < $max_iterations; $i++) {
      $payload = [
        'model' => $model,
        'messages' => $messages,
        'tools' => $tools,
        'tool_choice' => 'auto',
      ];

      $response = $client->post('https://api.x.ai/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 120,
      ]);

      $result = json_decode($response->getBody()->getContents(), TRUE);
      $choice = $result['choices'][0] ?? [];
      $assistant_message = $choice['message'] ?? [];
      $finish_reason = $choice['finish_reason'] ?? 'stop';

      $messages[] = $assistant_message;

      if ($finish_reason !== 'tool_calls' && empty($assistant_message['tool_calls'])) {
        return [
          'response' => $assistant_message['content'] ?? '',
          'tool_results' => $tool_results,
          'messages' => $messages,
          'provider' => 'xai',
          'model' => $model,
        ];
      }

      // Process tool calls.
      foreach ($assistant_message['tool_calls'] ?? [] as $tool_call) {
        $fn = $tool_call['function'] ?? [];
        $args = json_decode($fn['arguments'] ?? '{}', TRUE) ?: [];
        $tool_output = $this->toolRegistry->execute($fn['name'], $args);

        $tool_results[] = [
          'tool' => $fn['name'],
          'input' => $args,
          'output' => $tool_output,
        ];

        $messages[] = [
          'role' => 'tool',
          'tool_call_id' => $tool_call['id'],
          'content' => json_encode($tool_output),
        ];
      }
    }

    return [
      'response' => 'Max tool iterations reached.',
      'tool_results' => $tool_results,
      'messages' => $messages,
      'provider' => 'xai',
      'model' => $model,
    ];
  }

}

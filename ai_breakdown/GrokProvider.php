<?php

namespace Drupal\ai_provider\Provider;

use Drupal\ai_provider\Model\AiResponse;
use Drupal\ai_provider\Exception\AiGenerationException;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * xAI Grok provider — primary for all tasks.
 */
class GrokProvider implements AiProviderInterface {

  private const API_URL = 'https://api.x.ai/v1/chat/completions';

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  public function __construct(
    private ClientInterface $httpClient,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('ai_provider');
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(): string {
    return 'grok';
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable(): bool {
    return !empty($this->getApiKey());
  }

  /**
   * {@inheritdoc}
   */
  public function generate(string $task_type, array $prompt_data, array $options = []): AiResponse {
    $system_prompt = $this->buildSystemPrompt($task_type, $prompt_data);
    $user_message = $prompt_data['user_input'] ?? '';

    $payload = [
      'model' => getenv('GROK_MODEL') ?: 'grok-3',
      'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_message],
      ],
      'temperature' => $options['temperature'] ?? 1,
      'max_tokens' => $options['max_tokens'] ?? 4096,
    ];

    try {
      $response = $this->httpClient->request('POST', self::API_URL, [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->getApiKey(),
          'Content-Type' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 60,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $this->normalizeResponse($data, $task_type);
    }
    catch (AiGenerationException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      $this->logger->error('Grok generation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new AiGenerationException(
        'Grok generation failed: ' . $e->getMessage(), 0, $e
      );
    }
  }

  /**
   * Parse Grok's OpenAI-compatible response into AiResponse.
   */
  private function normalizeResponse(array $data, string $task_type): AiResponse {
    $text = $data['choices'][0]['message']['content'] ?? '';

    if (empty($text)) {
      throw new AiGenerationException('Grok returned empty response');
    }

    // For structured tasks, attempt JSON parse.
    $structured = NULL;
    if (in_array($task_type, ['page_builder', 'theme_generation'], TRUE)) {
      // Strip markdown fences if present.
      $clean = $text;
      $clean = preg_replace('/^```json\s*/i', '', $clean);
      $clean = preg_replace('/\s*```$/i', '', $clean);
      $clean = trim($clean);

      $decoded = json_decode($clean, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new AiGenerationException(
          'Grok returned invalid JSON for ' . $task_type . ': ' . json_last_error_msg()
        );
      }
      $structured = $decoded;
    }

    return new AiResponse(
      provider: 'grok',
      task_type: $task_type,
      raw_text: $text,
      structured_data: $structured,
      model: $data['model'] ?? '',
      usage: $data['usage'] ?? [],
    );
  }

  /**
   * Get API key from environment.
   */
  private function getApiKey(): string {
    return getenv('XAI_API_KEY') ?: '';
  }

  /**
   * Build system prompt per task type.
   *
   * TODO: Port your existing Grok system prompts into these methods.
   * The stubs below are placeholders.
   */
  private function buildSystemPrompt(string $task_type, array $prompt_data): string {
    return match ($task_type) {
      'page_builder' => $this->getPageBuilderPrompt($prompt_data),
      'theme_generation' => $this->getThemeGenerationPrompt($prompt_data),
      'profile_import' => $this->getProfileImportPrompt($prompt_data),
      'content_generation' => $this->getContentGenerationPrompt($prompt_data),
      default => 'You are a helpful assistant for RareImagery.',
    };
  }

  /**
   * TODO: Port existing page builder system prompt.
   */
  private function getPageBuilderPrompt(array $prompt_data): string {
    $subculture = $prompt_data['subculture'] ?? 'default';
    $existing_theme = $prompt_data['existing_theme'] ?? '{}';

    return <<<PROMPT
You are the RareImagery Page Builder AI. You generate JSON component configurations
that Next.js renders on creator storefronts.

RULES:
- Output ONLY valid JSON. No markdown fences. No explanation text.
- Every component must be mobile-responsive.
- Products must appear above the fold.
- No autoplay audio. Always include reduced-motion fallback.
- Respect the creator's active theme/subculture: {$subculture}
- Existing theme config for reference: {$existing_theme}

OUTPUT FORMAT:
{
  "components": [
    {
      "type": "hero|grid|banner|text|image|product_carousel|shoutout_wall",
      "props": {},
      "styles": {
        "desktop": {},
        "mobile": {}
      }
    }
  ],
  "metadata": {
    "generated_by": "grok",
    "subculture": "{$subculture}",
    "timestamp": "<ISO 8601>"
  }
}
PROMPT;
  }

  /**
   * TODO: Port from MYSPACE_THEME_BOT_RULES.md prompts.
   */
  private function getThemeGenerationPrompt(array $prompt_data): string {
    return 'You are the RareImagery MySpace Theme Bot. Generate a JSON theme configuration matching the exact schema used by the platform. Output ONLY valid JSON. No markdown fences. No explanation text.';
  }

  /**
   * TODO: Port existing X profile import prompt.
   */
  private function getProfileImportPrompt(array $prompt_data): string {
    return 'You are the RareImagery Profile Import assistant. Analyze the X profile data and extract structured information for the creator storefront.';
  }

  /**
   * TODO: Port if exists, or create new.
   */
  private function getContentGenerationPrompt(array $prompt_data): string {
    return 'You are a content assistant for RareImagery creator storefronts. Write engaging, on-brand copy for creators. Keep it concise and authentic.';
  }

}

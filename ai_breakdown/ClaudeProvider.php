<?php

namespace Drupal\ai_provider\Provider;

use Drupal\ai_provider\Model\AiResponse;
use Drupal\ai_provider\Exception\AiGenerationException;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Anthropic Claude provider — fallback, plus page_builder candidate.
 */
class ClaudeProvider implements AiProviderInterface {

  private const API_URL = 'https://api.anthropic.com/v1/messages';
  private const DEFAULT_MODEL = 'claude-sonnet-4-6';
  private const DEFAULT_MAX_TOKENS = 20000;
  private const API_VERSION = '2023-06-01';

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
    return 'claude';
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
      'model' => getenv('CLAUDE_MODEL') ?: self::DEFAULT_MODEL,
      'max_tokens' => $options['max_tokens'] ?? self::DEFAULT_MAX_TOKENS,
      'temperature' => $options['temperature'] ?? 1,
      'thinking' => ['type' => 'disabled'],
      'system' => $system_prompt,
      'messages' => [
        ['role' => 'user', 'content' => $user_message],
      ],
    ];

    try {
      $response = $this->httpClient->request('POST', self::API_URL, [
        'headers' => [
          'x-api-key' => $this->getApiKey(),
          'anthropic-version' => self::API_VERSION,
          'content-type' => 'application/json',
        ],
        'json' => $payload,
        'timeout' => 60,
      ]);

      $status = $response->getStatusCode();
      if ($status !== 200) {
        throw new AiGenerationException("Claude API returned HTTP {$status}");
      }

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $this->normalizeResponse($data, $task_type);
    }
    catch (AiGenerationException $e) {
      throw $e;
    }
    catch (\Throwable $e) {
      $this->logger->error('Claude generation failed: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new AiGenerationException(
        'Claude generation failed: ' . $e->getMessage(), 0, $e
      );
    }
  }

  /**
   * Extract text from Claude's content blocks and parse structured output.
   */
  private function normalizeResponse(array $data, string $task_type): AiResponse {
    // Claude returns an array of content blocks.
    // Extract all text blocks and concatenate.
    $text = '';
    foreach ($data['content'] ?? [] as $block) {
      if (($block['type'] ?? '') === 'text') {
        $text .= $block['text'];
      }
    }

    if (empty($text)) {
      throw new AiGenerationException('Claude returned empty response');
    }

    // For structured tasks, parse JSON.
    $structured = NULL;
    if (in_array($task_type, ['page_builder', 'theme_generation'], TRUE)) {
      // Strip markdown fences if Claude wraps them despite instructions.
      $clean = $text;
      $clean = preg_replace('/^```json\s*/i', '', $clean);
      $clean = preg_replace('/\s*```$/i', '', $clean);
      $clean = trim($clean);

      $decoded = json_decode($clean, TRUE);
      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->logger->warning('Claude returned invalid JSON for @task: @error', [
          '@task' => $task_type,
          '@error' => json_last_error_msg(),
        ]);
        throw new AiGenerationException(
          "Claude returned invalid JSON for {$task_type}: " . json_last_error_msg()
        );
      }
      $structured = $decoded;
    }

    return new AiResponse(
      provider: 'claude',
      task_type: $task_type,
      raw_text: $text,
      structured_data: $structured,
      model: $data['model'] ?? self::DEFAULT_MODEL,
      usage: $data['usage'] ?? [],
    );
  }

  /**
   * Get API key from environment.
   */
  private function getApiKey(): string {
    return getenv('ANTHROPIC_API_KEY') ?: '';
  }

  /**
   * Build system prompt per task type.
   */
  private function buildSystemPrompt(string $task_type, array $prompt_data): string {
    return match ($task_type) {
      'page_builder' => $this->getPageBuilderPrompt($prompt_data),
      'theme_generation' => $this->getThemeGenerationPrompt($prompt_data),
      'content_generation' => $this->getContentGenerationPrompt($prompt_data),
      default => 'You are a helpful assistant for RareImagery, a creator platform.',
    };
  }

  /**
   * Page builder system prompt.
   *
   * Must produce the same JSON schema as GrokProvider so the frontend
   * doesn't care which model generated the output.
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
    "generated_by": "claude",
    "subculture": "{$subculture}",
    "timestamp": "<ISO 8601>"
  }
}
PROMPT;
  }

  /**
   * Theme generation — fallback only.
   *
   * Output must match the JSON schema from MYSPACE_THEME_BOT_RULES.md.
   */
  private function getThemeGenerationPrompt(array $prompt_data): string {
    return 'You are the RareImagery MySpace Theme Bot. Generate a JSON theme configuration matching the exact schema used by the platform. Output ONLY valid JSON. No markdown fences. No explanation text.';
  }

  /**
   * Content/copy generation for storefronts.
   */
  private function getContentGenerationPrompt(array $prompt_data): string {
    return 'You are a content assistant for RareImagery creator storefronts. Write engaging, on-brand copy for creators. Keep it concise and authentic. Match the creator\'s voice and subculture aesthetic.';
  }

}

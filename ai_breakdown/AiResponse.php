<?php

namespace Drupal\ai_provider\Model;

/**
 * Normalized AI response.
 *
 * Returned by every provider regardless of their native response format.
 * The controller serializes this to JSON for the Next.js frontend.
 */
class AiResponse {

  public function __construct(
    public readonly string $provider,
    public readonly string $task_type,
    public readonly string $raw_text,
    public readonly ?array $structured_data = NULL,
    public readonly string $model = '',
    public readonly array $usage = [],
  ) {}

  /**
   * Serialize for JSON:API response.
   *
   * @return array
   */
  public function toArray(): array {
    return [
      'provider' => $this->provider,
      'task_type' => $this->task_type,
      'data' => $this->structured_data ?? $this->raw_text,
      'meta' => [
        'model' => $this->model,
        'usage' => $this->usage,
      ],
    ];
  }

}

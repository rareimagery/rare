<?php

namespace Drupal\ai_provider\Exception;

/**
 * Thrown when a provider is not configured or reachable.
 */
class ProviderUnavailableException extends \RuntimeException {

  public function __construct(string $provider_key, int $code = 0, ?\Throwable $previous = NULL) {
    parent::__construct("AI provider '{$provider_key}' is unavailable.", $code, $previous);
  }

}

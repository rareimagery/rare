<?php

namespace Drupal\ai_provider\Controller;

use Drupal\ai_provider\AiProviderManager;
use Drupal\ai_provider\Exception\AiGenerationException;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles AI generation requests from the Next.js frontend.
 *
 * POST /jsonapi/ai-provider/generate
 *
 * Expected JSON body:
 * {
 *   "task_type": "page_builder",
 *   "prompt_data": {
 *     "user_input": "Create a hero section with my latest products",
 *     "subculture": "emo",
 *     "existing_theme": "{ ... }"
 *   },
 *   "options": {
 *     "temperature": 1,
 *     "max_tokens": 20000
 *   }
 * }
 */
class AiGenerateController extends ControllerBase {

  public function __construct(
    private AiProviderManager $aiManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ai_provider.manager'),
    );
  }

  /**
   * Handle the generation request.
   */
  public function generate(Request $request): JsonResponse {
    // --- Parse request body ---
    $body = json_decode($request->getContent(), TRUE);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonResponse([
        'error' => 'Invalid JSON in request body.',
      ], 400);
    }

    $task_type = $body['task_type'] ?? NULL;
    $prompt_data = $body['prompt_data'] ?? [];
    $options = $body['options'] ?? [];

    // --- Validate task type ---
    $allowed_tasks = [
      'page_builder',
      'theme_generation',
      'profile_import',
      'content_generation',
    ];

    if (!$task_type || !in_array($task_type, $allowed_tasks, TRUE)) {
      return new JsonResponse([
        'error' => 'Invalid or missing task_type.',
        'allowed' => $allowed_tasks,
      ], 400);
    }

    // --- Validate prompt data ---
    if (empty($prompt_data['user_input'])) {
      return new JsonResponse([
        'error' => 'prompt_data.user_input is required.',
      ], 400);
    }

    // --- Dispatch to provider ---
    try {
      $response = $this->aiManager->dispatch($task_type, $prompt_data, $options);

      return new JsonResponse([
        'status' => 'ok',
        'result' => $response->toArray(),
      ]);
    }
    catch (AiGenerationException $e) {
      $this->getLogger('ai_provider')->error('Generation failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'AI generation failed. Please try again.',
        'detail' => $e->getMessage(),
      ], 502);
    }
    catch (\Throwable $e) {
      $this->getLogger('ai_provider')->error('Unexpected error: @msg', [
        '@msg' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Internal server error.',
      ], 500);
    }
  }

}

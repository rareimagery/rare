<?php

namespace Drupal\rareimagery_ai\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\rareimagery_ai\Service\AiAgent;

/**
 * Controller for the AI Admin chat interface and API.
 */
class AiChatController extends ControllerBase {

  protected AiAgent $agent;

  public function __construct(AiAgent $agent) {
    $this->agent = $agent;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rareimagery_ai.agent'),
    );
  }

  /**
   * Render the AI Admin chat page.
   */
  public function page() {
    $tools = $this->agent->getTools();
    $tool_names = array_map(fn($t) => $t['name'], $tools);

    return [
      '#type' => 'markup',
      '#markup' => '',
      '#attached' => [
        'library' => ['rareimagery_ai/chat'],
        'drupalSettings' => [
          'rareimagery_ai' => [
            'tools' => $tool_names,
            'endpoint' => '/api/ai/chat',
            'tool_endpoint' => '/api/ai/tool',
          ],
        ],
      ],
      '#prefix' => '<div id="ai-admin-app">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * API: Chat with the AI agent.
   */
  public function chat(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['message'])) {
      return new JsonResponse(['error' => 'Message is required.'], 400);
    }

    $provider = $data['provider'] ?? '';
    $conversation = $data['conversation'] ?? [];

    $result = $this->agent->chat($data['message'], $provider, $conversation);

    return new JsonResponse($result);
  }

  /**
   * API: Execute a specific tool directly.
   */
  public function executeTool(Request $request): JsonResponse {
    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['tool'])) {
      return new JsonResponse(['error' => 'Tool name is required.'], 400);
    }

    $result = $this->agent->executeTool($data['tool'], $data['input'] ?? []);

    return new JsonResponse($result);
  }

}

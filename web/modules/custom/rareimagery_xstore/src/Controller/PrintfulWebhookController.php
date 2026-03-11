<?php

namespace Drupal\rareimagery_xstore\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\rareimagery_xstore\Service\PrintfulSyncService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles incoming Printful webhook events.
 */
class PrintfulWebhookController extends ControllerBase {

  /**
   * The Printful sync service.
   */
  protected PrintfulSyncService $printfulSync;

  /**
   * Constructs a PrintfulWebhookController.
   */
  public function __construct(PrintfulSyncService $printful_sync) {
    $this->printfulSync = $printful_sync;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('rareimagery_xstore.printful_sync'),
    );
  }

  /**
   * Processes a Printful webhook request.
   */
  public function handle(Request $request): JsonResponse {
    $payload = json_decode($request->getContent(), TRUE);

    if (!$payload || empty($payload['type'])) {
      return new JsonResponse(['error' => 'Invalid payload'], 400);
    }

    $this->printfulSync->processWebhook($payload['type'], $payload['data'] ?? []);

    return new JsonResponse(['status' => 'ok']);
  }

}

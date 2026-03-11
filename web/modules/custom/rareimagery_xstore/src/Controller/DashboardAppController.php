<?php

namespace Drupal\rareimagery_xstore\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Serves the React dashboard app shell.
 */
class DashboardAppController extends ControllerBase {

  /**
   * Returns the dashboard React app mount point.
   */
  public function view(): array {
    return [
      '#theme' => 'dashboard_app',
      '#attached' => [
        'library' => ['rareimagery/dashboard-app'],
        'drupalSettings' => [
          'rareimagery_dashboard' => [
            'csrf_token_url' => '/session/token',
            'stripe_publishable_key' => getenv('STRIPE_PUBLISHABLE_KEY') ?: '',
          ],
        ],
      ],
    ];
  }

}

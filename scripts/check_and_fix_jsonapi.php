<?php
/**
 * Check JSON:API config and update all profiles to xai3.
 */
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// Boot Drupal
$autoloader = require_once '/opt/drupal/web/autoload.php';
chdir('/opt/drupal/web');
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);

// Check JSON:API read_only config
$config = \Drupal::config('jsonapi.settings');
$read_only = $config->get('read_only');
echo "jsonapi.settings.read_only = " . var_export($read_only, true) . "\n";

// If still read_only, try setting it
if ($read_only) {
  echo "Attempting to set read_only to FALSE...\n";
  \Drupal::configFactory()
    ->getEditable('jsonapi.settings')
    ->set('read_only', FALSE)
    ->save(TRUE);

  // Verify
  drupal_static_reset();
  $config2 = \Drupal::config('jsonapi.settings');
  echo "After save: read_only = " . var_export($config2->get('read_only'), true) . "\n";
}

// Update all profiles to xai3
echo "\nUpdating creator profiles...\n";
$nids = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'creator_x_profile')
  ->execute();

foreach (\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids) as $node) {
  $username = $node->get('field_x_username')->value ?? '?';
  $old = $node->get('field_store_theme')->value ?? '(none)';
  $node->set('field_store_theme', 'xai3');
  $node->save();
  echo "  $username: $old -> xai3\n";
}

echo "\nDone. " . count($nids) . " profiles updated.\n";

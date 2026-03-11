<?php
/**
 * Fix JSON:API write mode + update profiles to xai3.
 * Run: drush php:script fix_all.php
 */

// 1. Fix JSON:API read-only
$config = \Drupal::configFactory()->getEditable('jsonapi.settings');
echo "Before: read_only = " . var_export($config->get('read_only'), true) . "\n";
$config->set('read_only', FALSE)->save();
echo "After: read_only = " . var_export($config->get('read_only'), true) . "\n\n";

// 2. Update all creator profiles to xai3
$storage = \Drupal::entityTypeManager()->getStorage('node');
$nids = $storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'creator_x_profile')
  ->execute();

echo "Found " . count($nids) . " creator profiles\n";

foreach ($storage->loadMultiple($nids) as $node) {
  $username = $node->get('field_x_username')->value ?? '?';
  $old = $node->get('field_store_theme')->value ?? '(none)';
  $node->set('field_store_theme', 'xai3');
  $node->save();
  echo "  $username: $old -> xai3\n";
}

echo "\nDone!\n";

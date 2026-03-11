<?php

/**
 * Set all creator X profiles to "default" theme.
 * Run via: drush php:script set_default_theme.php
 */

$nids = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->getQuery()
  ->condition('type', 'creator_x_profile')
  ->accessCheck(FALSE)
  ->execute();

$nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

foreach ($nodes as $node) {
  $username = $node->get('field_x_username')->value ?? '(unknown)';
  $current = $node->get('field_store_theme')->value ?? '(none)';
  $node->set('field_store_theme', 'default');
  $node->save();
  echo "[$username] $current -> default\n";
}

echo "\nDone. Updated " . count($nodes) . " profiles to default theme.\n";

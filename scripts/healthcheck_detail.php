<?php
/**
 * Drupal application-level health checks.
 * Run via: drush php:script healthcheck_detail.php
 * Called automatically by healthcheck.sh if present.
 */

$exit_code = 0;

// Check 1: JSON:API read_only setting (has broken before)
$jsonapi_config = \Drupal::config('jsonapi.settings');
$read_only = $jsonapi_config->get('read_only');
if ($read_only) {
  echo "ERROR: jsonapi.settings read_only is TRUE (should be FALSE/0)\n";
  $exit_code = 1;
} else {
  echo "OK: jsonapi.settings read_only is disabled\n";
}

// Check 2: Verify entity types can load (catch schema mismatches)
$entity_types = ['node', 'commerce_store', 'commerce_product', 'user'];
foreach ($entity_types as $type) {
  try {
    $definition = \Drupal::entityTypeManager()->getDefinition($type, FALSE);
    if ($definition) {
      echo "OK: Entity type '$type' definition loaded\n";
    } else {
      echo "WARNING: Entity type '$type' not found (module may not be enabled)\n";
    }
  } catch (\Exception $e) {
    echo "ERROR: Entity type '$type' failed to load: " . $e->getMessage() . "\n";
    $exit_code = 1;
  }
}

// Check 3: Database connectivity and basic counts
try {
  $db = \Drupal::database();
  $node_count = $db->query("SELECT COUNT(*) FROM node_field_data")->fetchField();
  $store_count = $db->query("SELECT COUNT(*) FROM commerce_store_field_data")->fetchField();
  echo "OK: Database accessible - $node_count nodes, $store_count stores\n";
} catch (\Exception $e) {
  echo "ERROR: Database query failed: " . $e->getMessage() . "\n";
  $exit_code = 1;
}

// Check 4: Verify creator_x_profile content type exists
try {
  $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
  if (isset($bundles['creator_x_profile'])) {
    echo "OK: creator_x_profile content type exists\n";
  } else {
    echo "ERROR: creator_x_profile content type is missing\n";
    $exit_code = 1;
  }
} catch (\Exception $e) {
  echo "ERROR: Could not check bundles: " . $e->getMessage() . "\n";
  $exit_code = 1;
}

// Check 5: Verify cron has run recently (within 24 hours)
$cron_last = \Drupal::state()->get('system.cron_last', 0);
$hours_since_cron = (time() - $cron_last) / 3600;
if ($hours_since_cron > 24) {
  echo "WARNING: Cron last ran " . round($hours_since_cron) . " hours ago\n";
} else {
  echo "OK: Cron last ran " . round($hours_since_cron, 1) . " hours ago\n";
}

// Use drush_set_error for non-zero exit instead of exit() to avoid drush warnings
if ($exit_code !== 0) {
  drush_set_error('HEALTHCHECK_FAILED', 'One or more health checks failed.');
}

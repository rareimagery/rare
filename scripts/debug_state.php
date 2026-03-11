<?php
/**
 * Debug: list content types and check container state.
 */

// List all content types
$types = \Drupal::entityTypeManager()
  ->getStorage('node_type')
  ->loadMultiple();

echo "Content types:\n";
foreach ($types as $type) {
  echo "  - " . $type->id() . " (" . $type->label() . ")\n";
}

// Check the compiled container's ReadOnlyModeMethodFilter
$filter = \Drupal::service('jsonapi.method_filter');
$reflection = new ReflectionClass($filter);
$prop = $reflection->getProperty('readOnlyModeIsEnabled');
$prop->setAccessible(true);
$val = $prop->getValue($filter);
echo "\nReadOnlyModeMethodFilter->readOnlyModeIsEnabled = " . var_export($val, true) . "\n";

// Config check
$config = \Drupal::config('jsonapi.settings');
echo "Config read_only = " . var_export($config->get('read_only'), true) . "\n";

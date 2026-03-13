<?php
/**
 * Setup X Subscription fields on creator_x_profile.
 *
 * Adds:
 *   - field_x_subscription_tier (list_string): none, rare_supporter, inner_circle
 *   - field_x_subscriber_since (datetime)
 *
 * Run: docker exec rare-drupal php /opt/drupal/scripts/setup_x_subscription_fields.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

$autoloader = require_once '/opt/drupal/web/autoload.php';
chdir('/opt/drupal/web');
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$kernel = \Drupal\Core\DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);
\Drupal::setContainer($kernel->getContainer());

$entity_type = 'node';
$bundle = 'creator_x_profile';

// ── field_x_subscription_tier ──
$field_name = 'field_x_subscription_tier';
if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
  FieldStorageConfig::create([
    'field_name' => $field_name,
    'entity_type' => $entity_type,
    'type' => 'list_string',
    'settings' => [
      'allowed_values' => [
        'none' => 'None',
        'rare_supporter' => 'Rare Supporter ($5/mo)',
        'inner_circle' => 'Inner Circle Builder ($10/mo)',
      ],
    ],
    'cardinality' => 1,
  ])->save();
  echo "Created field storage: $field_name\n";
} else {
  echo "Field storage already exists: $field_name\n";
}

if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
  FieldConfig::create([
    'field_name' => $field_name,
    'entity_type' => $entity_type,
    'bundle' => $bundle,
    'label' => 'X Subscription Tier',
    'required' => false,
    'default_value' => [['value' => 'none']],
  ])->save();
  echo "Created field instance: $field_name on $bundle\n";
} else {
  echo "Field instance already exists: $field_name on $bundle\n";
}

// ── field_x_subscriber_since ──
$field_name = 'field_x_subscriber_since';
if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
  FieldStorageConfig::create([
    'field_name' => $field_name,
    'entity_type' => $entity_type,
    'type' => 'datetime',
    'settings' => ['datetime_type' => 'datetime'],
    'cardinality' => 1,
  ])->save();
  echo "Created field storage: $field_name\n";
} else {
  echo "Field storage already exists: $field_name\n";
}

if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
  FieldConfig::create([
    'field_name' => $field_name,
    'entity_type' => $entity_type,
    'bundle' => $bundle,
    'label' => 'X Subscriber Since',
    'required' => false,
  ])->save();
  echo "Created field instance: $field_name on $bundle\n";
} else {
  echo "Field instance already exists: $field_name on $bundle\n";
}

// Clear caches
\Drupal::service('cache.default')->deleteAll();
echo "\nDone! X subscription fields created on $bundle.\n";

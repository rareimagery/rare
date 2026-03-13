<?php

/**
 * Phase 2 Social Space Setup:
 * 1. Create 'shoutout' content type with fields
 * 2. Add field_my_picks (string_long JSON) to commerce_store
 */

use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// ---- 1. Create shoutout content type ----

$type = NodeType::load('shoutout');
if ($type) {
  echo "Content type 'shoutout' already exists.\n";
} else {
  $type = NodeType::create([
    'type' => 'shoutout',
    'name' => 'Shoutout',
    'description' => 'Short shoutout from one creator to another',
    'display_submitted' => TRUE,
    'new_revision' => FALSE,
  ]);
  $type->save();
  echo "Created content type 'shoutout'.\n";
}

// Shoutout fields
$shoutout_fields = [
  'field_from_store' => [
    'type' => 'string',
    'label' => 'From Store ID',
    'description' => 'UUID of the store posting the shoutout',
    'entity_type' => 'node',
    'bundle' => 'shoutout',
  ],
  'field_to_store' => [
    'type' => 'string',
    'label' => 'To Store ID',
    'description' => 'UUID of the store receiving the shoutout',
    'entity_type' => 'node',
    'bundle' => 'shoutout',
  ],
  'field_shoutout_text' => [
    'type' => 'string',
    'label' => 'Shoutout Text',
    'description' => 'Short shoutout message (max 120 chars)',
    'entity_type' => 'node',
    'bundle' => 'shoutout',
  ],
  'field_shoutout_status' => [
    'type' => 'string',
    'label' => 'Shoutout Status',
    'description' => 'published or deleted',
    'entity_type' => 'node',
    'bundle' => 'shoutout',
  ],
  'field_from_x_username' => [
    'type' => 'string',
    'label' => 'From X Username',
    'description' => 'X username of the creator who posted',
    'entity_type' => 'node',
    'bundle' => 'shoutout',
  ],
  'field_from_profile_pic' => [
    'type' => 'string',
    'label' => 'From Profile Picture URL',
    'description' => 'Profile picture URL of the creator who posted',
    'entity_type' => 'node',
    'bundle' => 'shoutout',
  ],
];

foreach ($shoutout_fields as $field_name => $info) {
  $storage = FieldStorageConfig::loadByName($info['entity_type'], $field_name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $info['entity_type'],
      'type' => $info['type'],
      'cardinality' => 1,
    ]);
    $storage->save();
    echo "Created field storage: $field_name\n";
  } else {
    echo "Field storage exists: $field_name\n";
  }

  $field = FieldConfig::loadByName($info['entity_type'], $info['bundle'], $field_name);
  if (!$field) {
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $info['entity_type'],
      'bundle' => $info['bundle'],
      'label' => $info['label'],
      'description' => $info['description'],
      'required' => FALSE,
    ]);
    $field->save();
    echo "Created field: $field_name on {$info['bundle']}\n";
  } else {
    echo "Field exists: $field_name on {$info['bundle']}\n";
  }
}

// ---- 2. Add field_my_picks to commerce_store ----
// Stored as JSON string: [{ storeId, storeName, storeSlug, xUsername, profilePictureUrl }]

$field_name = 'field_my_picks';
$storage = FieldStorageConfig::loadByName('commerce_store', $field_name);
if (!$storage) {
  $storage = FieldStorageConfig::create([
    'field_name' => $field_name,
    'entity_type' => 'commerce_store',
    'type' => 'string_long',
    'cardinality' => 1,
  ]);
  $storage->save();
  echo "Created field storage: $field_name on commerce_store\n";
} else {
  echo "Field storage exists: $field_name on commerce_store\n";
}

$field = FieldConfig::loadByName('commerce_store', 'online', $field_name);
if (!$field) {
  $field = FieldConfig::create([
    'field_name' => $field_name,
    'entity_type' => 'commerce_store',
    'bundle' => 'online',
    'label' => 'My Picks',
    'description' => 'JSON array of curated creator picks (max 10)',
    'required' => FALSE,
  ]);
  $field->save();
  echo "Created field: $field_name on commerce_store:online\n";
} else {
  echo "Field exists: $field_name on commerce_store:online\n";
}

echo "\nPhase 2 Drupal setup complete!\n";

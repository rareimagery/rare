<?php

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

$fields = [
  'field_follower_store_id' => [
    'type' => 'string',
    'label' => 'Follower Store ID',
    'description' => 'UUID of the store that is following',
  ],
  'field_follow_source' => [
    'type' => 'string',
    'label' => 'Follow Source',
    'description' => 'How the follow was created: rareimagery or x_import',
  ],
];

foreach ($fields as $field_name => $info) {
  $storage = FieldStorageConfig::loadByName('flagging', $field_name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'flagging',
      'type' => $info['type'],
      'cardinality' => 1,
    ]);
    $storage->save();
    echo "Created field storage: $field_name\n";
  } else {
    echo "Field storage already exists: $field_name\n";
  }

  $field = FieldConfig::loadByName('flagging', 'follow_creator', $field_name);
  if (!$field) {
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'flagging',
      'bundle' => 'follow_creator',
      'label' => $info['label'],
      'description' => $info['description'],
      'required' => FALSE,
    ]);
    $field->save();
    echo "Created field instance: $field_name on flagging:follow_creator\n";
  } else {
    echo "Field already exists: $field_name on flagging:follow_creator\n";
  }
}

echo "Flagging fields setup complete!\n";

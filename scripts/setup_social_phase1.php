<?php

/**
 * Phase 1 Social Space Setup:
 * 1. Create follow_creator flag on commerce_store
 * 2. Add social fields to commerce_store
 */

use Drupal\flag\Entity\Flag;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// ---- 1. Create follow_creator flag ----

$flag = Flag::load('follow_creator');
if ($flag) {
  echo "Flag 'follow_creator' already exists.\n";
} else {
  $flag = Flag::create([
    'id' => 'follow_creator',
    'label' => 'Follow Creator',
    'entity_type' => 'commerce_store',
    'flag_type' => 'entity:commerce_store',
    'link_type' => 'reload',
    'flagTypeConfig' => [],
    'linkTypeConfig' => [],
    'flag_short' => 'Follow',
    'flag_long' => 'Follow this creator',
    'flag_message' => 'You are now following this creator.',
    'unflag_short' => 'Unfollow',
    'unflag_long' => 'Unfollow this creator',
    'unflag_message' => 'You have unfollowed this creator.',
    'show_in_links' => [],
    'show_as_field' => TRUE,
    'show_on_form' => FALSE,
    'show_contextual_link' => FALSE,
    'bundles' => ['online'],
    'global' => FALSE,
  ]);
  $flag->save();
  echo "Created 'follow_creator' flag on commerce_store.\n";
}

// ---- 2. Add social fields to commerce_store (online bundle) ----

$fields = [
  'field_follower_count' => [
    'type' => 'integer',
    'label' => 'Follower Count',
    'description' => 'Number of followers (denormalized)',
  ],
  'field_following_count' => [
    'type' => 'integer',
    'label' => 'Following Count',
    'description' => 'Number of stores this creator follows',
  ],
  'field_x_seed_imported' => [
    'type' => 'boolean',
    'label' => 'X Seed Imported',
    'description' => 'Whether X follower graph has been seeded',
  ],
  'field_social_bio' => [
    'type' => 'text_long',
    'label' => 'Social Bio',
    'description' => 'Short about me for the social profile card',
  ],
  'field_shoutout_enabled' => [
    'type' => 'boolean',
    'label' => 'Shoutouts Enabled',
    'description' => 'Allow others to post shoutouts (default TRUE)',
  ],
  'field_collab_open' => [
    'type' => 'boolean',
    'label' => 'Open to Collabs',
    'description' => 'Open to collaboration requests (default FALSE)',
  ],
];

foreach ($fields as $field_name => $info) {
  // Create field storage if not exists
  $storage = FieldStorageConfig::loadByName('commerce_store', $field_name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'commerce_store',
      'type' => $info['type'],
      'cardinality' => 1,
    ]);
    $storage->save();
    echo "Created field storage: $field_name\n";
  } else {
    echo "Field storage already exists: $field_name\n";
  }

  // Create field instance on online bundle
  $field = FieldConfig::loadByName('commerce_store', 'online', $field_name);
  if (!$field) {
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'commerce_store',
      'bundle' => 'online',
      'label' => $info['label'],
      'description' => $info['description'],
      'required' => FALSE,
    ]);

    // Set defaults for booleans
    if ($info['type'] === 'boolean') {
      if ($field_name === 'field_shoutout_enabled') {
        $field->setDefaultValue(1);
      } else {
        $field->setDefaultValue(0);
      }
    }
    if ($info['type'] === 'integer') {
      $field->setDefaultValue(0);
    }

    $field->save();
    echo "Created field instance: $field_name on commerce_store:online\n";
  } else {
    echo "Field instance already exists: $field_name on commerce_store:online\n";
  }
}

echo "\nPhase 1 Drupal setup complete!\n";

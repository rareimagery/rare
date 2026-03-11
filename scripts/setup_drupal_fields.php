<?php
/**
 * Drupal field setup script for RareImagery X Marketplace.
 *
 * Run with:
 *   drush php:script scripts/setup_drupal_fields.php
 *
 * Creates all custom fields on:
 *   - commerce_store (online)
 *   - node (creator_x_profile)
 *   - commerce_product (default)
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\NodeType;

// ---------------------------------------------------------------------------
// Helper: create field storage + field instance if they don't already exist.
// ---------------------------------------------------------------------------

function ensure_field($entity_type, $bundle, $field_name, $field_type, $settings = [], $instance_settings = []) {
  // 1. Field Storage (shared across bundles)
  $storage = FieldStorageConfig::loadByName($entity_type, $field_name);
  if (!$storage) {
    $storage_def = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
      'cardinality' => $settings['cardinality'] ?? 1,
    ];
    if (!empty($settings['storage_settings'])) {
      $storage_def['settings'] = $settings['storage_settings'];
    }
    $storage = FieldStorageConfig::create($storage_def);
    $storage->save();
    echo "  STORAGE CREATED: $entity_type.$field_name ($field_type)\n";
  } else {
    echo "  STORAGE EXISTS:  $entity_type.$field_name\n";
  }

  // 2. Field Instance (per bundle)
  $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
  if (!$field) {
    $field_def = [
      'field_storage' => $storage,
      'bundle' => $bundle,
      'label' => $instance_settings['label'] ?? ucwords(str_replace(['field_', '_'], ['', ' '], $field_name)),
      'required' => $instance_settings['required'] ?? FALSE,
    ];
    if (!empty($instance_settings['description'])) {
      $field_def['description'] = $instance_settings['description'];
    }
    if (!empty($instance_settings['settings'])) {
      $field_def['settings'] = $instance_settings['settings'];
    }
    $field = FieldConfig::create($field_def);
    $field->save();
    echo "  FIELD CREATED:   $entity_type.$bundle.$field_name\n";
  } else {
    echo "  FIELD EXISTS:    $entity_type.$bundle.$field_name\n";
  }
}

// ===========================================================================
// 1. CREATOR X PROFILE content type
// ===========================================================================

echo "\n=== CREATOR X PROFILE (node: creator_x_profile) ===\n";

// Ensure the content type exists
$type = NodeType::load('creator_x_profile');
if (!$type) {
  $type = NodeType::create([
    'type' => 'creator_x_profile',
    'name' => 'Creator X Profile',
    'description' => 'X profile data linked to each Creator Store – auto-populated by Grok',
  ]);
  $type->save();
  echo "CONTENT TYPE CREATED: creator_x_profile\n";
} else {
  echo "CONTENT TYPE EXISTS: creator_x_profile\n";
}

// --- Text fields ---
ensure_field('node', 'creator_x_profile', 'field_x_username', 'string', [], [
  'label' => 'X Username',
  'required' => TRUE,
  'description' => 'Twitter/X handle without the @ symbol',
]);

ensure_field('node', 'creator_x_profile', 'field_store_theme', 'string', [], [
  'label' => 'Store Theme',
  'description' => 'Theme ID: default, minimal, neon, editorial, myspace',
]);

ensure_field('node', 'creator_x_profile', 'field_myspace_background', 'string', [], [
  'label' => 'MySpace Background URL',
]);

ensure_field('node', 'creator_x_profile', 'field_myspace_music_url', 'string', [], [
  'label' => 'MySpace Music URL',
]);

ensure_field('node', 'creator_x_profile', 'field_myspace_glitter_color', 'string', [], [
  'label' => 'MySpace Glitter Color',
  'description' => 'Hex color code, e.g. #ff00ff',
]);

ensure_field('node', 'creator_x_profile', 'field_myspace_accent_color', 'string', [], [
  'label' => 'MySpace Accent Color',
  'description' => 'Hex color code, e.g. #00ffff',
]);

// --- Formatted text ---
ensure_field('node', 'creator_x_profile', 'field_bio_description', 'text_long', [], [
  'label' => 'Bio / Description',
]);

// --- Number ---
ensure_field('node', 'creator_x_profile', 'field_follower_count', 'integer', [], [
  'label' => 'Follower Count',
]);

// --- Multi-value JSON text fields ---
ensure_field('node', 'creator_x_profile', 'field_top_posts', 'string_long', [
  'cardinality' => -1, // unlimited
], [
  'label' => 'Top 8 Posts',
  'description' => 'JSON-encoded post objects (text, likes, retweets, views)',
]);

ensure_field('node', 'creator_x_profile', 'field_top_followers', 'string_long', [
  'cardinality' => -1,
], [
  'label' => 'Top 8 Followers',
  'description' => 'JSON-encoded follower objects (username, name, follower_count)',
]);

ensure_field('node', 'creator_x_profile', 'field_metrics', 'string_long', [], [
  'label' => 'Metrics',
  'description' => 'JSON engagement metrics from Grok (engagement_score, themes, etc.)',
]);

// --- Image fields ---
ensure_field('node', 'creator_x_profile', 'field_profile_picture', 'image', [
  'storage_settings' => ['default_image' => ['uuid' => NULL, 'alt' => '', 'title' => '', 'width' => NULL, 'height' => NULL]],
], [
  'label' => 'Profile Picture (PFP)',
  'settings' => [
    'file_directory' => 'creator-pfps',
    'file_extensions' => 'png jpg jpeg',
    'alt_field' => FALSE,
  ],
]);

ensure_field('node', 'creator_x_profile', 'field_background_banner', 'image', [
  'storage_settings' => ['default_image' => ['uuid' => NULL, 'alt' => '', 'title' => '', 'width' => NULL, 'height' => NULL]],
], [
  'label' => 'Background Banner',
  'settings' => [
    'file_directory' => 'creator-banners',
    'file_extensions' => 'png jpg jpeg',
    'alt_field' => FALSE,
  ],
]);

// --- Entity Reference: Linked Store ---
ensure_field('node', 'creator_x_profile', 'field_linked_store', 'entity_reference', [
  'storage_settings' => ['target_type' => 'commerce_store'],
], [
  'label' => 'Linked Store',
  'description' => 'The Commerce Store linked to this creator profile',
  'settings' => [
    'handler' => 'default:commerce_store',
    'handler_settings' => [
      'target_bundles' => ['online' => 'online'],
      'auto_create' => FALSE,
    ],
  ],
]);


// ===========================================================================
// 2. COMMERCE STORE (online type)
// ===========================================================================

echo "\n=== COMMERCE STORE (commerce_store: online) ===\n";

ensure_field('commerce_store', 'online', 'field_store_slug', 'string', [], [
  'label' => 'Store Slug',
  'required' => TRUE,
  'description' => 'URL-safe username used for subdomain routing (e.g. elonmusk)',
]);

ensure_field('commerce_store', 'online', 'field_store_theme', 'string_long', [], [
  'label' => 'Store Theme Config',
  'description' => 'JSON theme configuration for MySpace or other custom themes',
]);

ensure_field('commerce_store', 'online', 'field_store_status', 'list_string', [
  'storage_settings' => [
    'allowed_values' => [
      'pending' => 'Pending Approval',
      'approved' => 'Approved',
      'rejected' => 'Rejected',
    ],
  ],
], [
  'label' => 'Store Status',
  'required' => TRUE,
  'description' => 'Admin approval status: pending, approved, or rejected',
  'settings' => [],
]);

ensure_field('commerce_store', 'online', 'field_linked_x_profile', 'entity_reference', [
  'storage_settings' => ['target_type' => 'node'],
], [
  'label' => 'Linked X Profile',
  'description' => 'The Creator X Profile linked to this store',
  'settings' => [
    'handler' => 'default:node',
    'handler_settings' => [
      'target_bundles' => ['creator_x_profile' => 'creator_x_profile'],
      'auto_create' => FALSE,
    ],
  ],
]);


// ===========================================================================
// 3. COMMERCE PRODUCT (default type)
// ===========================================================================

echo "\n=== COMMERCE PRODUCT (commerce_product: default) ===\n";

ensure_field('commerce_product', 'default', 'field_images', 'image', [
  'cardinality' => -1,
  'storage_settings' => ['default_image' => ['uuid' => NULL, 'alt' => '', 'title' => '', 'width' => NULL, 'height' => NULL]],
], [
  'label' => 'Product Images',
  'settings' => [
    'file_directory' => 'product-images',
    'file_extensions' => 'png jpg jpeg webp',
    'alt_field' => TRUE,
  ],
]);


// ===========================================================================
// 4. USER NOTIFICATION PREFERENCES
// ===========================================================================

echo "\n=== USER NOTIFICATION PREFERENCES (user: user) ===\n";

ensure_field('user', 'user', 'field_phone_number', 'string', [], [
  'label' => 'Mobile Phone Number',
  'description' => 'E.164 format (e.g. +15551234567). Enables SMS notifications.',
]);

ensure_field('user', 'user', 'field_notification_channel', 'list_string', [
  'storage_settings' => [
    'allowed_values' => [
      'email' => 'Email Only',
      'email_sms' => 'Email + SMS',
    ],
  ],
], [
  'label' => 'Notification Channel',
  'description' => 'How you want to receive notifications',
  'settings' => [],
]);

ensure_field('user', 'user', 'field_sms_alert_level', 'list_string', [
  'storage_settings' => [
    'allowed_values' => [
      'all' => 'All Events',
      'sales' => 'Sales Only',
      'critical' => 'Critical Only (approval, payment issues)',
    ],
  ],
], [
  'label' => 'SMS Alert Level',
  'description' => 'Which events trigger SMS notifications',
  'settings' => [],
]);


// ===========================================================================
// Done
// ===========================================================================

echo "\n✅ All fields created successfully!\n";
echo "Next steps:\n";
echo "  1. Run: drush php:script scripts/create_demo_profiles.php\n";
echo "  2. Run: drush php:script scripts/create_demo_stores.php\n";
echo "  3. Run: drush php:script scripts/create_demo_products.php\n";
echo "  4. Run: drush cr (clear caches)\n";

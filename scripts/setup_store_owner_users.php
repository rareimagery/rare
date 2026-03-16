<?php

/**
 * Setup script: Create store owner roles, permissions, and user profile fields.
 * Run via: drush php:script setup_store_owner_users.php
 */

use Drupal\user\Entity\Role;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// ============================================================
// Helper: ensure field storage + field config
// ============================================================
function ensure_field($entity_type, $bundle, $field_name, $field_type, $label, $settings = [], $cardinality = 1) {
  $storage = FieldStorageConfig::loadByName($entity_type, $field_name);
  if (!$storage) {
    $storage_settings = [];
    if ($field_type === 'entity_reference' && !empty($settings['target_type'])) {
      $storage_settings['target_type'] = $settings['target_type'];
    }
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
      'cardinality' => $cardinality,
      'settings' => $storage_settings,
    ]);
    $storage->save();
    echo "    [+] Created field storage: $entity_type.$field_name\n";
  }

  $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
  if (!$field) {
    $field_settings = [];
    if ($field_type === 'entity_reference' && !empty($settings['target_type'])) {
      $field_settings['handler'] = 'default';
      if (!empty($settings['target_bundles'])) {
        $field_settings['handler_settings'] = [
          'target_bundles' => $settings['target_bundles'],
        ];
      }
    }

    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $label,
      'settings' => $field_settings,
    ]);
    $field->save();
    echo "    [+] Created field instance: $entity_type.$bundle.$field_name\n";
  } else {
    echo "    [=] Field exists: $entity_type.$bundle.$field_name\n";
  }
}

// ============================================================
// Helper: ensure role exists
// ============================================================
function ensure_role($rid, $label) {
  $role = Role::load($rid);
  if (!$role) {
    $role = Role::create(['id' => $rid, 'label' => $label]);
    $role->save();
    echo "  [+] Created role: $rid\n";
  } else {
    echo "  [=] Role exists: $rid\n";
  }
  return $role;
}


echo "\n=== STEP 1: Create Roles ===\n";

$store_owner = ensure_role('store_owner', 'Store Owner');
$store_manager = ensure_role('store_manager', 'Store Manager');


echo "\n=== STEP 2: Set Store Owner Permissions ===\n";

$owner_permissions = [
  // Commerce Store
  'create commerce_store',
  'create online commerce_store',
  'view commerce_store',
  'view online commerce_store',
  'update own online commerce_store',
  'access commerce_store overview',

  // Commerce Products — all 3 types + default
  'access commerce_product overview',
  'create default commerce_product',
  'update own default commerce_product',
  'delete own default commerce_product',
  'view default commerce_product',

  'create clothing commerce_product',
  'update own clothing commerce_product',
  'delete own clothing commerce_product',
  'view clothing commerce_product',

  'create digital_download commerce_product',
  'update own digital_download commerce_product',
  'delete own digital_download commerce_product',
  'view digital_download commerce_product',

  'create crafts commerce_product',
  'update own crafts commerce_product',
  'delete own crafts commerce_product',
  'view crafts commerce_product',

  // Commerce Product Variations
  'manage default commerce_product_variation',
  'manage clothing commerce_product_variation',
  'manage digital_download commerce_product_variation',
  'manage crafts commerce_product_variation',

  // Commerce Orders
  'view own commerce_order',
  'view commerce_order',
  'access commerce_order overview',

  // Taxonomy
  'access content',

  // Creator profile content type used by Next.js integration
  'create creator_x_profile content',
  'edit own creator_x_profile content',
  'edit any creator_x_profile content',
  'view any unpublished content',

  // Files / Media
  'access content overview',

  // User account
  'change own username',
];

$granted = 0;
$skipped = 0;
foreach ($owner_permissions as $perm) {
  try {
    if (!$store_owner->hasPermission($perm)) {
      $store_owner->grantPermission($perm);
      $granted++;
    } else {
      $skipped++;
    }
  } catch (\Exception $e) {
    echo "  [!] Warning: Could not grant '$perm': " . $e->getMessage() . "\n";
  }
}
$store_owner->save();
echo "  Store Owner: granted $granted permissions, $skipped already set\n";


echo "\n=== STEP 3: Set Store Manager Permissions ===\n";

$manager_permissions = [
  // Commerce Store — view only
  'create commerce_store',
  'create online commerce_store',
  'view commerce_store',
  'view online commerce_store',

  // Commerce Products — CRUD
  'access commerce_product overview',
  'create default commerce_product',
  'update own default commerce_product',
  'delete own default commerce_product',
  'view default commerce_product',

  'create clothing commerce_product',
  'update own clothing commerce_product',
  'delete own clothing commerce_product',
  'view clothing commerce_product',

  'create digital_download commerce_product',
  'update own digital_download commerce_product',
  'delete own digital_download commerce_product',
  'view digital_download commerce_product',

  'create crafts commerce_product',
  'update own crafts commerce_product',
  'delete own crafts commerce_product',
  'view crafts commerce_product',

  // Variations
  'manage default commerce_product_variation',
  'manage clothing commerce_product_variation',
  'manage digital_download commerce_product_variation',
  'manage crafts commerce_product_variation',

  // Orders — view
  'view own commerce_order',
  'view commerce_order',
  'access commerce_order overview',

  // Basic
  'access content',

  // Creator profile content type used by Next.js integration
  'create creator_x_profile content',
  'edit own creator_x_profile content',
  'edit any creator_x_profile content',
  'view any unpublished content',
];

$granted = 0;
$skipped = 0;
foreach ($manager_permissions as $perm) {
  try {
    if (!$store_manager->hasPermission($perm)) {
      $store_manager->grantPermission($perm);
      $granted++;
    } else {
      $skipped++;
    }
  } catch (\Exception $e) {
    echo "  [!] Warning: Could not grant '$perm': " . $e->getMessage() . "\n";
  }
}
$store_manager->save();
echo "  Store Manager: granted $granted permissions, $skipped already set\n";


echo "\n=== STEP 4: User Profile Fields ===\n";

// User profile fields on user.user bundle
$user_fields = [
  ['field_store', 'entity_reference', 'Store', ['target_type' => 'commerce_store'], 1],
  ['field_shop_name', 'string', 'Display / Shop Name', [], 1],
  ['field_shop_logo', 'image', 'Shop Logo', [], 1],
  ['field_bio', 'text_long', 'Bio / About', [], 1],
  ['field_phone', 'telephone', 'Contact Phone', [], 1],
  ['field_social_links', 'link', 'Social Links', [], -1],
  ['field_payout_info', 'string_long', 'Payout / Payment Info', [], 1],
  ['field_onboarding_complete', 'boolean', 'Onboarding Complete', [], 1],
  ['field_admin_notes', 'text_long', 'Account Notes (admin only)', [], 1],
];

foreach ($user_fields as $f) {
  ensure_field('user', 'user', $f[0], $f[1], $f[2], $f[3], $f[4]);
}


echo "\n\n=== DONE ===\n";
echo "Created roles: store_owner, store_manager\n";
echo "Assigned permissions to both roles\n";
echo "Created 9 user profile fields\n";
echo "\nNext steps:\n";
echo "  - Configure Views contextual filters to scope store owner admin views\n";
echo "  - Optionally install Commerce Marketplace for full multi-vendor support\n";
echo "  - Consider enabling Masquerade module for admin support\n";
echo "  - Set up auto-assign store on product creation (hook_entity_presave)\n";

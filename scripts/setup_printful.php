<?php

/**
 * Configure Printful integration:
 * - Create a "printful" product type with matching variation type
 * - Add Printful-specific fields to variations
 * - Add Printful-related permissions to store_owner role
 * Run via: drush php:script setup_printful.php
 */

use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

// ============================================================
// Helper: ensure field storage + instance
// ============================================================
function ensure_printful_field($entity_type, $bundle, $field_name, $field_type, $settings = [], $cardinality = 1) {
  // Storage
  $storage = FieldStorageConfig::loadByName($entity_type, $field_name);
  if (!$storage) {
    $storage_def = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $field_type,
      'cardinality' => $cardinality,
    ];
    if (!empty($settings['allowed_values'])) {
      $storage_def['settings'] = ['allowed_values' => $settings['allowed_values']];
    }
    FieldStorageConfig::create($storage_def)->save();
    echo "  [+] Storage: $field_name ($field_type)\n";
  } else {
    echo "  [=] Storage exists: $field_name\n";
  }

  // Instance
  $instance = FieldConfig::loadByName($entity_type, $bundle, $field_name);
  if (!$instance) {
    $instance_def = [
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $settings['label'] ?? ucwords(str_replace(['field_', '_'], ['', ' '], $field_name)),
    ];
    if (isset($settings['description'])) {
      $instance_def['description'] = $settings['description'];
    }
    if ($field_type === 'image') {
      $instance_def['settings'] = [
        'file_extensions' => 'png jpg jpeg gif webp',
        'max_filesize' => '20 MB',
      ];
    }
    FieldConfig::create($instance_def)->save();
    echo "  [+] Instance: $entity_type.$bundle.$field_name\n";
  } else {
    echo "  [=] Instance exists: $entity_type.$bundle.$field_name\n";
  }
}

// ============================================================
// 1. Create Printful variation type
// ============================================================
echo "\n=== Creating Printful Variation Type ===\n";

$var_type = ProductVariationType::load('printful');
if (!$var_type) {
  $var_type = ProductVariationType::create([
    'id' => 'printful',
    'label' => 'Printful POD',
    'orderItemType' => 'default',
    'generateTitle' => TRUE,
  ]);
  $var_type->save();
  echo "  [+] Created variation type: printful\n";
} else {
  echo "  [=] Variation type exists: printful\n";
}

// ============================================================
// 2. Create Printful product type
// ============================================================
echo "\n=== Creating Printful Product Type ===\n";

$product_type = ProductType::load('printful');
if (!$product_type) {
  $product_type = ProductType::create([
    'id' => 'printful',
    'label' => 'Printful POD',
    'description' => 'Print-on-demand products fulfilled by Printful',
    'variationType' => 'printful',
    'multipleVariations' => TRUE,
    'injectVariationFields' => TRUE,
  ]);
  $product_type->save();
  echo "  [+] Created product type: printful\n";
} else {
  echo "  [=] Product type exists: printful\n";
}

// ============================================================
// 3. Add product-level fields
// ============================================================
echo "\n=== Adding Printful Product Fields ===\n";

$product_fields = [
  ['field_images', 'image', ['label' => 'Product Images'], -1],
  ['field_short_description', 'string_long', ['label' => 'Short Description']],
  ['field_categories', 'entity_reference', ['label' => 'Categories', 'allowed_values' => []]],
  ['field_tags', 'entity_reference', ['label' => 'Tags', 'allowed_values' => []]],
  ['field_seo_title', 'string', ['label' => 'SEO Title']],
  ['field_seo_description', 'string_long', ['label' => 'SEO Description']],
  ['field_featured', 'boolean', ['label' => 'Featured Product']],
  ['field_related_products', 'entity_reference', ['label' => 'Related Products'], -1],
  ['field_printful_product_id', 'string', ['label' => 'Printful Product ID', 'description' => 'Printful external product ID for sync']],
  ['field_printful_store_id', 'string', ['label' => 'Printful Store ID']],
  ['field_production_time', 'string', ['label' => 'Production Time', 'description' => 'e.g. 2-7 business days']],
  ['field_print_technique', 'list_string', ['label' => 'Print Technique', 'allowed_values' => [
    'dtg' => 'DTG (Direct-to-Garment)',
    'embroidery' => 'Embroidery',
    'aop' => 'All-Over Print',
    'cut_sew' => 'Cut & Sew',
    'sublimation' => 'Sublimation',
  ]]],
];

foreach ($product_fields as $f) {
  $cardinality = $f[3] ?? 1;
  ensure_printful_field('commerce_product', 'printful', $f[0], $f[1], $f[2], $cardinality);
}

// ============================================================
// 4. Add variation-level fields
// ============================================================
echo "\n=== Adding Printful Variation Fields ===\n";

$variation_fields = [
  ['field_size', 'list_string', ['label' => 'Size', 'allowed_values' => [
    'XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L',
    'XL' => 'XL', 'XXL' => 'XXL', '3XL' => '3XL', '4XL' => '4XL',
    'One Size' => 'One Size',
  ]]],
  ['field_color', 'list_string', ['label' => 'Color', 'allowed_values' => [
    'Black' => 'Black', 'White' => 'White', 'Navy' => 'Navy',
    'Heather Grey' => 'Heather Grey', 'Red' => 'Red', 'Royal Blue' => 'Royal Blue',
    'Forest Green' => 'Forest Green', 'Maroon' => 'Maroon', 'Pink' => 'Pink',
    'Sand' => 'Sand', 'Charcoal' => 'Charcoal', 'Light Blue' => 'Light Blue',
  ]]],
  ['field_color_swatch', 'image', ['label' => 'Color Swatch']],
  ['field_variation_image', 'image', ['label' => 'Variation Mockup Image']],
  ['field_printful_variant_id', 'string', ['label' => 'Printful Variant ID', 'description' => 'Printful external variant ID for sync']],
  ['field_printful_base_cost', 'string', ['label' => 'Printful Base Cost', 'description' => 'Cost charged by Printful per unit']],
  ['field_stock', 'integer', ['label' => 'Stock']],
  ['field_on_sale', 'boolean', ['label' => 'On Sale']],
  ['field_sale_price', 'commerce_price', ['label' => 'Sale Price']],
];

foreach ($variation_fields as $f) {
  $cardinality = $f[3] ?? 1;
  ensure_printful_field('commerce_product_variation', 'printful', $f[0], $f[1], $f[2], $cardinality);
}

// ============================================================
// 5. Configure form displays
// ============================================================
echo "\n=== Configuring Form Displays ===\n";

function add_to_printful_form($entity_type, $bundle, $field_name, $widget_type, $weight) {
  $form_display = EntityFormDisplay::load("$entity_type.$bundle.default");
  if (!$form_display) {
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
  }
  $form_display->setComponent($field_name, [
    'type' => $widget_type,
    'weight' => $weight,
    'settings' => [],
  ]);
  $form_display->save();
}

// Product form
$product_form_fields = [
  ['field_images', 'image_image', 10],
  ['field_short_description', 'string_textarea', 11],
  ['field_printful_product_id', 'string_textfield', 12],
  ['field_printful_store_id', 'string_textfield', 13],
  ['field_production_time', 'string_textfield', 14],
  ['field_print_technique', 'options_select', 15],
  ['field_categories', 'entity_reference_autocomplete', 16],
  ['field_tags', 'entity_reference_autocomplete', 17],
  ['field_featured', 'boolean_checkbox', 18],
  ['field_seo_title', 'string_textfield', 19],
  ['field_seo_description', 'string_textarea', 20],
  ['field_related_products', 'entity_reference_autocomplete', 21],
];

foreach ($product_form_fields as $f) {
  add_to_printful_form('commerce_product', 'printful', $f[0], $f[1], $f[2]);
}
echo "  [+] Product form display configured\n";

// Variation form
$variation_form_fields = [
  ['field_size', 'options_select', 10],
  ['field_color', 'options_select', 11],
  ['field_color_swatch', 'image_image', 12],
  ['field_variation_image', 'image_image', 13],
  ['field_printful_variant_id', 'string_textfield', 14],
  ['field_printful_base_cost', 'string_textfield', 15],
  ['field_stock', 'number', 16],
  ['field_on_sale', 'boolean_checkbox', 17],
  ['field_sale_price', 'commerce_unit_price', 18],
];

foreach ($variation_form_fields as $f) {
  add_to_printful_form('commerce_product_variation', 'printful', $f[0], $f[1], $f[2]);
}
echo "  [+] Variation form display configured\n";

// ============================================================
// 6. Add Printful permissions to store_owner role
// ============================================================
echo "\n=== Adding Printful Permissions ===\n";

$role = \Drupal\user\Entity\Role::load('store_owner');
if ($role) {
  $printful_perms = [
    'view printful commerce_product',
    'create printful commerce_product',
    'update own printful commerce_product',
    'delete own printful commerce_product',
    'view printful commerce_product_variation',
    'create printful commerce_product_variation',
    'update own printful commerce_product_variation',
    'delete own printful commerce_product_variation',
  ];

  // Check which permissions actually exist
  $all_perms = \Drupal::service('user.permissions')->getPermissions();
  foreach ($printful_perms as $perm) {
    if (isset($all_perms[$perm])) {
      $role->grantPermission($perm);
      echo "  [+] Granted: $perm\n";
    } else {
      echo "  [!] Permission not found: $perm\n";
    }
  }
  $role->save();
} else {
  echo "  [!] store_owner role not found\n";
}

// ============================================================
// 7. Configure Printful shipping method
// ============================================================
echo "\n=== Shipping Configuration ===\n";
echo "  [i] Commerce Shipping is enabled.\n";
echo "  [i] Configure Printful shipping at: /admin/commerce/config/shipping-methods\n";
echo "  [i] Configure Printful API key at: /admin/commerce/config/printful/printful_store\n";

echo "\n=== DONE ===\n";
echo "Printful POD product type created with all fields.\n";
echo "Next steps:\n";
echo "  1. Add Printful API key at /admin/commerce/config/printful/printful_store\n";
echo "  2. Design products in Printful's Design Maker\n";
echo "  3. Import products via /admin/commerce/printful/import\n";

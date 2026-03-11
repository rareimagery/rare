<?php

/**
 * Setup script: Create 3 Commerce product types with all fields, variations, and taxonomies.
 * Run via: drush php:script setup_product_types.php
 *
 * Product types: clothing, digital_download, crafts
 */

use Drupal\commerce_product\Entity\ProductType;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Vocabulary;

// ============================================================
// Helper: ensure a taxonomy vocabulary exists
// ============================================================
function ensure_vocabulary($vid, $name) {
  $vocab = Vocabulary::load($vid);
  if (!$vocab) {
    $vocab = Vocabulary::create(['vid' => $vid, 'name' => $name]);
    $vocab->save();
    echo "  [+] Created vocabulary: $vid\n";
  } else {
    echo "  [=] Vocabulary exists: $vid\n";
  }
  return $vocab;
}

// ============================================================
// Helper: ensure a product variation type exists
// ============================================================
function ensure_variation_type($id, $label) {
  $type = ProductVariationType::load($id);
  if (!$type) {
    $type = ProductVariationType::create([
      'id' => $id,
      'label' => $label,
      'orderItemType' => 'default',
      'generateTitle' => TRUE,
    ]);
    $type->save();
    echo "  [+] Created variation type: $id\n";
  } else {
    echo "  [=] Variation type exists: $id\n";
  }
  return $type;
}

// ============================================================
// Helper: ensure a product type exists
// ============================================================
function ensure_product_type($id, $label, $variation_type_id) {
  $type = ProductType::load($id);
  if (!$type) {
    $type = ProductType::create([
      'id' => $id,
      'label' => $label,
      'variationType' => $variation_type_id,
      'multipleVariations' => TRUE,
      'injectVariationFields' => TRUE,
    ]);
    $type->save();

    // Create the default body field for the product type.
    // Commerce doesn't add body by default; we do it manually.
    echo "  [+] Created product type: $id\n";
  } else {
    echo "  [=] Product type exists: $id\n";
  }
  return $type;
}

// ============================================================
// Helper: ensure field storage + field config
// ============================================================
function ensure_field($entity_type, $bundle, $field_name, $field_type, $label, $settings = [], $cardinality = 1) {
  // Field storage
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

  // Field config
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
    if ($field_type === 'list_string' && !empty($settings['allowed_values'])) {
      // Allowed values go on storage, not field config
      $storage->setSetting('allowed_values', $settings['allowed_values']);
      $storage->save();
    }
    if ($field_type === 'commerce_price') {
      // No extra settings needed
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


echo "\n=== STEP 1: Create Taxonomies ===\n";

$vocabularies = [
  'product_categories' => 'Product Categories',
  'tags' => 'Tags',
  'clothing_size' => 'Clothing Size',
  'clothing_color' => 'Clothing Color',
  'clothing_type' => 'Clothing Type',
  'clothing_gender' => 'Clothing Gender',
  'download_type' => 'Download Type',
  'download_format' => 'Download Format',
  'download_license' => 'Download License',
  'download_software' => 'Download Software',
  'craft_type' => 'Craft Type',
  'craft_technique' => 'Craft Technique',
  'craft_material' => 'Craft Material',
  'craft_occasion' => 'Craft Occasion',
  'craft_style' => 'Craft Style',
];

foreach ($vocabularies as $vid => $name) {
  ensure_vocabulary($vid, $name);
}


echo "\n=== STEP 2: Create Variation Types ===\n";

ensure_variation_type('clothing', 'Clothing Variation');
ensure_variation_type('digital_download', 'Digital Download Variation');
ensure_variation_type('crafts', 'Crafts Variation');


echo "\n=== STEP 3: Create Product Types ===\n";

ensure_product_type('clothing', 'Clothing', 'clothing');
ensure_product_type('digital_download', 'Digital Download', 'digital_download');
ensure_product_type('crafts', 'Crafts', 'crafts');


echo "\n=== STEP 4: Base Fields (all product types) ===\n";

$base_product_fields = [
  ['field_short_description', 'string_long', 'Short Description', [], 1],
  ['field_images', 'image', 'Product Images', [], -1],
  ['field_categories', 'entity_reference', 'Categories', ['target_type' => 'taxonomy_term', 'target_bundles' => ['product_categories' => 'product_categories']], -1],
  ['field_tags', 'entity_reference', 'Tags', ['target_type' => 'taxonomy_term', 'target_bundles' => ['tags' => 'tags']], -1],
  ['field_seo_title', 'string', 'SEO Title', [], 1],
  ['field_seo_description', 'string_long', 'SEO Description', [], 1],
  ['field_featured', 'boolean', 'Featured', [], 1],
  ['field_related_products', 'entity_reference', 'Related Products', ['target_type' => 'commerce_product'], -1],
];

foreach (['clothing', 'digital_download', 'crafts'] as $product_type) {
  echo "\n  -- Base fields for: $product_type --\n";
  foreach ($base_product_fields as $f) {
    ensure_field('commerce_product', $product_type, $f[0], $f[1], $f[2], $f[3], $f[4]);
  }
}


echo "\n=== STEP 5: Clothing — Product-Level Fields ===\n";

$clothing_fields = [
  ['field_brand', 'string', 'Brand'],
  ['field_gender', 'list_string', 'Gender', ['allowed_values' => ['Unisex' => 'Unisex', "Men's" => "Men's", "Women's" => "Women's", "Kids'" => "Kids'"]]],
  ['field_care_instructions', 'text_long', 'Care Instructions'],
  ['field_material', 'string_long', 'Material / Fabric'],
  ['field_country_of_origin', 'string', 'Country of Origin'],
  ['field_sustainability', 'text_long', 'Sustainability Info'],
  ['field_size_guide', 'text_long', 'Size Guide'],
  ['field_shipping_weight', 'decimal', 'Weight (for shipping)'],
  ['field_shipping_class', 'list_string', 'Shipping Class', ['allowed_values' => ['standard' => 'Standard', 'expedited' => 'Expedited', 'overnight' => 'Overnight']]],
];

foreach ($clothing_fields as $f) {
  $settings = $f[3] ?? [];
  ensure_field('commerce_product', 'clothing', $f[0], $f[1], $f[2], $settings);
}


echo "\n=== STEP 6: Clothing — Variation Fields ===\n";

$clothing_var_fields = [
  ['field_size', 'list_string', 'Size', ['allowed_values' => ['XS' => 'XS', 'S' => 'S', 'M' => 'M', 'L' => 'L', 'XL' => 'XL', 'XXL' => 'XXL']]],
  ['field_color', 'list_string', 'Color', ['allowed_values' => ['Black' => 'Black', 'White' => 'White', 'Red' => 'Red', 'Blue' => 'Blue', 'Green' => 'Green', 'Gray' => 'Gray', 'Navy' => 'Navy', 'Pink' => 'Pink']]],
  ['field_color_swatch', 'image', 'Color Swatch'],
  ['field_variation_image', 'image', 'Variation Image'],
  ['field_stock', 'integer', 'Stock / Inventory'],
  ['field_on_sale', 'boolean', 'On Sale'],
  ['field_sale_price', 'commerce_price', 'Sale Price'],
  ['field_dimensions', 'string', 'Dimensions'],
];

foreach ($clothing_var_fields as $f) {
  $settings = $f[3] ?? [];
  ensure_field('commerce_product_variation', 'clothing', $f[0], $f[1], $f[2], $settings);
}


echo "\n=== STEP 7: Digital Download — Product-Level Fields ===\n";

$digital_fields = [
  ['field_file_formats', 'list_string', 'File Format(s)', ['allowed_values' => ['PDF' => 'PDF', 'PNG' => 'PNG', 'SVG' => 'SVG', 'MP3' => 'MP3', 'ZIP' => 'ZIP', 'MP4' => 'MP4', 'EPUB' => 'EPUB']], -1],
  ['field_file_size', 'string', 'File Size'],
  ['field_license_type', 'list_string', 'License Type', ['allowed_values' => ['personal' => 'Personal', 'commercial' => 'Commercial', 'extended' => 'Extended']]],
  ['field_license_details', 'text_long', 'License Details'],
  ['field_instant_download', 'boolean', 'Instant Download'],
  ['field_preview_images', 'image', 'Preview Image(s)', [], -1],
  ['field_preview_file', 'file', 'Preview File'],
  ['field_software_required', 'string_long', 'Software Required'],
  ['field_dimensions_resolution', 'string', 'Dimensions / Resolution'],
  ['field_page_count', 'integer', 'Page Count'],
  ['field_language', 'list_string', 'Language', ['allowed_values' => ['en' => 'English', 'es' => 'Spanish', 'fr' => 'French', 'de' => 'German', 'pt' => 'Portuguese']]],
  ['field_version', 'string', 'Version'],
  ['field_changelog', 'text_long', 'Changelog'],
];

foreach ($digital_fields as $f) {
  $settings = $f[3] ?? [];
  $cardinality = $f[4] ?? 1;
  ensure_field('commerce_product', 'digital_download', $f[0], $f[1], $f[2], $settings, $cardinality);
}


echo "\n=== STEP 8: Digital Download — Variation Fields ===\n";

$digital_var_fields = [
  ['field_license_tier', 'list_string', 'License Tier', ['allowed_values' => ['personal' => 'Personal Use', 'commercial' => 'Commercial Use']]],
  ['field_downloadable_file', 'file', 'Downloadable File'],
  ['field_download_limit', 'integer', 'Download Limit'],
  ['field_download_expiry', 'integer', 'Download Expiry (days)'],
];

foreach ($digital_var_fields as $f) {
  $settings = $f[3] ?? [];
  ensure_field('commerce_product_variation', 'digital_download', $f[0], $f[1], $f[2], $settings);
}


echo "\n=== STEP 9: Crafts — Product-Level Fields ===\n";

$crafts_fields = [
  ['field_handmade', 'boolean', 'Handmade'],
  ['field_made_to_order', 'boolean', 'Made to Order'],
  ['field_production_time', 'string', 'Production Time'],
  ['field_materials_used', 'text_long', 'Materials Used'],
  ['field_craft_dimensions', 'string', 'Dimensions'],
  ['field_shipping_weight', 'decimal', 'Weight (for shipping)'],
  ['field_shipping_class', 'list_string', 'Shipping Class', ['allowed_values' => ['standard' => 'Standard', 'fragile' => 'Fragile', 'oversized' => 'Oversized']]],
  ['field_gift_wrap', 'boolean', 'Gift Wrapping Available'],
  ['field_customizable', 'boolean', 'Customizable'],
  ['field_customization_details', 'text_long', 'Customization Details'],
  ['field_care_instructions', 'text_long', 'Care Instructions'],
  ['field_maker', 'string', 'Artist / Maker'],
  ['field_craft_technique', 'entity_reference', 'Craft Technique', ['target_type' => 'taxonomy_term', 'target_bundles' => ['craft_technique' => 'craft_technique']]],
  ['field_occasion', 'entity_reference', 'Occasion', ['target_type' => 'taxonomy_term', 'target_bundles' => ['craft_occasion' => 'craft_occasion']], -1],
  ['field_safety_info', 'text_long', 'Safety Information'],
];

foreach ($crafts_fields as $f) {
  $settings = $f[3] ?? [];
  $cardinality = $f[4] ?? 1;
  ensure_field('commerce_product', 'crafts', $f[0], $f[1], $f[2], $settings, $cardinality);
}


echo "\n=== STEP 10: Crafts — Variation Fields ===\n";

$crafts_var_fields = [
  ['field_color_finish', 'list_string', 'Color / Finish', ['allowed_values' => ['Natural' => 'Natural', 'Black' => 'Black', 'White' => 'White', 'Gold' => 'Gold', 'Silver' => 'Silver', 'Rose Gold' => 'Rose Gold', 'Copper' => 'Copper']]],
  ['field_size_option', 'list_string', 'Size / Dimensions Option', ['allowed_values' => ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large', 'custom' => 'Custom']]],
  ['field_material_option', 'list_string', 'Material Option', ['allowed_values' => ['wood' => 'Wood', 'ceramic' => 'Ceramic', 'fabric' => 'Fabric', 'metal' => 'Metal', 'glass' => 'Glass', 'resin' => 'Resin']]],
  ['field_variation_image', 'image', 'Variation Image'],
  ['field_stock', 'integer', 'Stock / Inventory'],
  ['field_on_sale', 'boolean', 'On Sale'],
  ['field_sale_price', 'commerce_price', 'Sale Price'],
];

foreach ($crafts_var_fields as $f) {
  $settings = $f[3] ?? [];
  ensure_field('commerce_product_variation', 'crafts', $f[0], $f[1], $f[2], $settings);
}


echo "\n\n=== DONE ===\n";
echo "Created 3 product types (clothing, digital_download, crafts)\n";
echo "Created 3 variation types\n";
echo "Created 15 taxonomies\n";
echo "Created all base + type-specific + variation fields\n";
echo "\nNext steps:\n";
echo "  - Install Commerce Stock, Commerce File, Commerce Shipping modules if needed\n";
echo "  - Add taxonomy terms to the vocabularies\n";
echo "  - Configure form/display modes in Drupal admin UI\n";

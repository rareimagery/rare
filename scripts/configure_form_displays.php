<?php

/**
 * Configure form displays so all fields show on node/store edit forms.
 * Run via: drush php:script configure_form_displays.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

// ============================================================
// Helper: add a field to a form display with a widget
// ============================================================
function add_to_form($entity_type, $bundle, $field_name, $widget_type, $weight) {
  $form_display = EntityFormDisplay::load("$entity_type.$bundle.default");
  if (!$form_display) {
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => $entity_type,
      'bundle' => $bundle,
      'mode' => 'default',
      'status' => TRUE,
    ]);
  }

  $current = $form_display->getComponent($field_name);
  if ($current) {
    echo "  [=] Already on form: $entity_type.$bundle.$field_name\n";
    return;
  }

  $form_display->setComponent($field_name, [
    'type' => $widget_type,
    'weight' => $weight,
    'settings' => [],
  ]);
  $form_display->save();
  echo "  [+] Added to form: $entity_type.$bundle.$field_name (widget: $widget_type)\n";
}


echo "\n=== Creator X Profile — Form Display ===\n";

$profile_fields = [
  ['field_x_username',            'string_textfield',       1],
  ['field_bio_description',       'text_textarea',          2],
  ['field_follower_count',        'number',                 3],
  ['field_profile_picture',       'image_image',            4],
  ['field_background_banner',     'image_image',            5],
  ['field_top_posts',             'text_textarea',          6],
  ['field_top_followers',         'text_textarea',          7],
  ['field_metrics',               'text_textarea',          8],
  ['field_store_theme',           'string_textfield',       9],
  ['field_myspace_accent_color',  'string_textfield',       10],
  ['field_myspace_glitter_color', 'string_textfield',       11],
  ['field_myspace_background',    'string_textfield',       12],
  ['field_myspace_music_url',     'string_textfield',       13],
  ['field_linked_store',          'entity_reference_autocomplete', 14],
];

foreach ($profile_fields as $f) {
  add_to_form('node', 'creator_x_profile', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Store (online) — Form Display ===\n";

$store_fields = [
  ['field_store_slug',            'string_textfield',       10],
  ['field_store_theme',           'string_textarea',        11],
  ['field_linked_x_profile',     'entity_reference_autocomplete', 12],
];

foreach ($store_fields as $f) {
  add_to_form('commerce_store', 'online', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Product (default) — Form Display ===\n";

$default_product_fields = [
  ['field_short_description',     'string_textarea',        10],
  ['field_images',                'image_image',            11],
  ['field_categories',            'entity_reference_autocomplete', 12],
  ['field_tags',                  'entity_reference_autocomplete', 13],
  ['field_seo_title',             'string_textfield',       14],
  ['field_seo_description',       'string_textarea',        15],
  ['field_featured',              'boolean_checkbox',       16],
  ['field_related_products',      'entity_reference_autocomplete', 17],
];

foreach ($default_product_fields as $f) {
  add_to_form('commerce_product', 'default', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Product (clothing) — Form Display ===\n";

$clothing_product_fields = array_merge($default_product_fields, [
  ['field_brand',                 'string_textfield',       20],
  ['field_gender',                'options_select',         21],
  ['field_care_instructions',     'text_textarea',          22],
  ['field_material',              'string_textarea',        23],
  ['field_country_of_origin',     'string_textfield',       24],
  ['field_sustainability',        'text_textarea',          25],
  ['field_size_guide',            'text_textarea',          26],
  ['field_shipping_weight',       'number',                 27],
  ['field_shipping_class',        'options_select',         28],
]);

foreach ($clothing_product_fields as $f) {
  add_to_form('commerce_product', 'clothing', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Product Variation (clothing) — Form Display ===\n";

$clothing_var_fields = [
  ['field_size',                  'options_select',         10],
  ['field_color',                 'options_select',         11],
  ['field_color_swatch',          'image_image',            12],
  ['field_variation_image',       'image_image',            13],
  ['field_stock',                 'number',                 14],
  ['field_on_sale',               'boolean_checkbox',       15],
  ['field_sale_price',            'commerce_unit_price',    16],
  ['field_dimensions',            'string_textfield',       17],
];

foreach ($clothing_var_fields as $f) {
  add_to_form('commerce_product_variation', 'clothing', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Product (digital_download) — Form Display ===\n";

$digital_product_fields = array_merge($default_product_fields, [
  ['field_file_formats',          'options_buttons',        20],
  ['field_file_size',             'string_textfield',       21],
  ['field_license_type',          'options_select',         22],
  ['field_license_details',       'text_textarea',          23],
  ['field_instant_download',      'boolean_checkbox',       24],
  ['field_preview_images',        'image_image',            25],
  ['field_preview_file',          'file_generic',           26],
  ['field_software_required',     'string_textarea',        27],
  ['field_dimensions_resolution', 'string_textfield',       28],
  ['field_page_count',            'number',                 29],
  ['field_language',              'options_select',          30],
  ['field_version',               'string_textfield',       31],
  ['field_changelog',             'text_textarea',          32],
]);

foreach ($digital_product_fields as $f) {
  add_to_form('commerce_product', 'digital_download', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Product Variation (digital_download) — Form Display ===\n";

$digital_var_fields = [
  ['field_license_tier',          'options_select',         10],
  ['field_downloadable_file',     'file_generic',           11],
  ['field_download_limit',        'number',                 12],
  ['field_download_expiry',       'number',                 13],
];

foreach ($digital_var_fields as $f) {
  add_to_form('commerce_product_variation', 'digital_download', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Product (crafts) — Form Display ===\n";

$crafts_product_fields = array_merge($default_product_fields, [
  ['field_handmade',              'boolean_checkbox',       20],
  ['field_made_to_order',         'boolean_checkbox',       21],
  ['field_production_time',       'string_textfield',       22],
  ['field_materials_used',        'text_textarea',          23],
  ['field_craft_dimensions',      'string_textfield',       24],
  ['field_shipping_weight',       'number',                 25],
  ['field_shipping_class',        'options_select',         26],
  ['field_gift_wrap',             'boolean_checkbox',       27],
  ['field_customizable',          'boolean_checkbox',       28],
  ['field_customization_details', 'text_textarea',          29],
  ['field_care_instructions',     'text_textarea',          30],
  ['field_maker',                 'string_textfield',       31],
  ['field_craft_technique',       'entity_reference_autocomplete', 32],
  ['field_occasion',              'entity_reference_autocomplete', 33],
  ['field_safety_info',           'text_textarea',          34],
]);

foreach ($crafts_product_fields as $f) {
  add_to_form('commerce_product', 'crafts', $f[0], $f[1], $f[2]);
}


echo "\n=== Commerce Product Variation (crafts) — Form Display ===\n";

$crafts_var_fields = [
  ['field_color_finish',          'options_select',         10],
  ['field_size_option',           'options_select',         11],
  ['field_material_option',       'options_select',         12],
  ['field_variation_image',       'image_image',            13],
  ['field_stock',                 'number',                 14],
  ['field_on_sale',               'boolean_checkbox',       15],
  ['field_sale_price',            'commerce_unit_price',    16],
];

foreach ($crafts_var_fields as $f) {
  add_to_form('commerce_product_variation', 'crafts', $f[0], $f[1], $f[2]);
}


echo "\n=== User Profile — Form Display ===\n";

$user_fields = [
  ['field_store',                 'entity_reference_autocomplete', 20],
  ['field_shop_name',             'string_textfield',       21],
  ['field_shop_logo',             'image_image',            22],
  ['field_bio',                   'text_textarea',          23],
  ['field_phone',                 'telephone_default',      24],
  ['field_social_links',          'link_default',           25],
  ['field_payout_info',           'string_textarea',        26],
  ['field_onboarding_complete',   'boolean_checkbox',       27],
  ['field_admin_notes',           'text_textarea',          28],
];

foreach ($user_fields as $f) {
  add_to_form('user', 'user', $f[0], $f[1], $f[2]);
}


echo "\n\n=== DONE ===\n";
echo "All fields are now visible on edit forms.\n";
echo "Visit /node/1/edit to see creator_x_profile fields.\n";
echo "Visit /admin/commerce/config/stores to see store fields.\n";

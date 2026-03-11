<?php

/**
 * Update image field settings: increase upload limit and allow more file types.
 * Run via: drush php:script update_image_settings.php
 */

use Drupal\field\Entity\FieldConfig;

$image_fields = [
  ['node', 'creator_x_profile', 'field_profile_picture'],
  ['node', 'creator_x_profile', 'field_background_banner'],
  ['commerce_product', 'default', 'field_images'],
  ['commerce_product', 'clothing', 'field_images'],
  ['commerce_product', 'digital_download', 'field_images'],
  ['commerce_product', 'digital_download', 'field_preview_images'],
  ['commerce_product', 'crafts', 'field_images'],
  ['commerce_product_variation', 'clothing', 'field_color_swatch'],
  ['commerce_product_variation', 'clothing', 'field_variation_image'],
  ['commerce_product_variation', 'crafts', 'field_variation_image'],
  ['user', 'user', 'field_shop_logo'],
];

echo "\n=== Updating Image Field Settings ===\n\n";

foreach ($image_fields as [$entity_type, $bundle, $field_name]) {
  $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
  if (!$field) {
    echo "  [!] Not found: $entity_type.$bundle.$field_name\n";
    continue;
  }

  // Max upload size: 20 MB
  $field->setSetting('max_filesize', '20 MB');

  // Allowed extensions: common web image formats
  $field->setSetting('file_extensions', 'png jpg jpeg gif webp svg avif bmp tiff');

  // Min/max resolution (optional, remove constraints)
  $field->setSetting('max_resolution', '');
  $field->setSetting('min_resolution', '');

  $field->save();
  echo "  [+] Updated: $entity_type.$bundle.$field_name → 20 MB, png/jpg/jpeg/gif/webp/svg/avif/bmp/tiff\n";
}

// Also update PHP upload limits in Drupal settings
echo "\n=== Checking PHP upload limits ===\n";
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
echo "  upload_max_filesize: $upload_max\n";
echo "  post_max_size: $post_max\n";

if (intval($upload_max) < 20) {
  echo "  [!] WARNING: PHP upload_max_filesize ($upload_max) is less than 20M.\n";
  echo "      Update php.ini or .htaccess: upload_max_filesize = 20M\n";
}
if (intval($post_max) < 25) {
  echo "  [!] WARNING: PHP post_max_size ($post_max) is less than 25M.\n";
  echo "      Update php.ini or .htaccess: post_max_size = 25M\n";
}

echo "\n=== DONE ===\n";

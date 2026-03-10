<?php

/**
 * Generate placeholder images and attach them to all demo products.
 * Run via: drush php:script add_product_images.php
 */

use Drupal\file\Entity\File;
use Drupal\commerce_product\Entity\Product;

// Color palettes per creator (background hex)
$creator_colors = [
  'elonmusk'     => ['#1a1a2e', '#16213e', '#0f3460', '#533483', '#e94560', '#2b2d42'],
  'alphafox'     => ['#0d1b2a', '#1b263b', '#415a77', '#778da9', '#2d6a4f', '#40916c'],
  'clownworld'   => ['#ff006e', '#fb5607', '#ffbe0b', '#8338ec', '#3a86a7', '#ff595e'],
  'ksjcreative'  => ['#7400b8', '#6930c3', '#5e60ce', '#4ea8de', '#48bfe3', '#56cfe1'],
  'rareimagery'  => ['#212529', '#495057', '#6c757d', '#343a40', '#1b1b1b', '#2c3e50'],
];

// Ensure the products directory exists
$file_system = \Drupal::service('file_system');
$dir = 'public://product-images';
$file_system->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

echo "\n=== Adding Placeholder Images to Demo Products ===\n\n";

$total = 0;

foreach ($creator_colors as $slug => $colors) {
  // Find the store
  $stores = \Drupal::entityTypeManager()
    ->getStorage('commerce_store')
    ->loadByProperties(['field_store_slug' => $slug]);

  if (empty($stores)) {
    echo "  [!] No store for $slug — skipping\n";
    continue;
  }

  $store = reset($stores);
  $store_id = $store->id();

  // Load all products for this store
  $product_ids = \Drupal::entityTypeManager()
    ->getStorage('commerce_product')
    ->getQuery()
    ->condition('stores', $store_id)
    ->accessCheck(FALSE)
    ->execute();

  $products = Product::loadMultiple($product_ids);

  echo "=== $slug (" . count($products) . " products) ===\n";

  $i = 0;
  foreach ($products as $product) {
    $title = $product->getTitle();

    // Check if product already has images
    if ($product->hasField('field_images') && !$product->get('field_images')->isEmpty()) {
      echo "  [=] Already has image: $title\n";
      continue;
    }

    // Pick a color from the palette
    $bg_hex = $colors[$i % count($colors)];

    // Generate 2 images per product (main + alternate angle)
    $file_ids = [];
    for ($img = 0; $img < 2; $img++) {
      $filename = preg_replace('/[^a-z0-9]/', '-', strtolower($slug . '-' . $title)) . '-' . ($img + 1) . '.png';
      $filepath = $dir . '/' . $filename;

      // Create image with GD
      $width = 800;
      $height = 800;
      $image = imagecreatetruecolor($width, $height);

      // Parse background color
      $r = hexdec(substr($bg_hex, 1, 2));
      $g = hexdec(substr($bg_hex, 3, 2));
      $b = hexdec(substr($bg_hex, 5, 2));
      $bg = imagecolorallocate($image, $r, $g, $b);
      imagefill($image, 0, 0, $bg);

      // Add some visual interest — gradient overlay
      for ($y = 0; $y < $height; $y++) {
        $alpha = (int)($y / $height * 60);
        $overlay = imagecolorallocatealpha($image, 255, 255, 255, 127 - $alpha);
        imageline($image, 0, $y, $width, $y, $overlay);
      }

      // Add decorative shapes
      $accent = imagecolorallocatealpha($image, 255, 255, 255, 90);
      if ($img === 0) {
        // Main image: large centered circle
        imagefilledellipse($image, $width / 2, $height / 2, 300, 300, $accent);
        // Corner accents
        imagefilledellipse($image, 50, 50, 100, 100, $accent);
        imagefilledellipse($image, $width - 50, $height - 50, 100, 100, $accent);
      } else {
        // Alternate: diagonal stripes
        for ($s = -$height; $s < $width + $height; $s += 80) {
          imageline($image, $s, 0, $s + $height, $height, $accent);
        }
      }

      // Add product title text
      $white = imagecolorallocate($image, 255, 255, 255);
      $text_lines = wordwrap($title, 20, "\n", true);
      $lines = explode("\n", $text_lines);
      $line_height = 24;
      $start_y = ($height / 2) - (count($lines) * $line_height / 2);

      foreach ($lines as $li => $line) {
        $line = trim($line);
        // Use built-in font 5 (largest built-in)
        $text_width = imagefontwidth(5) * strlen($line);
        $x = ($width - $text_width) / 2;
        $y = $start_y + ($li * $line_height);
        // Shadow
        imagestring($image, 5, $x + 2, $y + 2, $line, imagecolorallocate($image, 0, 0, 0));
        imagestring($image, 5, $x, $y, $line, $white);
      }

      // Add store name at bottom
      $store_text = "@$slug";
      $sw = imagefontwidth(4) * strlen($store_text);
      imagestring($image, 4, ($width - $sw) / 2, $height - 40, $store_text, imagecolorallocatealpha($image, 255, 255, 255, 60));

      // Save to temp, then move to Drupal
      $tmp = tempnam(sys_get_temp_dir(), 'img');
      imagepng($image, $tmp);
      imagedestroy($image);

      // Move file into Drupal
      $data = file_get_contents($tmp);
      $file = \Drupal::service('file.repository')->writeData($data, $filepath, \Drupal\Core\File\FileExists::Replace);
      unlink($tmp);

      if ($file) {
        $file->setPermanent();
        $file->save();
        $file_ids[] = ['target_id' => $file->id(), 'alt' => $title . ($img > 0 ? ' - view ' . ($img + 1) : '')];
      }
    }

    if (!empty($file_ids) && $product->hasField('field_images')) {
      $product->set('field_images', $file_ids);
      $product->save();
      echo "  [+] Added " . count($file_ids) . " images: $title\n";
      $total += count($file_ids);
    } else {
      echo "  [!] Could not add images: $title\n";
    }

    $i++;
  }
}

echo "\n=== DONE === Added $total images total.\n";

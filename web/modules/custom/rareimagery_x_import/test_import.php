<?php

/**
 * @file
 * Test script: run with `drush php:script modules/custom/rareimagery_x_import/test_import.php`
 */

$x_api = \Drupal::service('rareimagery_x_import.x_api');
$grok = \Drupal::service('rareimagery_x_import.grok');

$usernames = ['elonmusk', 'alphafox', 'clownworld', 'doctorclownphd', 'ksjcreative'];

$mock_profiles = [
  'elonmusk' => [
    'name' => 'Elon Musk',
    'bio' => 'CEO of Tesla, SpaceX, xAI. Technoking.',
    'followers' => 195000000,
    'metrics' => ['engagement_rate' => 0.08, 'avg_likes' => 250000],
    'top_posts' => [['text' => 'The future is electric', 'likes' => 500000]],
    'followers_sample' => [['username' => 'jack', 'name' => 'Jack']],
  ],
  'alphafox' => [
    'name' => 'Alpha Fox',
    'bio' => 'Epic pranks & outdoor adventures.',
    'followers' => 2400000,
    'metrics' => ['engagement_rate' => 0.12, 'avg_likes' => 45000],
    'top_posts' => [['text' => 'New prank video just dropped', 'likes' => 80000]],
    'followers_sample' => [],
  ],
  'clownworld' => [
    'name' => 'Clown World',
    'bio' => 'Honk honk. Political satire & memes.',
    'followers' => 850000,
    'metrics' => ['engagement_rate' => 0.15, 'avg_likes' => 30000],
    'top_posts' => [['text' => 'The circus never ends', 'likes' => 60000]],
    'followers_sample' => [],
  ],
  'doctorclownphd' => [
    'name' => 'Doctor Clown PhD',
    'bio' => 'Certified clown doctor. Laughter is medicine.',
    'followers' => 320000,
    'metrics' => ['engagement_rate' => 0.10, 'avg_likes' => 8000],
    'top_posts' => [['text' => 'Prescription: more honking', 'likes' => 15000]],
    'followers_sample' => [],
  ],
  'ksjcreative' => [
    'name' => 'KSJ Creative',
    'bio' => 'Digital agency. Design, code, innovate.',
    'followers' => 45000,
    'metrics' => ['engagement_rate' => 0.06, 'avg_likes' => 1200],
    'top_posts' => [['text' => 'New project launch', 'likes' => 3000]],
    'followers_sample' => [],
  ],
];

echo "=== Creating 5 Demo Creator X Profiles + Stores + Products ===\n\n";

$fake_products = [
  'elonmusk' => [
    ['title' => 'Tesla Cybertruck Blueprint (Digital Print)', 'price' => 29.99, 'desc' => 'High-res printable blueprint. Own the future.'],
    ['title' => 'Grok AI Prompt Pack', 'price' => 9.99, 'desc' => '50 truth-seeking prompts for your own Grok.'],
    ['title' => 'Mars Colony NFT', 'price' => 199.99, 'desc' => 'Limited edition digital land on Mars.'],
  ],
  'alphafox' => [
    ['title' => 'Prank Kit Digital Guide', 'price' => 14.99, 'desc' => '10 epic pranks – step-by-step videos.'],
    ['title' => 'Tannerite Safety Poster Pack', 'price' => 4.99, 'desc' => 'Humorous safety prints.'],
    ['title' => 'Marshmallow Egg Recipe eBook', 'price' => 7.99, 'desc' => 'Ultimate prank recipe collection.'],
  ],
  'clownworld' => [
    ['title' => 'Clown World Meme NFT Pack', 'price' => 19.99, 'desc' => '10 viral memes as collectibles.'],
    ['title' => 'Election Circus Print', 'price' => 12.99, 'desc' => '40% Dem sweep poster.'],
    ['title' => '🤡 Daily Dose Sticker Sheet (Digital)', 'price' => 5.99, 'desc' => 'Printable clown stickers.'],
  ],
  'doctorclownphd' => [
    ['title' => 'Laughter Medicine Prescription (Digital)', 'price' => 8.99, 'desc' => 'Personalized clown doctor cert.'],
    ['title' => '🤡 Health Meme Pack', 'price' => 4.99, 'desc' => 'Funny health advice images.'],
  ],
  'ksjcreative' => [
    ['title' => 'Creative Project Template Pack', 'price' => 24.99, 'desc' => '5 Canva/Figma templates.'],
    ['title' => 'Digital Innovation eBook', 'price' => 12.99, 'desc' => 'Agency secrets.'],
  ],
];

foreach ($usernames as $username) {
  echo "\n--- Processing @{$username} ---\n";

  $mock = $mock_profiles[$username] ?? $mock_profiles['ksjcreative'];

  // 1. Create Profile
  $profile = \Drupal::entityTypeManager()->getStorage('node')->create([
    'type' => 'creator_x_profile',
    'title' => $mock['name'] . ' Store Profile',
    'status' => 1,
    'field_x_username' => $username,
    'field_bio_description' => ['value' => '<p>' . htmlspecialchars($mock['bio']) . '</p>', 'format' => 'basic_html'],
    'field_follower_count' => $mock['followers'],
    'field_top_posts' => array_map(fn($post) => ['value' => json_encode($post)], $mock['top_posts'] ?? []),
    'field_top_followers' => array_map(fn($f) => ['value' => json_encode($f)], $mock['followers_sample'] ?? []),
    'field_metrics' => ['value' => json_encode($mock['metrics'])],
  ]);
  $profile->save();
  $profile_id = $profile->id();
  echo "Profile ID: {$profile_id}\n";

  // 2. Create Store
  $store = \Drupal::entityTypeManager()->getStorage('commerce_store')->create([
    'type' => 'default',  // or 'creator_store'
    'name' => $mock['name'] . ' Creator Store',
    'mail' => $username . '@rareimagery.net',
    'field_store_slug' => $username,
    'field_linked_x_profile' => ['target_id' => $profile_id],
    'status' => 1,
  ]);
  $store->save();
  $store_id = $store->id();
  echo "Store ID: {$store_id}\n";

  // Link back
  $profile->set('field_linked_store', ['target_id' => $store_id]);
  $profile->save();

  // 3. Create Products
  foreach ($fake_products[$username] ?? [['title' => 'Demo Product', 'price' => 9.99, 'desc' => 'Test item']] as $prod) {
    $product = \Drupal::entityTypeManager()->getStorage('commerce_product')->create([
      'type' => 'default',  // or 'digital'
      'title' => $prod['title'],
      'stores' => [$store_id],
      'body' => ['value' => '<p>' . $prod['desc'] . '</p>'],
    ]);
    $product->save();
    echo "  Product: " . $prod['title'] . "\n";
  }
}

echo "\n=== FULL BUILD COMPLETE: 5 Profiles + Stores + Products ===\n";
echo "Test: /stores/elonmusk (add products to cart!)\n";

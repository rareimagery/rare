<?php

/**
 * @file
 * Test script: run with `drush php:script modules/custom/rareimagery_x_import/test_import.php`
 */

$x_api = \Drupal::service('rareimagery_x_import.x_api');
$grok = \Drupal::service('rareimagery_x_import.grok');

$usernames = ['elonmusk', 'alphafox', 'clownworld', 'doctorclownphd', 'ksjcreative'];

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

// If we got a real profile, continue with live data
echo "   Name: " . $profile['name'] . "\n";
echo "   Bio: " . ($profile['description'] ?? 'N/A') . "\n";
echo "   Followers: " . ($profile['public_metrics']['followers_count'] ?? 'N/A') . "\n";
$user_id = $profile['id'];

echo "\n2. Fetching tweets...\n";
$tweets = $x_api->getUserTweets($user_id, 20);
echo "   Got " . count($tweets) . " tweets\n";

echo "\n3. Fetching followers...\n";
$followers = $x_api->getUserFollowers($user_id, 8);
echo "   Got " . count($followers) . " followers\n";

$xai_key = getenv('XAI_API_KEY');
$grok_analysis = NULL;
if ($xai_key && $xai_key !== 'your_xai_key_here' && !empty($tweets)) {
  echo "\n4. Running Grok analysis...\n";
  $grok_analysis = $grok->analyzeProfile($profile, $tweets);
  if ($grok_analysis) {
    echo "   Engagement score: " . ($grok_analysis['engagement_score'] ?? 'N/A') . "\n";
    echo "   Audience quality: " . ($grok_analysis['audience_quality'] ?? 'N/A') . "\n";
  }
  else {
    echo "   Grok analysis returned null\n";
  }
}
else {
  echo "\n4. Skipping Grok (no valid XAI_API_KEY)\n";
}

// Download PFP
$pfp_file = NULL;
$pfp_url = str_replace('_normal', '_400x400', $profile['profile_image_url'] ?? '');
if (!empty($pfp_url)) {
  try {
    $response = \Drupal::httpClient()->request('GET', $pfp_url, ['timeout' => 15]);
    $data = $response->getBody()->getContents();
    $dir = 'public://creator-pfps';
    \Drupal::service('file_system')->prepareDirectory($dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);
    $pfp_file = \Drupal::service('file.repository')->writeData($data, $dir . '/' . $username . '-pfp.jpg', \Drupal\Core\File\FileSystemInterface::EXISTS_REPLACE);
    echo "\n5. PFP downloaded\n";
  }
  catch (\Exception $e) {
    echo "\n5. PFP download failed: " . $e->getMessage() . "\n";
  }
}

// Build top posts
$top_posts = [];
if (!empty($grok_analysis['top_posts'])) {
  foreach (array_slice($grok_analysis['top_posts'], 0, 8) as $post) {
    $top_posts[] = ['value' => json_encode($post)];
  }
}
elseif (!empty($tweets)) {
  usort($tweets, function ($a, $b) {
    $sa = ($a['public_metrics']['like_count'] ?? 0) + ($a['public_metrics']['retweet_count'] ?? 0) * 2;
    $sb = ($b['public_metrics']['like_count'] ?? 0) + ($b['public_metrics']['retweet_count'] ?? 0) * 2;
    return $sb - $sa;
  });
  foreach (array_slice($tweets, 0, 8) as $tweet) {
    $top_posts[] = ['value' => json_encode([
      'text' => $tweet['text'],
      'likes' => $tweet['public_metrics']['like_count'] ?? 0,
      'retweets' => $tweet['public_metrics']['retweet_count'] ?? 0,
    ])];
  }
}

// Build followers
$top_followers = [];
foreach (array_slice($followers, 0, 8) as $f) {
  $top_followers[] = ['value' => json_encode([
    'username' => $f['username'],
    'name' => $f['name'],
    'avatar' => $f['profile_image_url'] ?? '',
    'followers' => $f['public_metrics']['followers_count'] ?? 0,
  ])];
}

// Create node
$node_data = [
  'type' => 'creator_x_profile',
  'title' => ($profile['name'] ?? $username) . ' - X Profile',
  'status' => 1,
  'field_x_username' => $username,
  'field_bio_description' => [
    'value' => '<p>' . htmlspecialchars($profile['description'] ?? '') . '</p>',
    'format' => 'basic_html',
  ],
  'field_follower_count' => $profile['public_metrics']['followers_count'] ?? 0,
];

if ($pfp_file) {
  $node_data['field_profile_picture'] = ['target_id' => $pfp_file->id(), 'alt' => $username . ' profile picture'];
}
if (!empty($top_posts)) {
  $node_data['field_top_posts'] = $top_posts;
}
if (!empty($top_followers)) {
  $node_data['field_top_followers'] = $top_followers;
}
if (!empty($grok_analysis)) {
  $node_data['field_metrics'] = ['value' => json_encode($grok_analysis)];
}

$node = \Drupal::entityTypeManager()->getStorage('node')->create($node_data);
$node->save();

echo "\n=== SUCCESS ===\n";
echo "Creator X Profile created! Node ID: " . $node->id() . "\n";
echo "View at: /node/" . $node->id() . "\n";

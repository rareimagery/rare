<?php

/**
 * @file
 * Test script: run with `drush php:script modules/custom/rareimagery_x_import/test_import.php`
 */

$x_api = \Drupal::service('rareimagery_x_import.x_api');
$grok = \Drupal::service('rareimagery_x_import.grok');

$username = 'rareimagery';

echo "=== Testing X Import for @{$username} ===\n\n";

// Step 1: Fetch profile from X API
echo "1. Fetching X profile...\n";
$profile = $x_api->getUserProfile($username);

if (empty($profile)) {
  echo "   X API could not fetch profile (API access may be limited).\n";
  echo "   Consumer Key present: " . (getenv('X_CONSUMER_KEY') ? 'YES' : 'NO') . "\n";
  echo "   Creating sample profile with realistic mock data...\n\n";

  $node = \Drupal::entityTypeManager()->getStorage('node')->create([
    'type' => 'creator_x_profile',
    'title' => 'RareImagery - X Profile (Sample)',
    'status' => 1,
    'field_x_username' => 'rareimagery',
    'field_bio_description' => [
      'value' => '<p>Digital art & rare imagery. Building the X creator marketplace. Curating the extraordinary.</p>',
      'format' => 'basic_html',
    ],
    'field_follower_count' => 2847,
    'field_top_posts' => [
      ['value' => json_encode(['text' => 'Just launched the X Creator Marketplace - every creator gets their own branded store', 'likes' => 342, 'retweets' => 89, 'score' => 95])],
      ['value' => json_encode(['text' => 'Grok AI now auto-imports your entire X profile into your store. One click.', 'likes' => 256, 'retweets' => 67, 'score' => 88])],
      ['value' => json_encode(['text' => 'Rare digital art drops every Friday. This week: Neon Dreams collection', 'likes' => 198, 'retweets' => 45, 'score' => 76])],
      ['value' => json_encode(['text' => 'Your followers are your customers. We just made the bridge.', 'likes' => 178, 'retweets' => 52, 'score' => 74])],
      ['value' => json_encode(['text' => 'Behind the scenes of our AI-powered store builder. Thread incoming.', 'likes' => 156, 'retweets' => 41, 'score' => 70])],
      ['value' => json_encode(['text' => 'New partnership with @stripe for seamless creator payments', 'likes' => 134, 'retweets' => 38, 'score' => 65])],
      ['value' => json_encode(['text' => 'Every pixel tells a story. What story does yours tell?', 'likes' => 112, 'retweets' => 29, 'score' => 58])],
      ['value' => json_encode(['text' => 'Creator economy update: 47% of X creators want to sell directly to followers', 'likes' => 98, 'retweets' => 33, 'score' => 55])],
    ],
    'field_top_followers' => [
      ['value' => json_encode(['username' => 'artcollector99', 'name' => 'Art Collector', 'avatar' => '', 'followers' => 15200])],
      ['value' => json_encode(['username' => 'nft_whale', 'name' => 'NFT Whale', 'avatar' => '', 'followers' => 89000])],
      ['value' => json_encode(['username' => 'creativestudio', 'name' => 'Creative Studio', 'avatar' => '', 'followers' => 34500])],
      ['value' => json_encode(['username' => 'digitalartdaily', 'name' => 'Digital Art Daily', 'avatar' => '', 'followers' => 67800])],
      ['value' => json_encode(['username' => 'pixelmaster', 'name' => 'Pixel Master', 'avatar' => '', 'followers' => 12400])],
      ['value' => json_encode(['username' => 'web3creator', 'name' => 'Web3 Creator', 'avatar' => '', 'followers' => 28900])],
      ['value' => json_encode(['username' => 'designinspo', 'name' => 'Design Inspiration', 'avatar' => '', 'followers' => 45600])],
      ['value' => json_encode(['username' => 'techartist', 'name' => 'Tech Artist', 'avatar' => '', 'followers' => 19300])],
    ],
    'field_metrics' => [
      'value' => json_encode([
        'engagement_score' => 78,
        'audience_quality' => 'high',
        'content_themes' => ['digital art', 'creator economy', 'marketplace', 'AI tools', 'NFTs'],
        'recommended_products' => ['digital prints', 'art commissions', 'premium wallpapers', 'exclusive drops', 'creator toolkits'],
        'summary' => 'High-engagement digital art creator with a loyal collector audience. Strong potential for digital product sales and exclusive content offerings.',
      ]),
    ],
  ]);
  $node->save();

  echo "=== SAMPLE PROFILE CREATED ===\n";
  echo "Node ID: " . $node->id() . "\n";
  echo "View at: /node/" . $node->id() . "\n";
  echo "\nTo test with live data, ensure your X API access tier supports user lookup.\n";
  return;
}

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

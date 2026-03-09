<?php

/**
 * @file
 * Test script: run with `drush php:script modules/custom/rareimagery_x_import/test_import.php`
 */

$x_api = \Drupal::service('rareimagery_x_import.x_api');
$grok = \Drupal::service('rareimagery_x_import.grok');

$usernames = ['elonmusk', 'alphafox', 'clownworld', 'doctorclownphd', 'ksjcreative'];

echo "=== Creating 5 Demo Creator X Profiles ===\n\n";

foreach ($usernames as $username) {
  echo "\n--- Processing @{$username} ---\n";

  // Mock profile data from X scrape
  $mock_profiles = [
    'elonmusk' => [
      'name' => 'Elon Musk',
      'bio' => 'CEO of Tesla, SpaceX, xAI. Building the future.',
      'followers' => 236200000,
      'top_posts' => [
        ['text' => 'Only Grok speaks the truth. Only truthful AI is safe.', 'likes' => 14000, 'retweets' => 13000],
        ['text' => 'Next I’m buying Coca-Cola to put the cocaine back in', 'likes' => 168000, 'retweets' => 755000],
        ['text' => 'The future is gonna be so 🔥 🇺🇸', 'likes' => 57000, 'retweets' => 212000],
      ],
      'followers_sample' => [
        ['username' => 'tesla', 'name' => 'Tesla', 'followers' => 20000000],
        ['username' => 'spacex', 'name' => 'SpaceX', 'followers' => 40000000],
      ],
      'metrics' => ['engagement_score' => 95, 'audience_quality' => 'elite', 'summary' => 'World\\'s biggest influencer. Massive buying power.'],
    ],
    'alphafox' => [
      'name' => 'AlphaFox',
      'bio' => 'X Fox 🦊✝️ Earth',
      'followers' => 830900,
      'top_posts' => [
        ['text' => 'Never empty your mag into a lawn mower filled with tannerite unless you don’t require legs 😨', 'likes' => 287, 'retweets' => 303],
        ['text' => 'Marshmallow egg prank 😬', 'likes' => 2500, 'retweets' => 15000],
      ],
      'followers_sample' => [
        ['username' => 'prankster1', 'name' => 'Prankster', 'followers' => 50000],
      ],
      'metrics' => ['engagement_score' => 85, 'audience_quality' => 'high', 'summary' => 'Viral prank content creator.'],
    ],
    'clownworld' => [
      'name' => 'Clown World ™ 🤡',
      'bio' => 'The circus never stops. Uploads daily. DM for credit 📩',
      'followers' => 3100000,
      'top_posts' => [
        ['text' => 'Markets now show a 40% chance of a Democratic sweep in 2028.', 'likes' => 391, 'retweets' => 41],
        ['text' => 'Thoughts? (image post)', 'likes' => 6300, 'retweets' => 24000],
      ],
      'followers_sample' => [
        ['username' => 'kalshi', 'name' => 'Kalshi', 'followers' => 100000],
      ],
      'metrics' => ['engagement_score' => 88, 'audience_quality' => 'engaged', 'summary' => 'Political meme powerhouse with own store.'],
    ],
    'doctorclownphd' => [
      'name' => 'Doctor 🤡',
      'bio' => 'Laughter is the best medicine 💊 🤡🤣',
      'followers' => 8,
      'top_posts' => [],
      'followers_sample' => [],
      'metrics' => ['engagement_score' => 10, 'audience_quality' => 'new', 'summary' => 'Emerging clown doctor content.'],
    ],
    'ksjcreative' => [
      'name' => 'KSJ Creative (Mock - Account not found)',
      'bio' => 'Creative agency for digital innovation.',
      'followers' => 5000,
      'top_posts' => [
        ['text' => 'New creative project launch!', 'likes' => 100, 'retweets' => 20],
      ],
      'followers_sample' => [],
      'metrics' => ['engagement_score' => 60, 'audience_quality' => 'medium', 'summary' => 'Mock creative profile.'],
    ],
  ];

  $mock = $mock_profiles[$username] ?? $mock_profiles['ksjcreative'];

  $node = \Drupal::entityTypeManager()->getStorage('node')->create([
    'type' => 'creator_x_profile',
    'title' => $mock['name'] . ' - X Profile (Demo)',
    'status' => 1,
    'field_x_username' => $username,
    'field_bio_description' => [
      'value' => '<p>' . htmlspecialchars($mock['bio']) . '</p>',
      'format' => 'basic_html',
    ],
    'field_follower_count' => $mock['followers'],
    'field_top_posts' => array_map(function($post) {
      return ['value' => json_encode($post)];
    }, $mock['top_posts']),
    'field_top_followers' => array_map(function($f) {
      return ['value' => json_encode($f)];
    }, $mock['followers_sample']),
    'field_metrics' => [
      'value' => json_encode($mock['metrics']),
    ],
  ]);
  $node->save();

  echo "Created Node ID: " . $node->id() . " for @{$username}\n";
}

echo "\n=== 5 DEMO PROFILES CREATED ===\n";
echo "View all at /admin/content or test frontend /stores/elonmusk etc.\n";

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

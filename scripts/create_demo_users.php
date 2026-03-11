<?php

/**
 * Create demo store owner users with fake profile data, linked to existing stores.
 * Run via: drush php:script create_demo_users.php
 */

use Drupal\user\Entity\User;
use Drupal\user\Entity\Role;

$demo_users = [
  [
    'username'    => 'elonmusk',
    'email'       => 'elon@rareimagery.net',
    'password'    => 'Demo1234!elon',
    'shop_name'   => 'Elon\'s Mars Merch',
    'bio'         => 'CEO of SpaceX, Tesla, xAI. Making life multiplanetary. Shop exclusive Mars-themed collectibles, Cybertruck models, and space memorabilia.',
    'phone'       => '+1-555-0101',
    'social'      => ['https://x.com/elonmusk', 'https://instagram.com/elonmusk'],
    'payout'      => 'stripe:acct_elon_demo_001',
  ],
  [
    'username'    => 'alphafox',
    'email'       => 'alphafox@rareimagery.net',
    'password'    => 'Demo1234!alpha',
    'shop_name'   => 'Alpha Fox Trading Co.',
    'bio'         => 'Crypto trader & digital culture curator. Limited edition NFT-inspired merch, trading floor essentials, and crypto lifestyle gear.',
    'phone'       => '+1-555-0102',
    'social'      => ['https://x.com/alphafox', 'https://youtube.com/@alphafox'],
    'payout'      => 'stripe:acct_alpha_demo_002',
  ],
  [
    'username'    => 'clownworld',
    'email'       => 'clownworld@rareimagery.net',
    'password'    => 'Demo1234!clown',
    'shop_name'   => 'Clown World Satire Shop',
    'bio'         => 'Satirical commentary on the absurd. Meme-worthy merch, ironic apparel, and reality-check accessories for the chronically online.',
    'phone'       => '+1-555-0103',
    'social'      => ['https://x.com/clownworld', 'https://tiktok.com/@clownworld'],
    'payout'      => 'stripe:acct_clown_demo_003',
  ],
  [
    'username'    => 'ksjcreative',
    'email'       => 'ksj@rareimagery.net',
    'password'    => 'Demo1234!ksj',
    'shop_name'   => 'KSJ Creative Studio',
    'bio'         => 'Digital artist & illustrator. Original art prints, custom commissions, sticker packs, and creative tools for fellow artists.',
    'phone'       => '+1-555-0104',
    'social'      => ['https://x.com/ksjcreative', 'https://behance.net/ksjcreative', 'https://etsy.com/shop/ksjcreative'],
    'payout'      => 'stripe:acct_ksj_demo_004',
  ],
  [
    'username'    => 'rareimagery',
    'email'       => 'shop@rareimagery.net',
    'password'    => 'Demo1234!rare',
    'shop_name'   => 'Rare Imagery Photography',
    'bio'         => 'Fine art & landscape photographer. Premium photo prints, digital wallpaper packs, Lightroom presets, and behind-the-lens content.',
    'phone'       => '+1-555-0105',
    'social'      => ['https://x.com/rareimagery', 'https://instagram.com/rareimagery', 'https://500px.com/rareimagery'],
    'payout'      => 'stripe:acct_rare_demo_005',
  ],
];

// Make sure store_owner role exists
$role = Role::load('store_owner');
if (!$role) {
  echo "[!] store_owner role not found. Run setup_store_owner_users.php first.\n";
  return;
}

// Load all stores so we can link users to them
$store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
$stores_by_slug = [];
$store_entities = $store_storage->loadMultiple();
foreach ($store_entities as $store) {
  $slug = $store->get('field_store_slug')->value;
  if ($slug) {
    $stores_by_slug[$slug] = $store;
  }
}

echo "\n=== Creating Demo Store Owner Accounts ===\n\n";
echo "Found " . count($stores_by_slug) . " stores: " . implode(', ', array_keys($stores_by_slug)) . "\n\n";

foreach ($demo_users as $u) {
  $username = $u['username'];

  // Check if user already exists
  $existing = \Drupal::entityTypeManager()
    ->getStorage('user')
    ->loadByProperties(['name' => $username]);

  if (!empty($existing)) {
    $user = reset($existing);
    echo "[$username] User already exists (uid: {$user->id()}), updating fields...\n";
  } else {
    $user = User::create([
      'name' => $username,
      'mail' => $u['email'],
      'status' => 1,
    ]);
    $user->setPassword($u['password']);
    echo "[$username] Created new user\n";
  }

  // Assign role
  if (!$user->hasRole('store_owner')) {
    $user->addRole('store_owner');
    echo "  - Assigned store_owner role\n";
  }

  // Set email
  $user->setEmail($u['email']);

  // Profile fields
  if ($user->hasField('field_shop_name')) {
    $user->set('field_shop_name', $u['shop_name']);
    echo "  - Shop name: {$u['shop_name']}\n";
  }

  if ($user->hasField('field_bio')) {
    $user->set('field_bio', ['value' => $u['bio'], 'format' => 'basic_html']);
    echo "  - Bio set\n";
  }

  if ($user->hasField('field_phone')) {
    $user->set('field_phone', $u['phone']);
    echo "  - Phone: {$u['phone']}\n";
  }

  if ($user->hasField('field_social_links')) {
    $links = [];
    foreach ($u['social'] as $url) {
      $links[] = ['uri' => $url, 'title' => ''];
    }
    $user->set('field_social_links', $links);
    echo "  - Social links: " . count($links) . " added\n";
  }

  if ($user->hasField('field_payout_info')) {
    $user->set('field_payout_info', $u['payout']);
    echo "  - Payout info set\n";
  }

  if ($user->hasField('field_onboarding_complete')) {
    $user->set('field_onboarding_complete', TRUE);
    echo "  - Onboarding marked complete\n";
  }

  // Link to store if it exists
  if ($user->hasField('field_store') && isset($stores_by_slug[$username])) {
    $store = $stores_by_slug[$username];
    $user->set('field_store', ['target_id' => $store->id()]);
    echo "  - Linked to store: {$username} (store ID: {$store->id()})\n";

    // Also set the store owner to this user
    $store->setOwnerId($user->id());
    $store->save();
    echo "  - Set as store owner in Commerce\n";
  } else {
    echo "  - No matching store found for slug: $username\n";
  }

  $user->save();
  echo "  - Saved (uid: {$user->id()})\n\n";
}

echo "\n=== DONE ===\n\n";
echo "Demo Accounts:\n";
echo str_pad("Username", 15) . str_pad("Email", 30) . "Password\n";
echo str_repeat("-", 70) . "\n";
foreach ($demo_users as $u) {
  echo str_pad($u['username'], 15) . str_pad($u['email'], 30) . $u['password'] . "\n";
}
echo "\nAll accounts have the 'store_owner' role and are linked to their stores.\n";

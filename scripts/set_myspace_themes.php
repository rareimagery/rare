<?php
$usernames = ['elonmusk', 'alphafox', 'clownworld', 'ksjcreative', 'rareimagery'];

foreach ($usernames as $username) {
  $nodes = \Drupal::entityTypeManager()
    ->getStorage("node")
    ->loadByProperties(["type" => "creator_x_profile", "field_x_username" => $username]);

  if (empty($nodes)) {
    echo "NOT FOUND: $username\n";
    continue;
  }

  $node = reset($nodes);
  $current = $node->get('field_store_theme')->value;
  $node->set('field_store_theme', 'myspace');
  $node->save();
  echo "UPDATED: $username (was: " . ($current ?: 'null') . " -> myspace)\n";
}

echo "Done!\n";

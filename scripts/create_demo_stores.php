<?php
use Drupal\commerce_store\Entity\Store;
use Drupal\node\Entity\Node;

$stores = [
  ["slug" => "elonmusk", "name" => "Elon Musk's Store"],
  ["slug" => "alphafox", "name" => "AlphaFox's Store"],
  ["slug" => "clownworld", "name" => "ClownWorld's Store"],
  ["slug" => "ksjcreative", "name" => "KSJ Creative's Store"],
  ["slug" => "rareimagery", "name" => "RareImagery's Store"],
];

foreach ($stores as $s) {
  // Check if store already exists
  $existing = \Drupal::entityTypeManager()
    ->getStorage("commerce_store")
    ->loadByProperties(["field_store_slug" => $s["slug"]]);

  if (!empty($existing)) {
    $store = reset($existing);
    echo "SKIP STORE: " . $s["slug"] . " already exists (id=" . $store->id() . ")\n";
  } else {
    $store = Store::create([
      "type" => "online",
      "name" => $s["name"],
      "mail" => "admin@rareimagery.net",
      "default_currency" => "USD",
      "field_store_slug" => $s["slug"],
      "is_default" => ($s["slug"] === "rareimagery"),
    ]);
    $store->save();
    echo "CREATED STORE: " . $s["slug"] . " (id=" . $store->id() . ")\n";
  }

  // Find the matching creator profile and link it
  $profiles = \Drupal::entityTypeManager()
    ->getStorage("node")
    ->loadByProperties(["type" => "creator_x_profile", "field_x_username" => $s["slug"]]);

  if (!empty($profiles)) {
    $profile = reset($profiles);
    $profile->set("field_linked_store", $store->id());
    $profile->save();
    echo "LINKED: " . $s["slug"] . " profile (nid=" . $profile->id() . ") -> store (id=" . $store->id() . ")\n";
  } else {
    echo "NO PROFILE FOUND for " . $s["slug"] . "\n";
  }
}

echo "Done!\n";

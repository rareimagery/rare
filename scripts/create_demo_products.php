<?php
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_price\Price;

// Demo products for each creator store
$creator_products = [
  "elonmusk" => [
    ["title" => "Mars Colony Blueprint Poster", "price" => "29.99", "desc" => "High-res digital print of the SpaceX Mars colony concept art. Limited edition."],
    ["title" => "Cybertruck Scale Model", "price" => "49.99", "desc" => "Die-cast 1:64 scale Cybertruck. Bulletproof glass not included."],
    ["title" => "X Premium Hoodie", "price" => "64.99", "desc" => "Heavyweight black hoodie with embroidered X logo. Unisex fit."],
    ["title" => "Starship Launch Print", "price" => "19.99", "desc" => "Photo print of Starship's first successful orbital flight."],
    ["title" => "\"Free Speech\" Tee", "price" => "24.99", "desc" => "Classic cotton tee. Front: X logo. Back: 'Free Speech Is Not A Crime'."],
    ["title" => "Neuralink Cap", "price" => "34.99", "desc" => "Fitted baseball cap with Neuralink chip embroidery. Matte black."],
  ],
  "alphafox" => [
    ["title" => "Diamond Hands Hoodie", "price" => "59.99", "desc" => "Black hoodie with diamond hands graphic. For true believers only."],
    ["title" => "HODL Coffee Mug", "price" => "18.99", "desc" => "Ceramic mug that changes color with heat — reveals hidden chart going up."],
    ["title" => "Crypto Trading Journal", "price" => "14.99", "desc" => "Hardcover journal with trade log templates, risk tracking, and reflection pages."],
    ["title" => "Bull Run Poster", "price" => "22.99", "desc" => "Neon-style digital art poster of a charging bull with BTC chart overlay."],
    ["title" => "DeFi Degen Snapback", "price" => "29.99", "desc" => "Snapback hat with 'DeFi Degen' embroidery. Green on black."],
    ["title" => "Whale Alert Tee", "price" => "24.99", "desc" => "Tee with whale silhouette and wallet address pattern. WAGMI."],
  ],
  "clownworld" => [
    ["title" => "Honk Honk Air Horn", "price" => "12.99", "desc" => "Pocket-sized air horn for maximum honk energy. Use responsibly."],
    ["title" => "Clown World Mug", "price" => "16.99", "desc" => "\"This Is Fine\" mug but the dog is wearing a clown wig."],
    ["title" => "NPC Tee", "price" => "22.99", "desc" => "Grey NPC face on black tee. The shirt that starts conversations."],
    ["title" => "Simulation Glitch Poster", "price" => "19.99", "desc" => "Glitch art poster that says 'The Simulation Writers Are Getting Lazy'."],
    ["title" => "Clown Nose Stress Ball", "price" => "9.99", "desc" => "Red foam clown nose. Squeeze when the timeline gets too wild."],
    ["title" => "Peak Clown Hoodie", "price" => "54.99", "desc" => "Premium hoodie. Front: Rainbow clown. Back: 'Peak Clown Achieved. Or So We Thought.'"],
  ],
  "ksjcreative" => [
    ["title" => "Neon Dreams Art Print", "price" => "34.99", "desc" => "Signed 18x24 giclée print. Cyberpunk cityscape in neon palette. Edition of 50."],
    ["title" => "Digital Art Brush Pack", "price" => "12.99", "desc" => "200+ custom Procreate & Photoshop brushes used in my actual artwork."],
    ["title" => "Fantasy Character Commission", "price" => "149.99", "desc" => "Full character illustration — your OC or D&D character. Digital delivery."],
    ["title" => "Speed Painting Course", "price" => "39.99", "desc" => "2-hour video course: concept art speed painting techniques from sketch to final."],
    ["title" => "Sticker Pack Vol. 1", "price" => "8.99", "desc" => "10 die-cut holographic stickers featuring original character designs."],
    ["title" => "Artist Sketchbook", "price" => "24.99", "desc" => "160-page hardcover sketchbook with my cover art. Thick 120gsm paper."],
  ],
  "rareimagery" => [
    ["title" => "Urban Decay Photo Print", "price" => "39.99", "desc" => "Limited edition 16x20 fine art print. Abandoned factory in golden hour."],
    ["title" => "Street Photography Zine", "price" => "15.99", "desc" => "32-page saddle-stitched zine. Raw street photography from 5 cities."],
    ["title" => "Rare Shots Lightroom Presets", "price" => "19.99", "desc" => "12 custom Lightroom presets that give your photos the RareImagery look."],
    ["title" => "Neon Nights Poster Set", "price" => "29.99", "desc" => "Set of 3 neon-lit urban landscape posters. 12x18 each."],
    ["title" => "Photography Masterclass", "price" => "59.99", "desc" => "4-hour video course covering composition, lighting, and post-processing."],
    ["title" => "Rare Tee - Logo Edition", "price" => "27.99", "desc" => "Heavyweight cotton tee with embossed RareImagery wordmark. Limited run."],
  ],
];

foreach ($creator_products as $slug => $products) {
  // Find the store for this creator
  $stores = \Drupal::entityTypeManager()
    ->getStorage("commerce_store")
    ->loadByProperties(["field_store_slug" => $slug]);

  if (empty($stores)) {
    echo "NO STORE for $slug — skipping products\n";
    continue;
  }

  $store = reset($stores);
  echo "\n=== $slug (store id=" . $store->id() . ") ===\n";

  foreach ($products as $p) {
    // Check if product already exists
    $existing = \Drupal::entityTypeManager()
      ->getStorage("commerce_product")
      ->loadByProperties(["title" => $p["title"]]);

    if (!empty($existing)) {
      echo "  SKIP: " . $p["title"] . " already exists\n";
      continue;
    }

    // Create variation (price + SKU)
    $sku = strtolower(preg_replace("/[^a-zA-Z0-9]/", "-", $slug . "-" . $p["title"]));
    $sku = substr($sku, 0, 64);

    $variation = ProductVariation::create([
      "type" => "default",
      "sku" => $sku,
      "price" => new Price($p["price"], "USD"),
      "status" => 1,
    ]);
    $variation->save();

    // Create product
    $product = Product::create([
      "type" => "default",
      "title" => $p["title"],
      "body" => ["value" => $p["desc"], "format" => "basic_html"],
      "stores" => [$store->id()],
      "variations" => [$variation->id()],
      "status" => 1,
    ]);
    $product->save();

    echo "  CREATED: " . $p["title"] . " ($" . $p["price"] . ") id=" . $product->id() . "\n";
  }
}

echo "\nDone! All demo products created.\n";

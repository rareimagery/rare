<?php
use Drupal\node\Entity\Node;

$profiles = [
  [
    "title" => "Elon Musk - X Profile",
    "field_x_username" => "elonmusk",
    "field_bio_description" => ["value" => "<p>Owner of X, CEO of Tesla & SpaceX. Mars is the goal.</p>", "format" => "basic_html"],
    "field_follower_count" => 196400000,
    "field_top_posts" => [
      ["value" => json_encode(["text" => "The future of civilization depends on becoming multi-planetary", "likes" => 524000, "retweets" => 68000])],
      ["value" => json_encode(["text" => "Tesla Cybertruck is the most badass vehicle on the road", "likes" => 312000, "retweets" => 41000])],
      ["value" => json_encode(["text" => "Starship will make life multiplanetary", "likes" => 289000, "retweets" => 35000])],
      ["value" => json_encode(["text" => "X is the everything app", "likes" => 256000, "retweets" => 32000])],
      ["value" => json_encode(["text" => "AI will be the most transformative technology in human history", "likes" => 198000, "retweets" => 28000])],
      ["value" => json_encode(["text" => "Free speech is essential for democracy", "likes" => 187000, "retweets" => 25000])],
      ["value" => json_encode(["text" => "Neuralink will solve brain diseases and merge humans with AI", "likes" => 145000, "retweets" => 19000])],
      ["value" => json_encode(["text" => "Mars, here we come!", "likes" => 134000, "retweets" => 17000])],
    ],
    "field_top_followers" => [
      ["value" => json_encode(["username" => "BillGates", "name" => "Bill Gates", "followers" => 65000000])],
      ["value" => json_encode(["username" => "BarackObama", "name" => "Barack Obama", "followers" => 132000000])],
      ["value" => json_encode(["username" => "justinbieber", "name" => "Justin Bieber", "followers" => 113000000])],
      ["value" => json_encode(["username" => "Cristiano", "name" => "Cristiano Ronaldo", "followers" => 112000000])],
      ["value" => json_encode(["username" => "taylorswift13", "name" => "Taylor Swift", "followers" => 95000000])],
      ["value" => json_encode(["username" => "narendramodi", "name" => "Narendra Modi", "followers" => 99000000])],
      ["value" => json_encode(["username" => "NASA", "name" => "NASA", "followers" => 85000000])],
      ["value" => json_encode(["username" => "Tesla", "name" => "Tesla", "followers" => 22000000])],
    ],
    "field_store_theme" => "neon",
  ],
  [
    "title" => "AlphaFox - X Profile",
    "field_x_username" => "alphafox",
    "field_bio_description" => ["value" => "<p>Crypto trader | DeFi degen | Building the future of finance one block at a time</p>", "format" => "basic_html"],
    "field_follower_count" => 245000,
    "field_top_posts" => [
      ["value" => json_encode(["text" => "BTC to 200k is not a meme. The halvening cycle never lies.", "likes" => 8900, "retweets" => 2100])],
      ["value" => json_encode(["text" => "Just aped into this new L2. The tech is actually solid for once.", "likes" => 5400, "retweets" => 890])],
      ["value" => json_encode(["text" => "Thread: How I turned 5k into 500k in 18 months", "likes" => 12300, "retweets" => 4500])],
      ["value" => json_encode(["text" => "Bear markets build millionaires. Bull markets reveal them.", "likes" => 9100, "retweets" => 2900])],
      ["value" => json_encode(["text" => "New alpha drop. This token is going 100x minimum. NFA.", "likes" => 6700, "retweets" => 1800])],
      ["value" => json_encode(["text" => "The merge was just the beginning. ETH ecosystem is about to explode.", "likes" => 3800, "retweets" => 520])],
      ["value" => json_encode(["text" => "If you are not building in crypto you will miss the biggest wealth transfer", "likes" => 4200, "retweets" => 670])],
      ["value" => json_encode(["text" => "GM to everyone except the paper hands", "likes" => 3100, "retweets" => 450])],
    ],
    "field_top_followers" => [
      ["value" => json_encode(["username" => "CryptoKaleo", "name" => "Kaleo", "followers" => 580000])],
      ["value" => json_encode(["username" => "CryptoCobain", "name" => "Cobie", "followers" => 720000])],
      ["value" => json_encode(["username" => "inversebrah", "name" => "inversebrah", "followers" => 340000])],
      ["value" => json_encode(["username" => "DefiIgnas", "name" => "Ignas", "followers" => 290000])],
    ],
    "field_store_theme" => "minimal",
  ],
  [
    "title" => "ClownWorld - X Profile",
    "field_x_username" => "clownworld",
    "field_bio_description" => ["value" => "<p>Satire account | Pointing out absurdity since 2019 | Honk honk</p>", "format" => "basic_html"],
    "field_follower_count" => 890000,
    "field_top_posts" => [
      ["value" => json_encode(["text" => "We live in a society where the people who tell the truth are called crazy", "likes" => 45000, "retweets" => 12000])],
      ["value" => json_encode(["text" => "The current state of journalism is a comedian doing the news better than reporters", "likes" => 38000, "retweets" => 9800])],
      ["value" => json_encode(["text" => "Imagine explaining 2026 to someone from 2019. They would not believe a word.", "likes" => 29000, "retweets" => 7200])],
      ["value" => json_encode(["text" => "Breaking: Man who predicted nothing correctly promoted to chief analyst", "likes" => 34000, "retweets" => 8900])],
      ["value" => json_encode(["text" => "The simulation writers are getting lazy at this point", "likes" => 18000, "retweets" => 4300])],
      ["value" => json_encode(["text" => "Honk honk", "likes" => 22000, "retweets" => 5100])],
      ["value" => json_encode(["text" => "Clown world update: Everything is still clown world", "likes" => 15000, "retweets" => 3200])],
      ["value" => json_encode(["text" => "Peak clown achieved. Or so we thought.", "likes" => 12000, "retweets" => 2800])],
    ],
    "field_top_followers" => [
      ["value" => json_encode(["username" => "BabylonBee", "name" => "The Babylon Bee", "followers" => 2900000])],
      ["value" => json_encode(["username" => "catturd2", "name" => "Catturd", "followers" => 2600000])],
      ["value" => json_encode(["username" => "libsoftiktok", "name" => "Libs of TikTok", "followers" => 3200000])],
      ["value" => json_encode(["username" => "ZubyMusic", "name" => "Zuby", "followers" => 1800000])],
    ],
    "field_store_theme" => "myspace",
  ],
  [
    "title" => "KSJ Creative - X Profile",
    "field_x_username" => "ksjcreative",
    "field_bio_description" => ["value" => "<p>Digital artist and illustrator | Commissions open | Prints available | Art is life</p>", "format" => "basic_html"],
    "field_follower_count" => 67000,
    "field_top_posts" => [
      ["value" => json_encode(["text" => "New piece just dropped. This one took 40 hours. Worth every minute.", "likes" => 4500, "retweets" => 890])],
      ["value" => json_encode(["text" => "Speed painting timelapse - watch 20 hours of work in 60 seconds", "likes" => 8900, "retweets" => 2300])],
      ["value" => json_encode(["text" => "Behind the scenes of my latest digital painting process thread", "likes" => 5600, "retweets" => 1200])],
      ["value" => json_encode(["text" => "Thank you for 67k followers! You all inspire me every day.", "likes" => 6700, "retweets" => 1500])],
      ["value" => json_encode(["text" => "Commission slots are open for March! DM for pricing.", "likes" => 2100, "retweets" => 340])],
      ["value" => json_encode(["text" => "The difference between good art and great art is 10000 hours of practice", "likes" => 3200, "retweets" => 670])],
      ["value" => json_encode(["text" => "New print collection available on my store! Limited edition of 50.", "likes" => 1800, "retweets" => 290])],
      ["value" => json_encode(["text" => "Art drop tomorrow at noon EST. Set your alarms!", "likes" => 2900, "retweets" => 510])],
    ],
    "field_top_followers" => [
      ["value" => json_encode(["username" => "Beeple", "name" => "beeple", "followers" => 2100000])],
      ["value" => json_encode(["username" => "MBSJQ", "name" => "MBSJQ", "followers" => 180000])],
      ["value" => json_encode(["username" => "loaborin", "name" => "Loa Borin", "followers" => 95000])],
      ["value" => json_encode(["username" => "ArtStation", "name" => "ArtStation", "followers" => 450000])],
    ],
    "field_store_theme" => "editorial",
  ],
];

foreach ($profiles as $p) {
  $existing = \Drupal::entityTypeManager()
    ->getStorage("node")
    ->loadByProperties(["type" => "creator_x_profile", "field_x_username" => $p["field_x_username"]]);

  if (!empty($existing)) {
    echo "SKIP: " . $p["field_x_username"] . " already exists\n";
    continue;
  }

  $node = Node::create([
    "type" => "creator_x_profile",
    "title" => $p["title"],
    "field_x_username" => $p["field_x_username"],
    "field_bio_description" => $p["field_bio_description"],
    "field_follower_count" => $p["field_follower_count"],
    "field_top_posts" => $p["field_top_posts"],
    "field_top_followers" => $p["field_top_followers"],
    "field_store_theme" => $p["field_store_theme"] ?? null,
    "status" => 1,
  ]);
  $node->save();
  echo "CREATED: " . $p["field_x_username"] . " (nid=" . $node->id() . ")\n";
}

echo "Done!\n";

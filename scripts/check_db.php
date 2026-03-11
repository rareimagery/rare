<?php
/**
 * Check which database drush is using.
 * Run: drush php:script web/check_db.php
 */
$db = \Drupal\Core\Database\Database::getConnectionInfo();
echo "Database: " . $db['default']['database'] . "\n";
echo "Host: " . $db['default']['host'] . "\n";
echo "Driver: " . $db['default']['driver'] . "\n";

// Count nodes by type
$query = \Drupal::database()->query("SELECT type, count(*) as cnt FROM node_field_data GROUP BY type");
echo "\nNode counts by type:\n";
foreach ($query as $row) {
  echo "  {$row->type}: {$row->cnt}\n";
}

// Check config
$query2 = \Drupal::database()->query("SELECT encode(data, 'escape') as data FROM config WHERE name = 'jsonapi.settings'");
$row = $query2->fetchObject();
echo "\njsonapi.settings from DB: " . $row->data . "\n";

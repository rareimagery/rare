<?php

/**
 * Enable the user login REST resource.
 * Run via: drush php:script enable_rest_login.php
 */

use Drupal\rest\Entity\RestResourceConfig;

$resource_id = 'user_login';

$config = RestResourceConfig::load($resource_id);
if (!$config) {
  $config = RestResourceConfig::create([
    'id' => $resource_id,
    'plugin_id' => 'user_login',
    'granularity' => 'resource',
    'configuration' => [
      'methods' => ['POST'],
      'formats' => ['json'],
      'authentication' => ['cookie'],
    ],
  ]);
  $config->save();
  echo "[+] Enabled user_login REST resource\n";
} else {
  echo "[=] user_login REST resource already enabled\n";
}

// Also enable user_login_status resource
$status_id = 'user_login_status';
$status_config = RestResourceConfig::load($status_id);
if (!$status_config) {
  $status_config = RestResourceConfig::create([
    'id' => $status_id,
    'plugin_id' => 'user_login_status',
    'granularity' => 'resource',
    'configuration' => [
      'methods' => ['GET'],
      'formats' => ['json'],
      'authentication' => ['cookie'],
    ],
  ]);
  $status_config->save();
  echo "[+] Enabled user_login_status REST resource\n";
} else {
  echo "[=] user_login_status REST resource already enabled\n";
}

echo "\nDone. Clear cache with: drush cr\n";

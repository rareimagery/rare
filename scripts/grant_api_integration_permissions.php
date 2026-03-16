<?php

/**
 * Grant/repair Drupal permissions for the Next.js integration account.
 *
 * Run:
 *   drush php:script scripts/grant_api_integration_permissions.php
 *
 * Optional env overrides:
 *   DRUPAL_API_USER=rare
 *   DRUPAL_API_ROLE=api_integration
 */

use Drupal\user\Entity\Role;

$apiUser = getenv('DRUPAL_API_USER') ?: 'rare';
$apiRoleId = getenv('DRUPAL_API_ROLE') ?: 'api_integration';
$apiRoleLabel = 'API Integration';

echo "=== API Integration Permission Repair ===\n";
echo "User: {$apiUser}\n";
echo "Role: {$apiRoleId}\n\n";

$accounts = \Drupal::entityTypeManager()
  ->getStorage('user')
  ->loadByProperties(['name' => $apiUser]);

if (empty($accounts)) {
  echo "[x] User '{$apiUser}' not found.\n";
  echo "    Set DRUPAL_API_USER env var or create the account first.\n";
  return;
}

/** @var \Drupal\user\UserInterface $account */
$account = reset($accounts);

echo "[+] Found user #" . $account->id() . " ({$account->getAccountName()})\n";

$role = Role::load($apiRoleId);
if (!$role) {
  $role = Role::create(['id' => $apiRoleId, 'label' => $apiRoleLabel]);
  $role->save();
  echo "[+] Created role '{$apiRoleId}'\n";
} else {
  echo "[=] Role '{$apiRoleId}' already exists\n";
}

$allPermissions = \Drupal::service('user.permissions')->getPermissions();
$available = array_keys($allPermissions);

$desiredPermissions = [
  // Core/content access
  'access content',
  'access content overview',
  'view any unpublished content',

  // Commerce store (required for /jsonapi/commerce_store/online writes)
  'view commerce_store',
  'view online commerce_store',
  'create commerce_store',
  'create online commerce_store',
  'update own online commerce_store',
  'update any online commerce_store',
  'access commerce_store overview',

  // Creator profile content type (required for /jsonapi/node/creator_x_profile writes)
  'create creator_x_profile content',
  'edit own creator_x_profile content',
  'edit any creator_x_profile content',

  // Files/media for image field uploads
  'create files',
  'delete own files',
  'update any media',
  'create media',
];

$granted = 0;
$already = 0;
$missing = 0;

foreach ($desiredPermissions as $permission) {
  if (!in_array($permission, $available, true)) {
    echo "[!] Permission not found on this site: {$permission}\n";
    $missing++;
    continue;
  }

  if ($role->hasPermission($permission)) {
    $already++;
    continue;
  }

  $role->grantPermission($permission);
  $granted++;
}

$role->save();

echo "\n[+] Role permissions updated\n";
echo "    Granted: {$granted}\n";
echo "    Already present: {$already}\n";
echo "    Missing on site: {$missing}\n";

if (!$account->hasRole($apiRoleId)) {
  $account->addRole($apiRoleId);
  $account->save();
  echo "[+] Assigned role '{$apiRoleId}' to user '{$apiUser}'\n";
} else {
  echo "[=] User already has role '{$apiRoleId}'\n";
}

echo "\nDone. Run 'drush cr' after permission changes if needed.\n";

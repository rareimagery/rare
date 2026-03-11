<?php
/**
 * Enable JSON:API read-write mode + update all profiles to xai3 theme.
 * Run: docker exec rare-drupal bash -c "cd /var/www/html && php scripts/enable_jsonapi_write.php"
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

chdir('/var/www/html');
$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);
$container = $kernel->getContainer();

// 1. Enable JSON:API write mode
$config_factory = $container->get('config.factory');
$config = $config_factory->getEditable('jsonapi.settings');
$current = $config->get('read_only');
echo "Current JSON:API read_only: " . var_export($current, true) . "\n";

$config->set('read_only', FALSE)->save();
echo "JSON:API read_only set to: FALSE (write mode enabled)\n";

// 2. Update all creator profiles to xai3 theme
$entity_type_manager = $container->get('entity_type.manager');
$storage = $entity_type_manager->getStorage('node');
$query = $storage->getQuery()
  ->accessCheck(FALSE)
  ->condition('type', 'creator_x_profile');
$nids = $query->execute();

echo "Found " . count($nids) . " creator profiles\n";

foreach ($storage->loadMultiple($nids) as $node) {
  $username = $node->get('field_x_username')->value ?? '(unknown)';
  $old_theme = $node->get('field_store_theme')->value ?? '(none)';
  $node->set('field_store_theme', 'xai3');
  $node->save();
  echo "  Updated: $username ($old_theme → xai3)\n";
}

echo "\nDone! All profiles now use xai3 theme.\n";

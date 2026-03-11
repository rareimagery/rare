<?php
/**
 * Minimal test: what does Drupal's config factory return for jsonapi read_only?
 * Access via web: http://72.62.80.155/test_jsonapi_config.php
 */
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once __DIR__ . '/autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);

$config_factory = \Drupal::configFactory();
$config = $config_factory->get('jsonapi.settings');
$read_only = $config->get('read_only');

header('Content-Type: text/plain');
echo "read_only raw: " . var_export($read_only, true) . "\n";
echo "read_only type: " . gettype($read_only) . "\n";
echo "read_only bool: " . ($read_only ? 'TRUE' : 'FALSE') . "\n";

// Also check the original config (without overrides)
$immutable = $config_factory->get('jsonapi.settings');
$editable = $config_factory->getEditable('jsonapi.settings');
echo "immutable read_only: " . var_export($immutable->get('read_only'), true) . "\n";
echo "editable read_only: " . var_export($editable->get('read_only'), true) . "\n";

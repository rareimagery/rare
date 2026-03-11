<?php
/**
 * Clear all Drupal caches.
 */
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

chdir('/var/www/html');
$autoloader = require_once 'autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$kernel->preHandle($request);

drupal_flush_all_caches();
echo "All caches cleared.\n";

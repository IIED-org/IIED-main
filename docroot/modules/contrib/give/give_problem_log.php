<?php

/**
 * @file
 * Handles counts of node views via AJAX with minimal bootstrap.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

// The non-hard-coded way,
// $autoloader = require \Drupal::root() . '/autoload.php';
// doesn't work because it can't load the class Drupal, naturally.
$script_name = trim($_SERVER['SCRIPT_NAME'], '/');
$count = substr_count($script_name, '/');
$chdir = '';
for ($i = 1; $i < $count; $i++) {
  $chdir .= '../';
}
$chdir .= '..';

chdir($chdir);

$autoloader = require_once 'autoload.php';

$kernel = DrupalKernel::createFromRequest(Request::createFromGlobals(), $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();

$log = $container
  ->get('config.factory')
  ->get('give.settings')
  ->get('log_problems');

if ($log) {
  $donation_uuid = filter_input(INPUT_POST, 'donation_uuid');
  $type = filter_input(INPUT_POST, 'type');
  $detail = filter_input(INPUT_POST, 'detail');
  $user_agent = filter_input(INPUT_POST, 'user_agent');
  if ($donation_uuid && $type && $detail) {
    $container->get('give.problem_log')->log($donation_uuid, $type, $detail, $user_agent);
  }
}

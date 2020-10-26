<?php

/**
 * @file
 * Drush commands for the pat_redirect_import module.
 */

use Drupal\path_redirect_import\ImporterService;

/**
 * Implements hook_drush_commands().
 */
function path_redirect_import_drush_command() {
  $items = [];

  $items['path-redirect-import'] = [
    'description' => "Import list of URL redirects",
    'arguments' => [
      'file' => 'Path to the CSV file that includes the redirect info',
    ],
    'options' => [
      'no_headers' => 'Indicates if the first line must be taken into account. Default TRUE',
      'override' => 'Indicates if existent redirect should be overriden or not. Default FALSE',
      'status_code' => 'Indicates the redirection status code. Default 301',
      'delimiter' => 'Indicates the delimiter between fields. Default \',\'',
      'language' => 'Indicates the language to assign to this redirect.',
    ],
    'examples' => [
      'drush -l http://example.com path-redirect-import sites/all/modules/custom/import_tienda/import/consulta_fabricantes_espana.csv',
      'drush primport sites/all/modules/custom/import_tienda/import/consulta_fabricantes_espana.csv --status_code=302',
    ],
    'aliases' => ['primport'],
  ];

  return $items;
}

/**
 * Implements the path-redirect-import drush command.
 */
function drush_path_redirect_import($file) {

  if (!file_exists($file)) {
    drush_log("File $file doesn't exist \n", 'error');
    exit;
  }

  $options = [];
  $option_values = [
    'no_headers',
    'override',
    'status_code',
    'delimiter',
    'language',
  ];
  foreach ($option_values as $value) {
    if (drush_get_option($value)) {
      $options[$value] = drush_get_option($value);
    }
  }

  ImporterService::import($file, $options);
}

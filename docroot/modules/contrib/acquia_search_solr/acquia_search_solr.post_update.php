<?php

/**
 * @file
 * Search Solr updates once other modules have made their own updates.
 */

use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\acquia_search\Helper\Storage;
use Drupal\views\Views;

/**
 * Clear cache to rebuild routes.
 */
function acquia_search_solr_post_update_clear_routes() {
  \Drupal::service("router.builder")->rebuild();
  PhpStorageFactory::get("twig")->deleteAll();
}

function acquia_search_solr_post_update_upgrade_to_acquia_searchh() {
  // Install Acquia Search if its not yet installed.
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
  $module_installer = \Drupal::service('module_installer');
  if (!\Drupal::moduleHandler()->moduleExists('acquia_search')) {
    $module_installer->install(['acquia_search'], TRUE);
  }

  $config_factory = \Drupal::configFactory();

  // Export settings acquia_search.
  $storage = new Storage();
  $storage->setApiHost(\Drupal::config('acquia_search_solr.settings')
      ->get('api_host') ?? 'https://api.sr-prod02.acquia.com');
  $storage->setApiKey(\Drupal::state()->get('acquia_search_solr.api_key'));
  $storage->setIdentifier(\Drupal::state()
    ->get('acquia_search_solr.identifier'));
  $storage->setUuid(\Drupal::state()
    ->get('acquia_search_solr.uuid'));

  if ($solr_search_config = $config_factory->getEditable('acquia_search_solr.settings')) {
    if ($override = $solr_search_config->get('override_search_core')) {
      $acquia_search_config = $config_factory->getEditable('acquia_search.settings');
      $acquia_search_config->set('override_search_core', $override);
      $acquia_search_config->save();
      $solr_search_config->delete();
    }
  }
  // Move current index to Acquia Search.
  $old_solr_index = $config_factory->getEditable('search_api.index.acquia_search_solr_search_api_solr_index');
  $new_solr_index = $config_factory->getEditable('search_api.index.acquia_search_index');
  $acquia_search_server = $config_factory->getEditable('search_api.server.acquia_search_server');
  if (!empty($new_solr_index) && !empty($acquia_search_server)) {
    // Update Settings within the index to match search solr
    $settings = ['third_party_settings', 'field_settings', 'datasource_settings', 'processor_settings', 'tracker_settings', 'options'];
    foreach ($settings as $setting) {
      $new_solr_index->set($setting, $old_solr_index->get($setting));
    }

    $config_dependencies = $old_solr_index->get('dependencies.config');
    // Remove server dependency from index.
    if (($key = array_search('search_api.server.acquia_search_solr_search_api_solr_server', $config_dependencies)) !== false) {
      $config_dependencies[$key] = 'search_api.server.acquia_search_server';
      $new_solr_index->set('dependencies.config', $config_dependencies);
    }
    $new_solr_index->save();

    if ($old_search_server = $config_factory->getEditable('search_api.server.acquia_search_solr_search_api_solr_server')) {
      $old_search_server->delete();
    }
    $old_solr_index->delete();
    // Delete the new view before renaming the old one
    if ($new_search_view = $config_factory->getEditable('views.view.acquia_search')) {
      $new_search_view->delete();
    }
    $config_factory->rename('views.view.acquia_search_solr', 'views.view.acquia_search');
    // Update the view's base table - Needs to be fixed.
    $old_search_view = $config_factory->getEditable('views.view.acquia_search');
    $old_search_view->set('base_table', 'search_api_index_acquia_search_index');
    $old_search_view->set('id', 'acquia_search');
    $view_display = $old_search_view->get('display');
    // Loop through the displays
    foreach ($view_display as $display_key => $display_items) {
      if (!empty($view_display[$display_key]['display_options'])) {
        foreach ($view_display[$display_key]['display_options'] as $display_options => $items) {
          if (is_array($items)) {
            foreach ($items as $key => $item) {
              if (is_array($item) && array_key_exists('table', $item)) {// echo $key; //continue;
                $view_display[$display_key]['display_options'][$display_options][$key]['table'] = 'search_api_index_acquia_search_index';
              }
            }
          }
        }
      }
    }

    $old_search_view->set('display', $view_display);
    $old_search_view->save(TRUE);
    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  // Self distruct in 5, 4, 3, 2, 1...
  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
  $module_installer = \Drupal::service('module_installer');
  $module_installer->uninstall(['acquia_search_solr']);

  \Drupal::service("router.builder")->rebuild();
  PhpStorageFactory::get("twig")->deleteAll();
  drupal_flush_all_caches();
}

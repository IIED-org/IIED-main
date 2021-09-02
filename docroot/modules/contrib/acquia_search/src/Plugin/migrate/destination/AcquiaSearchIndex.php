<?php

namespace Drupal\acquia_search\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\Config;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Migrating Acquia Search Index configuration.
 *
 * @MigrateDestination(
 *   id = "d7_acquia_search_index"
 * )
 */
class AcquiaSearchIndex extends Config {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      ['config_name' => 'search_api.index.acquia_search_index'],
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('config.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $imported = FALSE;
    $bundle_path = 'datasource_settings.entity:' . $row->get('entity_type') . '.bundles.selected';
    $bundles_existing = $this->config->get($bundle_path);
    $bundles_existing = empty($bundles_existing) ? [] : $bundles_existing;
    $bundle_new = $row->get('bundle');
    if (!in_array($bundle_new, $bundles_existing)) {
      $bundles_existing[] = $bundle_new;
      $this->config->set($bundle_path, $bundles_existing)
        ->save();
      $imported = TRUE;
    }
    return $imported;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['env_id']['type'] = 'string';
    $ids['bundle']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [];
  }

}

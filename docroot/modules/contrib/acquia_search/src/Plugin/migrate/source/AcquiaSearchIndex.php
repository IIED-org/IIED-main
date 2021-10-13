<?php

namespace Drupal\acquia_Search\Plugin\migrate\source;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 acquia search index source from database.
 *
 * @MigrateSource(
 *   id = "d7_acquia_search_index",
 *   source_module = "acquia_search"
 * )
 */
class AcquiaSearchIndex extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $result = $this->variableGet('apachesolr_default_environment', 'solr');
    return $this->select('apachesolr_index_bundles', 'a')
      ->condition('env_id', $result, '=')
      ->fields('a');
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $config_factory = \Drupal::configFactory()->getEditable('search_api.index.acquia_search_index');
    $results = $this->prepareQuery()->execute()->fetchAll();
    $new_results = [];

    foreach ($results as $key => $result) {
      $bundle_path = 'datasource_settings.entity:' . $result['entity_type'] . '.bundles.selected';
      $bundles_existing = $config_factory->get($bundle_path);
      $bundles_existing = empty($bundles_existing) ? [] : $bundles_existing;
      $bundle_new = $result['bundle'];
      if (!in_array($bundle_new, $bundles_existing)) {
        $new_results[$key] = $result;
      }
    }
    // Group all instances by their base field.
    $instances = [];
    foreach ($new_results as $result) {
      $instances[$result['env_id']][] = $result;
    }

    // Add the array of all instances using the same base field to each row.
    $rows = [];
    foreach ($new_results as $result) {
      $result['instances'] = $instances[$result['env_id']];
      $rows[] = $result;
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'env_id' => $this->t('The name of the environment'),
      'entity_type' => $this->t('The type of entity.'),
      'bundle' => $this->t('The bundle to index.'),
    ];
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
  public function checkRequirements() {
    parent::checkRequirements();
    $solr_version = $this->variableGet('apachesolr_default_environment', 'solr');
    if ($solr_version !== 'acquia_search_server_3') {
      throw new RequirementsException('Required Sol7 configuration on source site.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    $iterator = $this->getIterator();
    return $iterator instanceof \Countable ? $iterator->count() : iterator_count($this->initializeIterator());
  }

}

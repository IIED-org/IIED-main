<?php

namespace Drupal\custom_sql_migrate_source_plugin\Plugin\migrate\source;

use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;

/**
 *
 * @MigrateSource(
 *   id = "custom_sql_query",
 *   source_module = "custom_sql_migrate_source_plugin",
 * )
 */
class CustomSQLQuery extends SourcePluginBase implements ContainerFactoryPluginInterface, RequirementsInterface {
  /**
   * The query string.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $query;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * State service for retrieving database info.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The count of the number of batches run.
   *
   * @var int
   */
  protected $batch = 0;

  /**
   * Number of records to fetch from the database during each batch.
   *
   * A value of zero indicates no batching is to be done.
   *
   * @var int
   */
  protected $batchSize = 0;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->state = $state;
    $this->database = \Drupal\Core\Database\Database::getConnection('default', $configuration['key']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state')
    );
  }

  /**
   * Prints the query string when the object is used as a string.
   *
   * @return string
   *   The query string.
   */
  public function __toString() {
    return $this->getQueryString();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [];
    if (!$this->file) {
      $this->initializeIterator();
    }
    foreach ($this->file->getColumnNames() as $column) {
      $fields[key($column)] = reset($column);
    }

    // Any caller-specified fields with the same names as extracted fields will
    // override them; any others will be added.
    $fields = $this->getConfiguration()['fields'] + $fields;

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIDs() {
    $ids = [];

    foreach ($this->configuration['keys'] as $delta => $value) {
      if (is_array($value)) {
        $ids[$delta] = $value;
      }
      else {
        $ids[$value]['type'] = 'string';
      }
    }
    return $ids;
  }

  /**
   * Gets the database connection object.
   *
   * @return \Drupal\Core\Database\Connection
   *   The database connection.
   */
  public function getDatabase() {
    if (!isset($this->database)) {
      // Look first for an explicit state key containing the configuration.
      if (isset($this->configuration['database_state_key'])) {
        $this->database = $this->setUpDatabase($this->state->get($this->configuration['database_state_key']));
      }
      // Next, use explicit configuration in the source plugin.
      elseif (isset($this->configuration['key'])) {
        $this->database = $this->setUpDatabase($this->configuration);
      }
      // Next, try falling back to the global state key.
      elseif (($fallback_state_key = $this->state->get('migrate.fallback_state_key'))) {
        $this->database = $this->setUpDatabase($this->state->get($fallback_state_key));
      }
      // If all else fails, let setUpDatabase() fallback to the 'migrate' key.
      else {
        $this->database = $this->setUpDatabase([]);
      }
    }
    return $this->database;
  }

  /**
   * Gets a connection to the referenced database.
   *
   * This method will add the database connection if necessary.
   *
   * @param array $database_info
   *   Configuration for the source database connection. The keys are:
   *    'key' - The database connection key.
   *    'target' - The database connection target.
   *    'database' - Database configuration array as accepted by
   *      Database::addConnectionInfo.
   *
   * @return \Drupal\Core\Database\Connection
   *   The connection to use for this plugin's queries.
   *
   * @throws \Drupal\migrate\Exception\RequirementsException
   *   Thrown if no source database connection is configured.
   */
  protected function setUpDatabase(array $database_info) {
    if (isset($database_info['key'])) {
      $key = $database_info['key'];
    }
    else {
      // If there is no explicit database configuration at all, fall back to a
      // connection named 'migrate'.
      $key = 'migrate';
    }
    if (isset($database_info['target'])) {
      $target = $database_info['target'];
    }
    else {
      $target = 'default';
    }
    if (isset($database_info['database'])) {
      Database::addConnectionInfo($key, $target, $database_info['database']);
    }
    try {
      $connection = Database::getConnection($target, $key);
    }
    catch (ConnectionNotDefinedException $e) {
      // If we fell back to the magic 'migrate' connection and it doesn't exist,
      // treat the lack of the connection as a RequirementsException.
      if ($key == 'migrate') {
        throw new RequirementsException("No database connection configured for source plugin " . $this->pluginId, [], 0, $e);
      }
      else {
        throw $e;
      }
    }
    return $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    if ($this->pluginDefinition['requirements_met'] === TRUE) {
      $this->getDatabase();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    // Initialize the batch size.
    if ($this->batchSize == 0 && isset($this->configuration['batch_size'])) {
      // Valid batch sizes are integers >= 0.
      if (is_int($this->configuration['batch_size']) && ($this->configuration['batch_size']) >= 0) {
        $this->batchSize = $this->configuration['batch_size'];
      }
      else {
        throw new MigrateException("batch_size must be greater than or equal to zero");
      }
    }

    // If a batch has run the query is already setup.

    return new \IteratorIterator($this->getQueryResults());
  }

  /**
   * Position the iterator to the following row.
   */
  protected function fetchNextRow() {
    $this->getIterator()->next();
    // We might be out of data entirely, or just out of data in the current
    // batch. Attempt to fetch the next batch and see.
    if ($this->batchSize > 0 && !$this->getIterator()->valid()) {
      $this->fetchNextBatch();
    }
  }

  /**
   * Prepares query for the next set of data from the source database.
   */
  protected function fetchNextBatch() {
    $this->batch++;
    unset($this->iterator);
    $this->getIterator()->rewind();
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    $results = $this->getQueryResults();

    $i = 0;
    foreach ($results as $result) {
      $i++;
    }
    return $i;
  }

  /**
   * Checks if we can join against the map table.
   *
   * This function specifically catches issues when we're migrating with
   * unique sets of credentials for the source and destination database.
   *
   * @return bool
   *   TRUE if we can join against the map table otherwise FALSE.
   */
  protected function mapJoinable() {
    return FALSE;
  }

  private function getQueryString() {
    return $this->configuration['sql_query'];
  }

  private function getQueryResults() {
    return $this->database->query($this->configuration['sql_query']);
  }

  public function next() {
    $this->currentSourceIds = NULL;
    $this->currentRow = NULL;

    // In order to find the next row we want to process, we ask the source
    // plugin for the next possible row.
    while (!isset($this->currentRow) && $this->getIterator()->valid()) {

      $row_data = $this->getIterator()->current();
      $this->fetchNextRow();
      $row = new Row((array)$row_data, $this->getIds());

      // Populate the source key for this row.
      $this->currentSourceIds = $row->getSourceIdValues();

      // Pick up the existing map row, if any, unless fetchNextRow() did it.
      if (!$this->mapRowAdded && ($id_map = $this->idMap->getRowBySource($this->currentSourceIds))) {
        $row->setIdMap($id_map);
      }

      // Clear any previous messages for this row before potentially adding
      // new ones.
      if (!empty($this->currentSourceIds)) {
        $this->idMap->delete($this->currentSourceIds, TRUE);
      }

      // Preparing the row gives source plugins the chance to skip.
      if ($this->prepareRow($row) === FALSE) {
        continue;
      }

      // Check whether the row needs processing.
      // 1. This row has not been imported yet.
      // 2. Explicitly set to update.
      // 3. The row is newer than the current highwater mark.
      // 4. If no such property exists then try by checking the hash of the row.
      if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
        $this->currentRow = $row->freezeSource();
      }

      if ($this->getHighWaterProperty()) {
        $this->saveHighWater($row->getSourceProperty($this->highWaterProperty['name']));
      }
    }
  }

  /**
   * Checks if the incoming row has changed since our last import.
   *
   * @param \Drupal\migrate\Row $row
   *   The row we're importing.
   *
   * @return bool
   *   TRUE if the row has changed otherwise FALSE.
   */
  protected function rowChanged(Row $row) {
    return TRUE;
//    return $this->trackChanges && $row->changed();
  }
}



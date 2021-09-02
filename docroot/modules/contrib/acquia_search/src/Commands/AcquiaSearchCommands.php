<?php

namespace Drupal\acquia_search\Commands;

use Drupal\acquia_search\Helper\Runtime;
use Drupal\acquia_search\Helper\Storage;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class AcquiaSearchCommands extends DrushCommands {

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private $cache;

  /**
   * AcquiaSearchCommands constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend service.
   */
  public function __construct(CacheBackendInterface $cache) {
    parent::__construct();

    $this->cache = $cache;
  }

  /**
   * Lists available Acquia search cores.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option format
   *   Optional. Format may be json, print_r, or var_dump. Defaults to print_r.
   *
   * @command acquia:search-solr:cores
   *
   * @aliases acquia:ss:cores
   *
   * @usage acquia:search-solr:cores
   *   Lists all available Acquia search cores.
   * @usage acquia:ss:cores --format=json
   *   Lists all available Acquia search cores in JSON format.
   *
   * @validate-module-enabled acquia_search
   *
   * @throws \Exception
   *   If no cores available.
   */
  public function searchSolrCoresList(array $options = ['format' => NULL]) {

    if (!$available_cores = Runtime::getAcquiaSearchApiClient(Storage::getUuid())->getSearchIndexes(Storage::getIdentifier())) {
      throw new \Exception('No Acquia search cores available');
    }

    $available_cores = array_keys($available_cores);

    switch ($options['format']) {
      case 'json':
        $this->output()->writeln(Json::encode($available_cores));
        break;

      case 'var_dump':
      case 'var_export':
        $this->output()->writeln(var_export($available_cores, TRUE));
        break;

      case 'print_r':
      default:
        $this->output()->writeln(print_r($available_cores, TRUE));
        break;

    }

  }

  /**
   * Resets the Acquia Solr Search cores cache.
   *
   * By identifier provided either by configuration or by argument.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option id
   *   Optional. The Acquia subscription identifier corresponding to the search
   *   core for cache reset. By default, this identifier is provided by
   *   configuration.
   *
   * @command acquia:search-solr:cores:cache-reset
   *
   * @aliases acquia:ss:cores:cr
   *
   * @usage acquia:search-solr:cores:cache-reset
   *   Clears the Acquia search cores cache for the default Acquia subscription
   *   identifier provided by module configuration.
   * @usage acquia:ss:cores:cr --id=ABC-12345
   *   Clears the Acquia Search cores cache for the ABC-12345 subscription
   *   identifier.
   *
   * @validate-module-enabled acquia_search
   *
   * @throws \Exception
   *   In case of the invalid Acquia subscription identifier provided via id
   *   option or stored in the module configuration.
   */
  public function searchSolrResetCoresCache(array $options = ['id' => NULL]) {

    $id = $options['id'];

    if (empty($id)) {
      $id = Storage::getIdentifier();
      if (empty($id)) {
        throw new \Exception('No Acquia subscription identifier specified in command line or by configuration.');
      }
    }

    if (!preg_match('@^[A-Z]{4,5}-[0-9]{5,6}$@', $id)) {
      throw new \Exception('Provide a valid Acquia subscription identifier');
    }

    $cid = sprintf("acquia_search.indexes.%s", $id);
    if ($this->cache->get($cid)) {
      $this->cache->delete($cid);
      $this->output()->writeln(dt('Cache cleared for @id', ['@id' => $id]));
      return;
    }

    $this->output()->writeln(dt('Cache is empty for @id', ['@id' => $id]));

  }

  /**
   * Lists possible Acquia search cores.
   *
   * A search core should be in the available cores list to work properly.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option format
   *   Optional. Format may be json, print_r, or var_dump. Defaults to print_r.
   *
   * @command acquia:search-solr:cores:possible
   *
   * @aliases acquia:ss:cores:possible
   *
   * @usage acquia:search-solr:cores:possible
   *   Lists all possible Acquia search cores.
   * @usage acquia:ss:cores:possible --format=json
   *   Lists all possible Acquia search cores in JSON format.
   *
   * @validate-module-enabled acquia_search
   *
   * @throws \Exception
   *   In case if no possible search cores found.
   */
  public function searchSolrCoresPossible(array $options = ['format' => NULL]) {

    if (!$possible_cores = Runtime::getPreferredSearchCoreService()->getListOfPossibleCores()) {
      throw new \Exception('No possible cores');
    }

    switch ($options['format']) {
      case 'json':
        $this->output()->writeln(Json::encode($possible_cores));
        break;

      case 'var_dump':
      case 'var_export':
        $this->output()->writeln(var_export($possible_cores, TRUE));
        break;

      case 'print_r':
      default:
        $this->output()->writeln(print_r($possible_cores, TRUE));
        break;

    }

  }

  /**
   * Display preferred Acquia search core.
   *
   * @command acquia:search-solr:cores:preferred
   * @aliases acquia:ss:cores:preferred
   *
   * @usage acquia:search-solr:cores:preferred
   *   Display preferred Acquia search core.
   * @usage acquia:ss:cores:preferred
   *   Display preferred Acquia search core.
   *
   * @validate-module-enabled acquia_search
   *
   * @throws \Exception
   *   In case if no preferred search core available.
   */
  public function searchSolrCoresPreferred() {

    if (!$preferred_core = Runtime::getPreferredSearchCoreService()->getPreferredCore()) {
      throw new \Exception('No preferred search core available');
    }

    $this->output()->writeln($preferred_core['core_id']);

  }

}

<?php

namespace Drupal\acquia_search\Commands;

use Drupal\acquia_connector\Subscription;
use Drupal\acquia_search\AcquiaSearchApiClient;
use Drupal\acquia_search\Plugin\search_api\backend\AcquiaSearchSolrBackend;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\search_api\Entity\Server;
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
  protected $cache;

  /**
   * Acquia Subscription Service.
   *
   * @var \Drupal\acquia_connector\Subscription
   */
  protected $subscription;

  /**
   * Acquia Search API Client.
   *
   * @var \Drupal\acquia_search\AcquiaSearchApiClient
   */
  protected $acquiaSearchApiClient;

  /**
   * AcquiaSearchCommands constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend service.
   * @param \Drupal\acquia_connector\Subscription $subscription
   *   Acquia Subscription Service.
   * @param \Drupal\acquia_search\AcquiaSearchApiClient $acquia_search_api_client
   *   Acquia Search API Client.
   */
  public function __construct(CacheBackendInterface $cache, Subscription $subscription, AcquiaSearchApiClient $acquia_search_api_client) {
    $this->cache = $cache;
    $this->subscription = $subscription;
    $this->acquiaSearchApiClient = $acquia_search_api_client;
    parent::__construct();
  }

  /**
   * Lists available Acquia search cores for a search server.
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
    $available_cores = $this->acquiaSearchApiClient->getSearchIndexes();
    if ($available_cores === FALSE) {
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
      $id = $this->subscription->getSettings()->getIdentifier();
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
   * @param string $server_id
   *   The Search API server ID.
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
  public function searchSolrCoresPossible(string $server_id, array $options = ['format' => NULL]) {
    $server = Server::load($server_id);
    if ($server === NULL) {
      throw new \Exception("$server_id is not a server");
    }
    $backend = $server->getBackend();
    if (!$backend instanceof AcquiaSearchSolrBackend) {
      throw new \Exception("$server_id is not an Acquia Search server");
    }

    if (!$possible_cores = $backend->getListOfPossibleCores()) {
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
   * @param string $server_id
   *   The Search API server ID.
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
  public function searchSolrCoresPreferred(string $server_id) {
    $server = Server::load($server_id);
    if ($server === NULL) {
      throw new \Exception("$server_id is not a server");
    }
    $backend = $server->getBackend();
    if (!$backend instanceof AcquiaSearchSolrBackend) {
      throw new \Exception("$server_id is not an Acquia Search server");
    }

    if (!$backend->isPreferredCoreAvailable()) {
      throw new \Exception('No preferred search core available');
    }

    $this->output()->writeln($backend->getSolrConnector()->getConfiguration()['core']);

  }

}

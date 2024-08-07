<?php

namespace Drupal\memcache_admin\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\memcache\DrupalMemcacheInterface;

/**
 * Memcache Stats Event.
 *
 * The memcache stats event stores all the attributes generated by the different
 * types of memcache servers. Currently, memcache_admin supports memcache and
 * mcrouter.
 */
class MemcacheStatsEvent extends Event {

  /**
   * Event used to build the memcache stats array.
   *
   * When the stats array is created, this event allows modules to inject extra
   * data to be contained within the array.
   */
  const BUILD_MEMCACHE_STATS = 'memcache_build_memcache_stats';

  /**
   * Event used to report out the memcache stats array.
   *
   * When the stats array is created, this event allows modules to inject extra
   * data to be contained within the array.
   */
  const REPORT_MEMCACHE_STATS = 'memcache_report_memcache_stats';

  /**
   * The Stats Array for which to create attributes.
   *
   * @var array
   */
  protected $rawStats;

  /**
   * The Stats Array for which to create attributes.
   *
   * @var array
   */
  protected $formattedStats = [];

  /**
   * The Stats Array for which to create attributes.
   *
   * @var array
   */
  protected $totals;

  /**
   * The Stats Array for which to create attributes.
   *
   * @var array
   */
  protected $report;

  /**
   * The Stats Array for which to create attributes.
   *
   * @var array
   */
  protected $servers;

  /**
   * Cache Bin To Retrieve.
   *
   * @var string
   */
  protected $bin;

  /**
   * Drupal memcache.
   *
   * @var \Drupal\memcache_admin\Stats\MemcacheStatsInterface
   */
  protected $memcache;

  /**
   * MemcacheStatsEvent constructor.
   *
   * @param Drupal\memcache\DrupalMemcacheInterface $memcache
   *   Drupal memcache.
   * @param string $bin
   *   The cache bin.
   */
  public function __construct(DrupalMemcacheInterface $memcache, string $bin = 'default') {
    $this->memcache = $memcache;
    $this->rawStats = $memcache->stats($bin, 'default', TRUE);
    $this->formattedStats = [];
    $this->bin = $bin;
  }

  /**
   * Get the Stats Array being created.
   *
   * @return array
   *   The Stats Object.
   */
  public function getRawStats() {
    return $this->rawStats;
  }

  /**
   * Gets the stats formatted from a MemcacheStatsInterface.
   *
   * @param string $server_type
   *   Server type.
   *
   * @return array|mixed
   *   Formatted stats.
   */
  public function getFormattedStats(string $server_type) {
    if (isset($this->formattedStats[$server_type])) {
      return $this->formattedStats[$server_type];
    }
    return [];
  }

  /**
   * Returns the memcache connection.
   */
  public function getMemcache() {
    return $this->memcache;
  }

  /**
   * Returns the cache bin from this event.
   */
  public function getCacheBin() {
    return $this->bin;
  }

  /**
   * Sets the formatted stats array with relevant data.
   *
   * @param string $format
   *   Memcache format.
   * @param string $bin
   *   Memcache bins.
   * @param string $server
   *   Memcache server.
   * @param \Drupal\memcache_admin\Stats\MemcacheStatsInterface $data
   *   Memcache data.
   */
  public function updateFormattedStats($format, $bin, $server, $data) {
    $this->formattedStats[$format][$bin][$server] = $data;
  }

  /**
   * Update the total column when multiple memcache servers exist.
   *
   * @param int $total
   *   Memcache total update.
   */
  public function updateTotals($total) {
    $this->totals = $total;
  }

  /**
   * Return the total values from all memcache servers.
   *
   * @return array
   *   Get total memcache.
   */
  public function getTotals(): array {
    return $this->totals;
  }

  /**
   * Add a new server to the servers array.
   *
   * @param string $server
   *   Memcache server.
   */
  public function updateServers(string $server) {
    $this->servers[] = $server;
  }

  /**
   * Retrieve all servers from the servers array.
   *
   * @return array
   *   Get memache servers.
   */
  public function getServers() {
    return $this->servers;
  }

  /**
   * Update the full report from the event.
   *
   * @param array $report
   *   Memcache report.
   */
  public function updateReport(array $report) {
    $this->report = $report;
  }

  /**
   * Returns the stats report.
   *
   * @return array
   *   List of memcache stats.
   */
  public function getReport() {
    return $this->report;
  }

}

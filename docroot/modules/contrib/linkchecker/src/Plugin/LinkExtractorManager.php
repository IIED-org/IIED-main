<?php

namespace Drupal\linkchecker\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Link extractor plugin plugin manager.
 */
class LinkExtractorManager extends DefaultPluginManager {

  /**
   * Constructs a new LinkExtractorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/LinkExtractor', $namespaces, $module_handler, 'Drupal\linkchecker\Plugin\LinkExtractorInterface', 'Drupal\linkchecker\Annotation\LinkExtractor');

    $this->alterInfo('linkchecker_link_extractor_info');
    $this->setCacheBackend($cache_backend, 'linkchecker_link_extractor_plugins');
  }

}

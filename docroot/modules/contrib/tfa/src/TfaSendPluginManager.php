<?php

namespace Drupal\tfa;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The send plugin manager.
 */
class TfaSendPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new TfaSend plugin manager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/TfaSend', $namespaces, $module_handler, 'Drupal\tfa\Plugin\TfaSendInterface', 'Drupal\tfa\Annotation\TfaSend');
    $this->alterInfo('tfa_send_info');
    $this->setCacheBackend($cache_backend, 'tfa_send');
  }

  /**
   * {@inheritdoc}
   *
   * Provide some backwards compatibility with the old implicit setupPluginId.
   * This will give other modules more time to update their plugins.
   *
   * @deprecated in tfa:8.x-1.0-alpha7 and is removed from tfa:8.x-2.0. Please
   * specify the setupPluginId property in the plugin annotation.
   * @see https://www.drupal.org/project/tfa/issues/2925066
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    foreach ($definitions as &$definition) {
      if (empty($definition['setupPluginId'])) {
        $definition['setupPluginId'] = $definition['id'] . '_setup';
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   *
   * Provide some backwards compatibility with the old implicit setupPluginId.
   * This will give other modules more time to update their plugins.
   *
   * @deprecated in tfa:8.x-1.0-alpha7 and is removed from tfa:8.x-2.0. Please
   * specify the setupPluginId property in the plugin annotation.
   * @see https://www.drupal.org/project/tfa/issues/2925066
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    $plugin = parent::getDefinition($plugin_id, $exception_on_invalid);
    if (is_array($plugin) && empty($plugin['setupPluginId'])) {
      $plugin['setupPluginId'] = $plugin_id . '_setup';
    }
    return $plugin;
  }

}

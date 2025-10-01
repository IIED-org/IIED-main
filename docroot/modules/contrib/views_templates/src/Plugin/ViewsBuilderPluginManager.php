<?php

namespace Drupal\views_templates\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Class ViewsTemplateBuilderPluginManager.
 *
 * @package Drupal\views_templates\Plugin
 */
class ViewsBuilderPluginManager extends DefaultPluginManager {

  /**
   * Constructs an ViewsTemplateBuilderPluginManager object.
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
    parent::__construct('Plugin/ViewsTemplateBuilder', $namespaces, $module_handler, 'Drupal\views_templates\Plugin\ViewsBuilderPluginInterface', 'Drupal\views_templates\Annotation\ViewsBuilder');
    $this->alterInfo('views_template_builder_info');
    $this->setCacheBackend($cache_backend, 'views_template_builder');
  }

}

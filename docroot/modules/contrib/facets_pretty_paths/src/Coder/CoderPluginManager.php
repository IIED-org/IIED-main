<?php

namespace Drupal\facets_pretty_paths\Coder;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets_pretty_paths\Annotation\FacetsPrettyPathsCoder;

/**
 * Manages Coder plugins.
 *
 * @see \Drupal\facets_pretty_paths\Annotation\FacetsPrettyPathsCoder
 * @see \Drupal\facets_pretty_paths\Coder\CoderInterface
 * @see plugin_api
 */
class CoderPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/facets_pretty_paths/coder', $namespaces, $module_handler, CoderInterface::class, FacetsPrettyPathsCoder::class);
    $this->setCacheBackend($cache_backend, 'facets_pretty_paths_coder');
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The coder plugin %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

}

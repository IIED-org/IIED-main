<?php

namespace Drupal\webform_content_creator\Plugin;

use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\webform_content_creator\FieldMappingInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin manager for finding and using field mapping types.
 */
class WebformContentCreatorFieldMappingManager extends DefaultPluginManager {

  /**
   * Constructs an WebformContentCreatorFieldMappingManager object.
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
    parent::__construct('Plugin/WebformContentCreator/FieldMapping', $namespaces, $module_handler, 'Drupal\webform_content_creator\Plugin\FieldMappingInterface', 'Drupal\webform_content_creator\Annotation\WebformContentCreatorFieldMapping');
    $this->alterInfo('webform_content_creator_info');
    $this->setCacheBackend($cache_backend, 'webform_content_creator_info_plugins');
  }

  public function getPlugin($plugin_id = 'default_mapping') {
    return $this->createInstance($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldMappings(string $field_type) {
    $instances = $configuration = [];
    $plugin_definitions = $this->getDefinitions();
    usort($plugin_definitions, function ($definition1, $definition2) {
      return $definition1['weight'] <=> $definition1['weight'];
    });
    foreach ($this->getDefinitions() as $field_mapping) {
      if (in_array($field_type, $field_mapping['field_types']) || empty($field_mapping['field_types'])) {
        $instances[] = $this->createInstance($field_mapping['id'], $configuration)->getPlugin();
      }
    }
    return $instances;
  }

}

<?php

namespace Drupal\Tests\tablefield\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides a helper method for creating Tablefield fields.
 */
trait TablefieldCreationTrait {

  /**
   * Create a new tablefield field.
   *
   * @param string $name
   *   The name of the new field (all lowercase).
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   (optional) A list of field storage settings that will be added to the
   *   defaults.
   * @param array $field_settings
   *   (optional) A list of instance settings that will be added to the instance
   *   defaults.
   * @param array $widget_settings
   *   (optional) Widget settings to be added to the widget defaults.
   * @param array $formatter_settings
   *   (optional) Formatter settings to be added to the formatter defaults.
   * @param string $description
   *   (optional) A description for the field. Defaults to ''.
   */
  protected function createTableField($name, $type_name, array $storage_settings = [], array $field_settings = [], array $widget_settings = [], array $formatter_settings = [], $description = '') {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'tablefield',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $type_name,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
      'description' => $description,
    ]);
    $field_config->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', $type_name)
      ->setComponent($name, [
        'type' => 'tablefield',
        'settings' => $widget_settings,
      ])
      ->save();

    $display_repository->getViewDisplay('node', $type_name)
      ->setComponent($name, [
        'type' => 'tablefield',
        'settings' => $formatter_settings,
      ])
      ->save();

    return $field_config;
  }

}

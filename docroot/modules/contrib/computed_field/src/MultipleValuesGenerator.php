<?php

namespace Drupal\computed_field;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Service for generating missing values for multi-valued computed fields.
 *
 * Drupal core assumes that field cardinality is defined by the form, and is
 * determined before our fields are computed.  Any values that fall outside of
 * this range will therefore not be included.  Therefore, we must append these
 * missing computed values to each field's item list after the known ones are
 * computed, but before the entity is saved.
 */
class MultipleValuesGenerator {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManager
   */
  protected $fieldTypePluginManager;

  /**
   * The entity we're working with.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   */
  public function __construct(
    EntityFieldManagerInterface $entity_field_manager,
    FieldTypePluginManagerInterface $field_type_plugin_manager
  ) {
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->entity = NULL;
  }

  /**
   * Associates the service with a particular entity.
   *
   * It's necessary to call this method before any other non-static methods.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose field is being calculated.
   *
   * @return $this
   *   The object itself, for method chaining.
   */
  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
    return $this;
  }

  /**
   * Fetches the entity currently in use by the service.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity currently in use by the service.
   *
   * @throws \Exception
   *   If the entity is missing.
   */
  protected function getEntity() {
    if (is_null($this->entity)) {
      throw new \Exception('This operation requires that the service be set with an entity.');
    }
    return $this->entity;
  }

  /**
   * Generates missing computed-field values in multi-valued computed fields.
   *
   * @throws \Exception
   *   If the entity is missing.
   */
  public function generateMissingValues() {
    $entity = $this->getEntity();
    if (!($entity instanceof FieldableEntityInterface)) {
      return;
    }

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    foreach ($field_definitions as $field_id => $field_definition) {
      if ($this->fieldIsNotComputed($field_definition)) {
        continue;
      }

      $this->generateMissingValuesForField($field_id, $field_definition);
    }
  }

  /**
   * Determines if a field is computed or not.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   The result.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function fieldIsNotComputed(FieldDefinitionInterface $field_definition) {
    return $this->fieldTypePluginManager->getDefinition($field_definition->getType())['provider'] != "computed_field";
  }

  /**
   * Generates missing values for a particular computed field.
   *
   * @param string $field_id
   *   The field machine name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @throws \Exception
   *   If the entity is missing.
   */
  protected function generateMissingValuesForField($field_id, FieldDefinitionInterface $field_definition) {
    $field_item_list = $this->getEntity()->get($field_id);
    $updated_values = $field_item_list->getValue();
    $field_item_list_index = count($updated_values);
    $field_cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();

    while ($this->thereAreMoreValuesToCompute($field_cardinality, $field_item_list_index)) {
      $field_item_list->appendItem();
      $field_item = $field_item_list->get($field_item_list_index);

      if ($field_item->isEmpty()) {
        $field_item_list->removeItem($field_item_list_index);
        break;
      }

      $updated_values[] = ['value' => $field_item->executeCode()];
      $field_item_list_index++;
    }

    $this->updateFieldIfMoreValuesHaveBeenAdded($field_item_list, $updated_values);
  }

  /**
   * Determines if additional value computations are required.
   *
   * @param int $field_cardinality
   *   The field cardinality.
   * @param int $field_item_index
   *   The current index within the field item list.
   *
   * @return bool
   *   The result.
   */
  protected function thereAreMoreValuesToCompute($field_cardinality, $field_item_index) {
    return ($field_cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      || ($field_item_index < $field_cardinality);
  }

  /**
   * Updates a field's values if some were missing.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item_list
   *   The field item list.
   * @param array $updated_values
   *   The updated list of values.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function updateFieldIfMoreValuesHaveBeenAdded(FieldItemListInterface $field_item_list, array $updated_values) {
    if (count($updated_values) > count(array_filter($field_item_list->getValue()))) {
      $field_item_list->setValue($updated_values);
    }
  }

}

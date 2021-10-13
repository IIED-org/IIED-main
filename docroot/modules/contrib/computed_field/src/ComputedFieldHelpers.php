<?php

namespace Drupal\computed_field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class ComputedFieldHelpers.
 */
class ComputedFieldHelpers {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * ComputedFieldHelpers constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Fetches this field's compute function name for implementing elsewhere.
   *
   * @param string $field_name
   *   Name of the field we need to calculate the value of.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity containing the field.
   * @param int $delta
   *   Field item delta.
   *
   * @return string
   *   The function name.
   */
  public function executeCode($field_name, EntityInterface $entity, $delta) {
    $fields = $entity->toArray();
    $value = '';
    if ($this->computeFunctionNameExists($field_name)) {
      $compute_function = $this->getComputeFunctionName($field_name);
      $value = $compute_function($this->entityTypeManager, $entity, $fields, $delta);
    }

    // Let other modules alter the values.
    $context = [
      'field_name' => $field_name,
      'entity' => $entity,
    ];
    $this->moduleHandler->alter('computed_field_value', $value, $context);
    $this->moduleHandler->alter('computed_field_' . $field_name . '_value', $value, $context);
    return $value;
  }

  /**
   * Fetches this field's compute function name for implementing elsewhere.
   *
   * @param string $field_name
   *   Current field name.
   *
   * @return string
   *   The function name.
   */
  public function getComputeFunctionName($field_name) {
    return 'computed_field_' . $field_name . '_compute';
  }

  /**
   * Determines if a compute function exists for this field.
   *
   * @param string $field_name
   *   Current field name.
   *
   * @return bool
   *   TRUE if the function exists, FALSE if not.
   */
  public function computeFunctionNameExists($field_name) {
    return function_exists($this->getComputeFunctionName($field_name));
  }

}

<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;

/**
 * Provides email field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "office_hours_mapping",
 *   label = @Translation("Office Hours"),
 *   weight = 0,
 *   field_types = {
 *     "office_hours"
 *   },
 * )
 */
class OfficeHoursFieldMapping extends FieldMappingBase {

  /**
   *
   */
  public function getSupportedWebformFields($webform_id) {
    $supported_types = ["office_hours"];

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  /**
   *
   */
  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];

    $field_value = array_map(function ($value) {
      return unserialize($value);
    }, $field_value);

    $content->set($field_id, $field_value);
  }

}

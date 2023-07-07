<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;


/**
 * Provides a boolean field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "boolean_mapping",
 *   label = @Translation("Boolean Mapping"),
 *   weight = 0,
 *   field_types = {
 *     "boolean",
 *     "search_api_exclude_entity"
 *   },
 * )
 */
class BooleanFieldMapping extends FieldMappingBase {

  public function getSupportedWebformFields($webform_id) {
    $supported_types = ["boolean", "checkbox"];

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];

    //Convert various strings (true, yes, on) to boolean
    if (!is_bool($field_value)) {
      $field_value = filter_var($field_value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    if (isset($field_value)) {
      $content->set($field_id, $field_value);
    }
  }

}

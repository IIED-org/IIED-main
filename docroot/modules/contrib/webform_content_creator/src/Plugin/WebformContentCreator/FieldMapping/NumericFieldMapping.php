<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;
use Drupal\webform_content_creator\Plugin\FieldMappingInterface;

/**
 * Provides numeric field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "numeric_mapping",
 *   label = @Translation("Numeric"),
 *   weight = 0,
 *   field_types = {
 *     "decimal",
 *     "float",
 *     "integer",
 *     "list_float",
 *     "list_integer"
 *   },
 * )
 */
class NumericFieldMapping extends FieldMappingBase {

  public function getSupportedWebformFields($webform_id) {
    $supported_types = array_merge(["integer", "number", "range", "webform_scale"], FieldMappingInterface::WEBFORM_OPTIONS_ELEMENTS);

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];

    $content->set($field_id, $field_value);
  }

}

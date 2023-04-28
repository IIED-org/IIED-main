<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;

/**
 * Provides an address field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "address_mapping",
 *   label = @Translation("Address"),
 *   weight = 0,
 *   field_types = {
 *     "address",
 *   },
 * )
 */
class AddressFieldMapping extends FieldMappingBase {

  public function supportsCustomFields() {
    return FALSE;
  }

  public function getSupportedWebformFields($webform_id) {
    $supported_types = ["address", "webform_address"];

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $field_definition->getDefaultValue($content)[0];
    $is_address = FALSE;
    if (!empty($data[$field_id]['address'])) {
      $is_address = TRUE;
      $field_value['address_line1'] = $data[$field_id]['address'];
    }
    if (!empty($data[$field_id]['address_2'])) {
      $field_value['address_line2'] = $data[$field_id]['address_2'];
    }
    if (!empty($data[$field_id]['city'])) {
      $field_value['locality'] = $data[$field_id]['city'];
    }
    if (!empty($data[$field_id]['state_province'])) {
      $field_value['administrative_area'] = $data[$field_id]['state_province'];
    }
    if (!empty($data[$field_id]['postal_code'])) {
      $field_value['postal_code'] = $data[$field_id]['postal_code'];
    }
    if (!empty($data[$field_id]['country'])) {
      $field_value['country_code'] = $data[$field_id]['country'];
    }

    if (!$is_address) {
      $field_value = array_merge($field_value, $data[$field_id]);
    }

    $content->set($field_id, $field_value);
  }
}

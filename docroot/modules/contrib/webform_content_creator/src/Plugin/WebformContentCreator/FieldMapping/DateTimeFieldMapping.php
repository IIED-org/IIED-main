<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;

/**
 * Provides a datetime field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "datetime_mapping",
 *   label = @Translation("Datetime"),
 *   weight = 0,
 *   field_types = {
 *     "datetime",
 *     "timestamp"
 *   },
 * )
 */
class DateTimeFieldMapping extends FieldMappingBase {

  public function getSupportedWebformFields($webform_id) {
    $supported_types = [
      "changed",
      "created",
      "date",
      "datetime",
      "datelist",
      "timestamp",
      "webform_time",
    ];

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];

    if (($field_definition->getType() == "datetime") && ($field_value != 0)) {
      $field_data = $this->convertTimestamp($field_value, $field_definition);
      $content->set($field_id, $field_data);
    }
  }

  /**
   * Convert timestamp value according to field type.
   *
   * @param int $value
   *   Original datetime value.
   * @param FieldDefinitionInterface $field_definition
   *   Entity field.
   *
   * @return string
   *   Converted value.
   */
  protected function convertTimestamp($value, FieldDefinitionInterface $field_definition) {
    $date_time = new DrupalDateTime($value, 'UTC');
    $date_type = $field_definition->getSettings()['datetime_type'];
    if ($date_type === 'datetime') {
      $result = \Drupal::service('date.formatter')->format(
        $date_time->getTimestamp(), 'custom',
        DateTimeItemInterface::DATETIME_STORAGE_FORMAT, 'UTC'
      );
    }
    else {
      $result = \Drupal::service('date.formatter')->format(
        $date_time->getTimestamp(), 'custom',
        DateTimeItemInterface::DATE_STORAGE_FORMAT, 'UTC'
      );
    }

    return $result;
  }

}

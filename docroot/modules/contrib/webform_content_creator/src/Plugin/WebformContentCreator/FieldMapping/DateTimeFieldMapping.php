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
 *     "daterange",
 *     "smartdate",
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
      "daterange",
      "smartdate",
      "datelist",
      "timestamp",
      "webform_time",
    ];

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];
    $field_type = $field_definition->getType();

    if (($field_type == "datetime" || $field_type == "daterange" || $field_type == "smartdate") && ($field_value != 0)) {
      if ($field_type == "daterange" || $field_type == "smartdate") {
        // If this is a daterange field or a smartdate field, we need to add the end date.
        // Split the $field_value into start and end values on the "/" and test if the end value is set.
        $field_value_parts = explode("/", $field_value);
        $field_data['value'] = $this->convertTimestamp($field_value_parts[0], $field_definition);

        if (isset($field_value_parts[1]) && !empty($field_value_parts[1])) {
          $field_data["end_value"] = $this->convertTimestamp($field_value_parts[1], $field_definition);
        } else {
          $field_data["end_value"] = strval(intval($field_data['value']) + 86340);
        }

        // If "smartdate" field we need to calculate the duration in minutes and set the duration field.
        if ($field_type == "smartdate") {
          if (isset($field_value_parts[1]) && !empty($field_value_parts[1])) {
            $start_date = new DrupalDateTime($field_value_parts[0]);
            $end_date = new DrupalDateTime($field_value_parts[1]);
            $duration = $end_date->getTimestamp() - $start_date->getTimestamp();
            $field_data["duration"] = $duration / 60;
          } else {
            $field_data["duration"] = 1439;
          }
        }
      } else {
        // Just a regular datetime field.
        $field_data = $this->convertTimestamp($field_value, $field_definition);
      }
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
    // Get default timezone and set it in the datetime before converting to UTC.
    $tzone = date_default_timezone_get();
    $date_time = new DrupalDateTime($value, $tzone);
    $date_type = $field_definition->getSettings()['datetime_type'] ?? '';
    if ($date_type === 'datetime') {
      $result = \Drupal::service('date.formatter')->format(
        $date_time->getTimestamp(), 'custom',
        DateTimeItemInterface::DATETIME_STORAGE_FORMAT, 'UTC'
      );
    } else {
      // Check if field_type of "smartdate" and if so, convert.
      if ($field_definition->getType() == "smartdate") {
        // Convert to Unix timestamp.
        $result = \Drupal::service('date.formatter')->format(
          $date_time->getTimestamp(), 'custom', 'U', 'UTC'
        );
      } else {
        $result = \Drupal::service('date.formatter')->format(
          $date_time->getTimestamp(), 'custom',
          DateTimeItemInterface::DATE_STORAGE_FORMAT, 'UTC'
        );
      }
    }

    return $result;
  }

}

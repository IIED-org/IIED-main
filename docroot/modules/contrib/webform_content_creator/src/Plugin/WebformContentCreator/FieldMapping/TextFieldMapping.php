<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;
use Drupal\webform_content_creator\Plugin\FieldMappingInterface;
use Drupal\webform_content_creator\WebformContentCreatorInterface;

/**
 * Provides a text field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "text_mapping",
 *   label = @Translation("Text"),
 *   weight = 0,
 *   field_types = {
 *     "telephone",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *     "string",
 *     "string_long",
 *   },
 * )
 */
class TextFieldMapping extends FieldMappingBase {

  public function getSupportedWebformFields($webform_id) {
    $supported_types = array_merge(["string", "string_long", "tel"],
      FieldMappingInterface::WEBFORM_TEXT_ELEMENTS,
      FieldMappingInterface::WEBFORM_OPTIONS_ELEMENTS);

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];

    if (is_array($field_value)) {
      foreach ($field_value as &$field_value_item) {
        $maxLength = $this->checkMaxFieldSizeExceeded($field_definition, $field_value_item);
        if ($maxLength != 0) {
          $field_value_item = substr($field_value_item, 0, $maxLength);
        }
      }
      $content->set($field_id, $field_value);
    }
    else {
      $maxLength = $this->checkMaxFieldSizeExceeded($field_definition, $field_value);
      if ($maxLength === 0) {
        $content->set($field_id, $field_value);
      }
      else {
        $content->set($field_id, substr($field_value, 0, $maxLength));
      }
    }
  }


  /**
   * Check if field maximum size is exceeded.
   *
   * @param FieldDefinitionInterface $field_definition
   *   Field definition
   * @param string $value
   *   Field value.
   *
   * @return int
   *   The max length or length of field, otherwise return 0.
   */
  protected function checkMaxFieldSizeExceeded(FieldDefinitionInterface $field_definition, $value = "") {
    $field_settings = $field_definition->getSettings();
    if (empty($field_settings) || !array_key_exists('max_length', $field_settings)) {
      return 0;
    }

    $max_length = $field_settings['max_length'];
    if (empty($max_length)) {
      return 0;
    }
    if ($max_length < strlen($value)) {
      \Drupal::logger(WebformContentCreatorInterface::WEBFORM_CONTENT_CREATOR)->notice($this->t('Problem: Field max length exceeded (truncated).'));
      return $max_length;
    }
    return strlen($value);
  }

}

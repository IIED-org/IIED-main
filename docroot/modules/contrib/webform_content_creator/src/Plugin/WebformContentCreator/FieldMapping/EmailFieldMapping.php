<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;

/**
 * Provides email field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "email_mapping",
 *   label = @Translation("Email"),
 *   weight = 0,
 *   field_types = {
 *     "email"
 *   },
 * )
 */
class EmailFieldMapping extends FieldMappingBase {

  public function getSupportedWebformFields($webform_id) {
    $supported_types = ["email", "webform_email_confirm"];

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];

    $content->set($field_id, $field_value);
  }

}

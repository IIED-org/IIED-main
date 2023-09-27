<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;

/**
 * Provides link field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "link_mapping",
 *   label = @Translation("Link"),
 *   weight = 0,
 *   field_types = {
 *     "link"
 *   },
 * )
 */
class LinkFieldMapping extends FieldMappingBase {

  /**
   * {@inheritdoc}
   */
  public function supportsCustomFields() {
    return TRUE;
  }

  public function getSupportedWebformFields($webform_id) {
    $supported_types = ["url", "string"];

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function getEntityComponentFields(FieldDefinitionInterface $field_definition) {
    return ['uri', 'title'];
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();

    $field_value = [];
    if (isset($data[$field_id])) {
      if (!array_key_exists('url', $data[$field_id])) {
        foreach ($data[$field_id] as $key => $field_value_item) {
          $field_value[$key]['uri'] = $field_value_item['url'];
          $field_value[$key]['title'] = $field_value_item['title'];
        }
      }
      else {
        $field_value['uri'] = $data[$field_id]['url'];
        $field_value['title'] = $data[$field_id]['title'];
      }
      $content->set($field_id, $field_value);
    }

  }

}

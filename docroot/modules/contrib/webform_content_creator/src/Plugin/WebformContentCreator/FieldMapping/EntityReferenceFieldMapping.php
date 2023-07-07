<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\webform_content_creator\Plugin\FieldMappingBase;
use Drupal\webform_content_creator\Plugin\FieldMappingInterface;
use Drupal\webform_content_creator\WebformContentCreatorInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides an entity reference field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "entity_reference_mapping",
 *   label = @Translation("Entity reference"),
 *   weight = 0,
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revision",
 *   },
 * )
 */
class EntityReferenceFieldMapping extends FieldMappingBase {

  /**
   * {@inheritdoc}
   */
  public function supportsCustomFields() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedWebformFields($webform_id) {
    $supported_types = array_merge(['entity_reference'], FieldMappingInterface::WEBFORM_ENTIY_REFERENCE_ELEMENTS);

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  /**
   * {@inheritdoc}
   */
  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];

    if (!is_array($field_value) && intval($field_value) === 0) {
      $content->set($field_id, []);
    }
    else {
      $content->set($field_id, $field_value);
    }
  }

}

<?php

namespace Drupal\webform_content_creator\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Interface for the field mapping.
 */
interface FieldMappingInterface extends PluginInspectionInterface {

  const WEBFORM_OPTIONS_ELEMENTS = [
    "checkboxes",
    "webform_checkboxes_other",
    "webform_likert",
    "radios",
    "webform_radios_other",
    "select",
    "webform_select_other",
    "tableselect",
    "webform_tableselect_sort",
    "webform_table_sort",
  ];

  const WEBFORM_ENTIY_REFERENCE_ELEMENTS = [
    "entity_autocomplete",
    "webform_entity_checkboxes",
    "webform_entity_radios",
    "webform_entity_select",
    "webform_term_checkboxes",
    "webform_term_select",
  ];

  const WEBFORM_TEXT_ELEMENTS = [
    "textarea",
    "textfield",
    "hidden",
  ];

  /**
   * Returns whether the mapper supports custom field text.
   *
   * @return bool
   */
  public function supportsCustomFields();

  /**
   * Returns the entity component fields.
   *
   * @return array
   *   returns an array of the component fields that makeup this field
   */
  public function getEntityComponentFields(FieldDefinitionInterface $field_definition);

  /**
   * @param $webform_id
   *
   * @return mixed
   */
  public function getSupportedWebformFields($webform_id);

  /**
   * Use a single mapping to set an entity field value.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface &$content
   *   Content being mapped with a webform submission.
   * @param array $webform_element
   *   Webform element
   * @param FieldDefinitionInterface $field_definition
   *   Entity field definition.
   * @param array $data
   *   Webform submission data.
   * @param array $attributes
   *   Mapping attributes.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Created content item.
   */
  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []);

}

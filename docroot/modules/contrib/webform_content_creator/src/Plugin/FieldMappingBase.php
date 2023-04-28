<?php

namespace Drupal\webform_content_creator\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform_content_creator\WebformContentCreatorUtilities;

/**
 * Base for a field mapping plugin.
 */
abstract class FieldMappingBase extends PluginBase implements FieldMappingInterface {

  /**
   * Return the plugin.
   */
  public function getPlugin() {
    return $this;
  }

  /**
   * Get the plugin ID.
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * Get the plugin label.
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Get the plugin weight.
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

  /**
   * Get the field types this plugin is available for.
   */
  public function getFieldTypes() {
    return $this->pluginDefinition['field_types'];
  }

  /**
   * Is this a generic (non-element specific) plugin.
   */
  public function isGeneric() {
    return (empty($this->pluginDefinition['field_types'])) ? TRUE : FALSE;
  }

  public function supportsCustomFields() {
    return TRUE;
  }

  public function getEntityComponentFields(FieldDefinitionInterface $field_definition) {
    return [];
  }

  public function getSupportedWebformFields($webform_id) {
    return WebformContentCreatorUtilities::getWebformElements($webform_id);
  }

  /**
   * Use a single mapping to set an entity field value.
   */
  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_id = $field_definition->getName();
    $field_value = $data[$field_id];
    $content->set($field_id, $field_value);
  }

  protected function filterWebformFields($webform_id, array $supported_types, array $available_fields = NULL) {
    if (!isset($available_fields)) {
      $available_fields = WebformContentCreatorUtilities::getWebformElements($webform_id);
    }
    $webform_field_types = WebformContentCreatorUtilities::getWebformElementsTypes($webform_id);
    $allowed_fields = [];

    foreach ($available_fields as $key => $available_field) {
      $key_parts = explode(',', $key);
      if (sizeOf($key_parts) > 1) {
        $element_type = $webform_field_types[$key_parts[1]];
        //Webform field vs user added fields
        if ($key_parts[0] == "1") {
          $element_type = $element_type['type'];
        }
        if (in_array($element_type, $supported_types)) {
          $allowed_fields[$key] = $available_field;
        }
      }
      else {
        //We're dealing with an option group, so recursive call to process the sub fields
        $retval = $this->filterWebformFields($webform_id, $supported_types, $available_field);
        if (!empty($retval)) {
          $allowed_fields[$key] = $retval;
        }
      }
    }

    return $allowed_fields;
  }
}

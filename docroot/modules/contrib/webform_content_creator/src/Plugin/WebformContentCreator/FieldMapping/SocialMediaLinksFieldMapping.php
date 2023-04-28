<?php

namespace Drupal\webform_content_creator\Plugin\WebformContentCreator\FieldMapping;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_content_creator\Plugin\FieldMappingBase;
use Drupal\webform_content_creator\Plugin\FieldMappingInterface;

/**
 * Provides social media links field mapping.
 *
 * @WebformContentCreatorFieldMapping(
 *   id = "social_media_links_mapping",
 *   label = @Translation("Social media links"),
 *   weight = 0,
 *   field_types = {
 *     "social_media_links_field"
 *   },
 * )
 */
class SocialMediaLinksFieldMapping extends FieldMappingBase {

  public function getEntityComponentFields(FieldDefinitionInterface $field_definition) {
    $platforms = $field_definition->getSetting("platforms");
    $available_platforms = [];
    foreach ($platforms as $key => $platform) {
      if ($platform['enabled'] == 1) {
        $available_platforms[] = $key;
      }
    }

    return $available_platforms;
  }

  public function getSupportedWebformFields($webform_id) {
    $supported_types = FieldMappingInterface::WEBFORM_TEXT_ELEMENTS;

    return $this->filterWebformFields($webform_id, $supported_types);
  }

  public function mapEntityField(ContentEntityInterface &$content, array $webform_element, FieldDefinitionInterface $field_definition, array $data = [], array $attributes = []) {
    $field_data = [];
    $field_id = $field_definition->getName();
    foreach ($data as $key => $value) {
      $field_data[$key] = [
        'value' => $value,
      ];
    }
    $content->set($field_id, ['platform_values' => $field_data]);
  }

}

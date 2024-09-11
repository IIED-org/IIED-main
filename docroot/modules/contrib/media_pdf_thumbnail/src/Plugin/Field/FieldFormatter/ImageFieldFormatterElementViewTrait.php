<?php

namespace Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter;

/**
 * Trait ImageFieldFormatterElementViewTrait
 *
 * @package Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter
 */
trait ImageFieldFormatterElementViewTrait {

  /**
   * @param $entity
   *
   * @return array|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getElementSettings($entity): ?array {

    // All settings.
    $settings = $this->getSettings();

    // If no settings from entity view mode field formatter, it means it's a single field render.
    if (empty($settings[static::PDF_FILE_FIELD_SETTING])) {
      $bundle = $entity->bundle();
      // Search for available options in all settings.
      if (!empty($settings[$bundle . static::MEDIA_BUNDLE_ENABLE]) && !empty($settings[$bundle . static::MEDIA_BUNDLE_FIELD])) {
        $settings[static::PDF_FILE_FIELD_SETTING] = $settings[$bundle . static::MEDIA_BUNDLE_FIELD];
        $settings[static::PDF_PAGE_SETTING] = $settings[$bundle . static::MEDIA_BUNDLE_PAGE];
        $settings[static::IMAGE_FORMAT_SETTINGS] = $settings[$bundle . static::MEDIA_BUNDLE_IMAGE_FORMAT];
        $settings[static::IMAGE_STYLE_SETTINGS] = $settings[$bundle . static::MEDIA_BUNDLE_IMAGE_STYLE];
        $settings[static::IMAGE_LINK_ATTRIBUTE_DOWNLOAD_SETTING] = $settings[$bundle . static::MEDIA_BUNDLE_ATTRIBUTES_DOWNLOAD];
        $settings[static::IMAGE_LINK_ATTRIBUTE_TARGET_SETTING] = $settings[$bundle . static::MEDIA_BUNDLE_ATTRIBUTES_TARGET];
        $settings[static::IMAGE_LINK_ATTRIBUTE_REL_SETTING] = $settings[$bundle . static::MEDIA_BUNDLE_ATTRIBUTES_REL];
        $settings[static::IMAGE_USE_CRON] = $settings[$bundle . static::MEDIA_BUNDLE_USE_CRON];
        $settings[static::IMAGE_LINK_SETTINGS] = $settings[$bundle . static::MEDIA_BUNDLE_LINK];
      }
      else {
        $this->mediaPdfThumbnailImageManager->getGenericThumbnail();
        return NULL;
      }
    }

    // If no format found in settings, use the default one.
    $settings[static::IMAGE_FORMAT_SETTINGS] = empty($settings[static::IMAGE_FORMAT_SETTINGS]) ? static::DEFAULT_IMAGE_FORMAT_SETTING : $settings[static::IMAGE_FORMAT_SETTINGS];

    return $settings;
  }

  /**
   * @param $element
   * @param $imageId
   * @param $entity
   *
   * @return array
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function renderImage($element, $imageId, $entity): array {
    /**
     * @var \Drupal\image\Plugin\Field\FieldType\ImageItem $imageItem
     */
    $imageItem = $element[0]['#item'];
    $value = $imageItem->getValue();
    $value['target_id'] = $imageId;
    $value['alt'] = $entity->name->value;
    $imageItem->setValue($value);
    $element[0]['#item'] = $imageItem;
    return $element;
  }

  /**
   * @param $settings
   *
   * @return array
   */
  protected function htmlAttributes($settings): array {
    $options = [];
    if (!empty($settings[static::IMAGE_LINK_ATTRIBUTE_DOWNLOAD_SETTING])) {
      $options['attributes']['download'] = '';
    }
    if (!empty($settings[static::IMAGE_LINK_ATTRIBUTE_TARGET_SETTING])) {
      $options['attributes']['target'] = $settings[static::IMAGE_LINK_ATTRIBUTE_TARGET_SETTING];
    }
    if (!empty($settings[static::IMAGE_LINK_ATTRIBUTE_REL_SETTING])) {
      $options['attributes']['rel'] = $settings[static::IMAGE_LINK_ATTRIBUTE_REL_SETTING];
    }
    return $options;
  }
}
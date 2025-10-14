<?php

namespace Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;

/**
 * Trait ImageFieldFormatterElementViewTrait.
 *
 * Get settings from entity view mode field formatter.
 *
 * @package Drupal\media_pdf_thumbnail\Plugin\Field\FieldFormatter
 */
trait ImageFieldFormatterElementViewTrait {

  /**
   * Get settings from entity view mode field formatter.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return array|null
   *   Settings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getElementSettings(EntityInterface $entity): ?array {
    // All settings.
    $settings = $this->getSettings();

    // If no settings from entity view mode field formatter,
    // it means it's a single field render.
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
        $settings[static::IMAGE_STYLE_SETTINGS] = $settings['default_bundle' . static::MEDIA_BUNDLE_IMAGE_STYLE];
        $settings[static::IMAGE_LINK_SETTINGS] = $settings['default_bundle' . static::MEDIA_BUNDLE_LINK];
        $settings['is_default_bundle'] = TRUE;
        return $settings;
      }
    }

    // If no format found in settings, use the default one.
    $settings[static::IMAGE_FORMAT_SETTINGS] = empty($settings[static::IMAGE_FORMAT_SETTINGS]) ? static::DEFAULT_IMAGE_FORMAT_SETTING : $settings[static::IMAGE_FORMAT_SETTINGS];

    return $settings;
  }

  /**
   * Get image element.
   *
   * @param array $element
   *   Element.
   * @param string|int $imageId
   *   Image id.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity.
   *
   * @return array
   *   Element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function renderImage(array $element, string | int $imageId, EntityInterface $entity): array {
    /**
     * @var \Drupal\image\Plugin\Field\FieldType\ImageItem $imageItem
     */
    $imageItem = $element[0]['#item'];
    $value = $imageItem->getValue();
    $value['target_id'] = $imageId;
    $value['alt'] = $entity->name->value;
    $this->handleDerivative($imageId, $value, $element[0]['#image_style']);
    $imageItem->setValue($value);
    $element[0]['#item'] = $imageItem;
    return $element;
  }

  /**
   * Handle derivative.
   *
   * @param string|int $imageId
   *   Image id.
   * @param array $value
   *   Value.
   * @param string $imageStyleSetting
   *   The name of the image style.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function handleDerivative(string | int $imageId, array &$value, string $imageStyleSetting): void {
    unset($value['width']);
    unset($value['height']);
    $file = File::load($imageId);

    // If no file found, use the generic thumbnail.
    if (empty($file) || !$this->mediaPdfThumbnailImageManager->checkFileExists($file)) {
      $this->mediaPdfThumbnailImageManager->getLogger()->warning('No file found for image id: @imageId. Using generic thumbnail instead', ['@imageId' => $imageId]);
      $file = $this->mediaPdfThumbnailImageManager->getGenericThumbnail(TRUE);
    }
    $uri = !empty($file) ? $file->getFileUri() : NULL;
    if (empty($uri)) {
      $this->mediaPdfThumbnailImageManager->getLogger()->error('Couldn\'t get generic thumbnail for image id: @imageId', ['@imageId' => $imageId]);
      return;
    }

    // If the uri is not a valid file, try to get default thumbnail.
    if (!$this->mediaPdfThumbnailImageManager->checkFileExistsByUri($uri)) {
      $this->mediaPdfThumbnailImageManager->getLogger()->warning('No valid file uri found for image id: @imageId (@uri). Using generic thumbnail instead', [
        '@imageId' => $imageId,
        '@uri' => $uri,
      ]);
      $file = $this->mediaPdfThumbnailImageManager->getGenericThumbnail(TRUE);
      if (empty($file)) {
        $this->mediaPdfThumbnailImageManager->getLogger()
          ->error('Couldn\'t get generic thumbnail for image id: @imageId', ['@imageId' => $imageId]);
        return;
      }
      $uri = $file->getFileUri();
    }
    $value['uri'] = $uri;
    $imageStyle = !empty($imageStyleSetting) ? $this->imageStyleStorage->load($imageStyleSetting) : NULL;
    $derivativeUri = !empty($imageStyle) ? $imageStyle->buildUri($uri) : NULL;

    // If no derivative uri, get the original image size.
    if (empty($derivativeUri)) {
      $imageSize = getimagesize($uri);
      $value['width'] = $imageSize[0];
      $value['height'] = $imageSize[1];
    }
    // If derivative uri exists, get the derivative image size or create it.
    else {
      if (!$this->mediaPdfThumbnailImageManager->checkFileExistsByUri($derivativeUri)) {
        $derivativeUri = $imageStyle->createDerivative($uri, $derivativeUri) ? $derivativeUri : NULL;
      }
      $imageSize = !empty($derivativeUri) ? getimagesize($derivativeUri) : getimagesize($uri);
      if (!empty($imageSize)) {
        $value['width'] = $imageSize[0];
        $value['height'] = $imageSize[1];
      }
    }
  }

  /**
   * Get html attributes.
   *
   * @param array $settings
   *   Settings.
   *
   * @return array
   *   Html attributes.
   */
  protected function htmlAttributes(array $settings): array {
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

<?php

namespace Drupal\media_entity_slideshow\Plugin\media\Source;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceBase;
use Drupal\media\MediaSourceEntityConstraintsInterface;

/**
 * Provides media type plugin for Slideshows.
 *
 * @MediaSource(
 *   id = "slideshow",
 *   label = @Translation("Slideshow"),
 *   description = @Translation("Provides business logic and metadata for slideshows."),
 *   default_thumbnail_filename = "slideshow.png",
 *   allowed_field_types = {"entity_reference"},
 * )
 */
class Slideshow extends MediaSourceBase implements MediaSourceEntityConstraintsInterface {

  /**
   * {@inheritdoc}
   */
  public function getMetadataAttributes() {
    $attributes = [
      'length' => $this->t('Slideshow length'),
    ];

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata(MediaInterface $media, $name) {
    $source_field = $this->configuration['source_field'];

    switch ($name) {
      case 'default_name':
        // The default name will be the timestamp + number of slides.
        $length = $this->getMetadata($media, 'length');
        if (!empty($length)) {
          return $this->formatPlural($length,
            '1 slide, created on @date',
            '@count slides, created on @date',
            [
              '@date' => \Drupal::service('date.formatter')
                ->format($media->getCreatedTime(), 'custom', DateTimeItemInterface::DATETIME_STORAGE_FORMAT),
            ]);
        }
        return parent::getMetadata($media, 'default_name');

      case 'length':
        return $media->{$source_field}->count();

      case 'thumbnail_uri':
        $source_field = $this->configuration['source_field'];

        if (empty($media->{$source_field}->target_id)) {
          return parent::getMetadata($media, 'thumbnail_uri');
        }

        /** @var \Drupal\media\MediaInterface $slideshow_item */
        $slideshow_item = $this->entityTypeManager->getStorage('media')->load($media->{$source_field}->target_id);
        if (!$slideshow_item) {
          return parent::getMetadata($media, 'thumbnail_uri');
        }

        /** @var \Drupal\media\MediaTypeInterface $bundle */
        $bundle = $this->entityTypeManager->getStorage('media_type')->load($slideshow_item->bundle());
        if (!$bundle) {
          return parent::getMetadata($media, 'thumbnail_uri');
        }

        $thumbnail = $bundle->getSource()->getMetadata($slideshow_item, 'thumbnail_uri');
        if (!$thumbnail) {
          return parent::getMetadata($media, 'thumbnail_uri');
        }

        return $thumbnail;

      default:
        return parent::getMetadata($media, $name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityConstraints() {
    $source_field = $this->configuration['source_field'];

    return ['ItemsCount' => ['sourceFieldName' => $source_field]];
  }

}

<?php

namespace Drupal\media_thumbnails\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\file\Plugin\Field\FieldType\FileFieldItemList;
use Drupal\media\MediaInterface;
use Drupal\media_thumbnails\Annotation\MediaThumbnail;
use Exception;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Traversable;

/**
 * Provides the Media thumbnail plugin manager.
 */
class MediaThumbnailManager extends DefaultPluginManager implements ContainerAwareInterface {

  use ContainerAwareTrait;

  /**
   * List of mime types and corresponding plugin ids.
   *
   * @var array
   */

  protected $plugins;

  /**
   * Constructs a new MediaThumbnailManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/MediaThumbnail',
      $namespaces,
      $module_handler,
      MediaThumbnailInterface::class,
      MediaThumbnail::class
    );

    $this->alterInfo('media_thumbnails_media_thumbnail_info');
    $this->setCacheBackend($cache_backend, 'media_thumbnails_media_thumbnail_plugins');
    $this->plugins = [];

    // Build a list of unique mime types supported by thumbnail plugins.
    $definitions = $this->getDefinitions();
    foreach ($definitions as $id => $definition) {
      foreach ($definition['mime'] as $mime) {
        $this->plugins[$mime] = $id;
      }
    }
  }

  /**
   * Create a new media thumbnail.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media object.
   */
  public function createThumbnail(MediaInterface $media) {
    // Get a thumbnail plugin id for supported media types.
    if (!$plugin = $this->getPluginId($media)) {
      return;
    }
    // Get the global configuration to pass it to the plugins.
    $config = $this->container->get('config.factory')
      ->get('media_thumbnails.settings')->get();

    // Create a plugin instance.
    try {
      /** @var \Drupal\media_thumbnails\Plugin\MediaThumbnailInterface $instance */
      $instance = $this->createInstance($plugin, $config);
    }
    catch (PluginException $e) {
      return;
    }

    // Create the thumbnail file using the plugin.
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->getSource($media);
    if (!$file = $instance->createThumbnail($file->getFileUri())) {
      return;
    }

    // Add this file to the media entity.
    $media->set('thumbnail', $file);
  }

  /**
   * Update a media thumbnail.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media object.
   */
  public function updateThumbnail(MediaInterface $media) {
    $this->deleteThumbnail($media);
    $this->createThumbnail($media);
  }

  /**
   * Delete a media thumbnail.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media object.
   */
  public function deleteThumbnail(MediaInterface $media) {

    // Get the thumbnail file object.
    /** @var \Drupal\file\FileInterface $thumbnail */
    $thumbnail = $this->getThumbnail($media);

    // Remove the thumbnail from the media entity.
    $media->set('thumbnail', NULL);

    // Don't delete thumbnails used in other places.
    /** @var \Drupal\file\FileUsage\FileUsageInterface $fileUsage */
    $fileUsage = $this->container->get('file.usage');
    $usage = $fileUsage->listUsage($thumbnail);
    $count = 0;
    array_walk_recursive($usage, static function () use (&$count) {
      $count++;
    });
    if ($count > 1) {
      return;
    }

    // Don't delete generic default thumbnails.
    if ($thumbnail->getCreatedTime() < $media->getCreatedTime()) {
      return;
    }

    // Delete the thumbnail file.
    try {
      $thumbnail->delete();
    }
    catch (EntityStorageException $e) {
    }
  }

  /**
   * Get the source file object for a media entity, if any.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The given media entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded file object.
   */
  public function getSource(MediaInterface $media) {
    try {
      return $this->getFileObject($media, $media->getSource()
        ->getConfiguration()['source_field']);
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Get the thumbnail file object for a media entity, if any.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The given media entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded file object.
   */
  public function getThumbnail(MediaInterface $media) {
    try {
      return $this->getFileObject($media, 'thumbnail');
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Get a media file object, either source or thumbnail.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The given media entity.
   * @param string $field_name
   *   The field name of the source file, or 'thumbnail'.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The loaded file object.
   */
  public function getFileObject(MediaInterface $media, $field_name) {
    // Fetch the thumbnail file id, if any.
    try {
      $fid = $media->get($field_name)->first()->getValue()['target_id'];
    }
    catch (Exception $e) {
      return NULL;
    }
    // Return the corresponding file object, if any.
    return $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->load($fid);
  }

  /**
   * Check if the media source is a local file.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return bool
   *   TRUE if there is a local file, FALSE otherwise.
   */
  public function isLocal(MediaInterface $media): bool {
    $source = $media->getSource()->getConfiguration()['source_field'];
    return $media->get($source) instanceof FileFieldItemList;
  }

  /**
   * Get the thumbnail plugin id for a media entity, if any.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return string|null
   *   Plugin id if there is a plugin, NULL otherwise
   */
  public function getPluginId(MediaInterface $media) {
    if (!$this->isLocal($media)) {
      return NULL;
    }
    $source = $media->getSource()->getConfiguration()['source_field'];
    try {
      $first = $media->get($source)->first();
      $file = $first ? $first->getValue() : NULL;
    }
    catch (MissingDataException $e) {
      return NULL;
    }
    if (!isset($file['target_id'])) {
      return NULL;
    }
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->container->get('entity_type.manager')
      ->getStorage('file')
      ->load($file['target_id']);
    if (!$file) {
      return NULL;
    }
    $mime = $file->getMimeType();
    return $this->plugins[$mime] ?? NULL;
  }

  /**
   * Check if the media source has a thumbnail plugin.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return bool
   *   TRUE if there is a plugin, FALSE otherwise.
   */
  public function hasPlugin(MediaInterface $media): bool {
    return (bool) $this->getPluginId($media);
  }

}

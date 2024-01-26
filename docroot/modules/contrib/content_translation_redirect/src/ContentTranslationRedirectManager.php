<?php

namespace Drupal\content_translation_redirect;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides common functionality for Content Translation Redirect.
 */
class ContentTranslationRedirectManager implements ContentTranslationRedirectManagerInterface {

  /**
   * A list of entity types that are not supported.
   *
   * Similar to the Metatag module.
   */
  protected const UNSUPPORTED_TYPES = [
    // Custom blocks.
    'block_content',
    // Comments.
    'comment',
    // Contact messages.
    'contact_message',
    // Menu items.
    'menu_link_content',
    // Shortcut items.
    'shortcut',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * ContentTranslationRedirectManager constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tag_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidatorInterface $cache_tag_invalidator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tag_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityTypeSupported(EntityTypeInterface $entity_type): bool {
    // Check for a content entity type.
    if (!$entity_type instanceof ContentEntityTypeInterface) {
      return FALSE;
    }

    // Check for a supported entity type.
    if (in_array($entity_type->id(), static::UNSUPPORTED_TYPES)) {
      return FALSE;
    }

    // Check for a translatable entity type with a canonical link.
    return $entity_type->isTranslatable() && $entity_type->hasLinkTemplate('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedEntityTypes(): array {
    return array_filter($this->entityTypeManager->getDefinitions(), [$this, 'isEntityTypeSupported']);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(ContentTranslationRedirectInterface $redirect): void {
    $this->cacheTagsInvalidator->invalidateTags(['content_translation_redirect:' . $redirect->id()]);

    // The default redirect affects all supported entity types.
    if ($redirect->id() === ContentTranslationRedirectInterface::DEFAULT_ID) {
      $entity_types = array_keys($this->getSupportedEntityTypes());
    }
    else {
      $entity_types = [$redirect->getTargetEntityTypeId()];
    }

    foreach ($entity_types as $entity_type_id) {
      // Reset the render cache for each affected entity type.
      if ($this->entityTypeManager->hasHandler($entity_type_id, 'view_builder')) {
        $this->entityTypeManager->getViewBuilder($entity_type_id)->resetCache();
      }
    }
  }

  /**
   * Returns redirect status codes.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Redirect status codes.
   */
  public static function getStatusCodes(): array {
    return [
      300 => t('300 Multiple Choices'),
      301 => t('301 Moved Permanently'),
      302 => t('302 Found'),
      303 => t('303 See Other'),
      304 => t('304 Not Modified'),
      305 => t('305 Use Proxy'),
      307 => t('307 Temporary Redirect'),
    ];
  }

  /**
   * Returns translation modes.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup[]
   *   Translation modes.
   */
  public static function getTranslationModes(): array {
    return [
      ContentTranslationRedirectInterface::MODE_TRANSLATABLE => t('Translatable entities'),
      ContentTranslationRedirectInterface::MODE_UNTRANSLATABLE => t('Untranslatable entities'),
      ContentTranslationRedirectInterface::MODE_ALL => t('All entities'),
    ];
  }

}

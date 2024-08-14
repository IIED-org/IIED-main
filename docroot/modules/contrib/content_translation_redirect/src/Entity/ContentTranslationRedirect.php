<?php

namespace Drupal\content_translation_redirect\Entity;

use Drupal\content_translation_redirect\ContentTranslationRedirectInterface;
use Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;

/**
 * Defines the Content Translation Redirect entity.
 *
 * @ConfigEntityType(
 *   id = "content_translation_redirect",
 *   label = @Translation("Content Translation Redirect"),
 *   handlers = {
 *     "storage" = "Drupal\content_translation_redirect\ContentTranslationRedirectStorage",
 *     "list_builder" = "Drupal\content_translation_redirect\ContentTranslationRedirectListBuilder",
 *     "access" = "Drupal\content_translation_redirect\ContentTranslationRedirectAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\content_translation_redirect\Form\ContentTranslationRedirectForm",
 *       "edit" = "Drupal\content_translation_redirect\Form\ContentTranslationRedirectForm",
 *       "delete" = "Drupal\content_translation_redirect\Form\ContentTranslationRedirectDeleteForm",
 *     }
 *   },
 *   config_prefix = "entity",
 *   admin_permission = "administer content translation redirects",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/regional/content-translation-redirect/{content_translation_redirect}",
 *     "delete-form" = "/admin/config/regional/content-translation-redirect/{content_translation_redirect}/delete",
 *     "collection" = "/admin/config/regional/content-translation-redirect",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "code",
 *     "path",
 *     "mode",
 *   }
 * )
 */
class ContentTranslationRedirect extends ConfigEntityBase implements ContentTranslationRedirectInterface {

  /**
   * The redirect ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label for the redirect.
   *
   * @var string
   */
  protected $label;

  /**
   * The redirect status code.
   *
   * @var int|null
   */
  protected $code;

  /**
   * The redirect path.
   *
   * @var string
   */
  protected $path = '';

  /**
   * The translation mode.
   *
   * @var string
   */
  protected $mode = self::MODE_TRANSLATABLE;

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId(): ?string {
    $id = $this->id();

    // The default redirect affects all supported entity types.
    if ($id === NULL || $id === static::DEFAULT_ID) {
      return NULL;
    }

    return explode('__', $id)[0];
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityType(): ?EntityTypeInterface {
    $entity_type_id = $this->getTargetEntityTypeId();

    if ($entity_type_id !== NULL) {
      return $this->entityTypeManager()->getDefinition($entity_type_id, FALSE);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetBundle(): ?string {
    $id = $this->id();

    // The default redirect affects all supported entity types.
    if ($id === NULL || $id === static::DEFAULT_ID) {
      return NULL;
    }

    return explode('__', $id)[1] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusCode(): ?int {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(): string {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): ?Url {
    return $this->path ? Url::fromUserInput($this->path) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationMode(): string {
    return $this->mode;
  }

  /**
   * {@inheritdoc}
   */
  public function translatableEntityOnly(): bool {
    return $this->mode === static::MODE_TRANSLATABLE;
  }

  /**
   * {@inheritdoc}
   */
  public function untranslatableEntityOnly(): bool {
    return $this->mode === static::MODE_UNTRANSLATABLE;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked(): bool {
    return $this->id() === static::DEFAULT_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);

    if ($this->isNew()) {
      $entity_type = $this->getTargetEntityType();

      if ($entity_type !== NULL) {
        $bundle_id = $this->getTargetBundle();

        // Get the entity type label.
        $label = (string) $entity_type->getLabel();

        // Get the bundle label.
        if ($bundle_id !== NULL) {
          $bundle_info = $this->entityTypeBundleInfo()->getBundleInfo($entity_type->id());
          $label .= ': ' . ($bundle_info[$bundle_id]['label'] ?? $bundle_id);
        }

        // Set the new label.
        $this->set('label', $label);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE): void {
    parent::postSave($storage, $update);
    static::manager()->resetCache($this);
  }

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   * @param \Drupal\content_translation_redirect\ContentTranslationRedirectInterface[] $entities
   *   An array of entities.
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities): void {
    parent::postDelete($storage, $entities);
    $manager = static::manager();

    foreach ($entities as $entity) {
      $manager->resetCache($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b): int {
    // Always put Default in first place.
    if ($a->id() === static::DEFAULT_ID) {
      return -1;
    }
    elseif ($b->id() === static::DEFAULT_ID) {
      return 1;
    }
    return parent::sort($a, $b);
  }

  /**
   * Returns the content translation redirect manager.
   *
   * @return \Drupal\content_translation_redirect\ContentTranslationRedirectManagerInterface
   *   The content translation redirect manager.
   */
  protected static function manager(): ContentTranslationRedirectManagerInterface {
    return \Drupal::service('content_translation_redirect.manager');
  }

}

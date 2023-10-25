<?php

namespace Drupal\content_translation_redirect\Entity;

use Drupal\content_translation_redirect\ContentTranslationRedirectInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this->isNew()) {
      $parts = explode('__', $this->id());
      $entity_type_id = $parts[0];

      $entity_type = $this->entityTypeManager()
        ->getDefinition($entity_type_id, FALSE);

      if ($entity_type) {
        $bundle_id = $parts[1] ?? NULL;

        // Get the entity type label.
        $label = (string) $entity_type->getLabel();

        // Get the bundle label.
        if ($bundle_id !== NULL) {
          $bundle_info = $this->entityTypeBundleInfo()->getBundleInfo($entity_type_id);
          $label .= ': ' . $bundle_info[$bundle_id]['label'];
        }

        // Set the label on new entity.
        $this->set('label', $label);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
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
      static::MODE_TRANSLATABLE => t('Translatable entities'),
      static::MODE_UNTRANSLATABLE => t('Untranslatable entities'),
      static::MODE_ALL => t('All entities'),
    ];
  }

}

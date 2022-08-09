<?php

namespace Drupal\content_translation_redirect\Entity;

use Drupal\content_translation_redirect\ContentTranslationRedirectInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Content Translation Redirect entity.
 *
 * @ConfigEntityType(
 *   id = "content_translation_redirect",
 *   label = @Translation("Content Translation Redirect"),
 *   handlers = {
 *     "list_builder" = "Drupal\content_translation_redirect\Controller\ContentTranslationRedirectListBuilder",
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
 *     "edit-form" = "/admin/config/regional/content-translation-redirect/entity/{content_translation_redirect}",
 *     "delete-form" = "/admin/config/regional/content-translation-redirect/entity/{content_translation_redirect}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "code",
 *     "message",
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
   * Redirect status code.
   *
   * @var int
   */
  protected $code;

  /**
   * Message after redirection.
   *
   * @var string
   */
  protected $message;

  /**
   * {@inheritdoc}
   */
  public function setStatusCode($code) {
    $this->code = $code;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusCode() {
    return $this->code;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

}

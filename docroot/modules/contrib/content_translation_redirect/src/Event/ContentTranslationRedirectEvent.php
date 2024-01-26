<?php

namespace Drupal\content_translation_redirect\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\content_translation_redirect\ContentTranslationRedirectInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;

/**
 * Defines the content translation redirect event.
 */
class ContentTranslationRedirectEvent extends Event {

  /**
   * The redirect response.
   *
   * @var \Drupal\Core\Routing\TrustedRedirectResponse
   */
  protected $response;

  /**
   * The entity for which the redirect is being performed.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The current content language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $language;

  /**
   * The redirect entity.
   *
   * @var \Drupal\content_translation_redirect\ContentTranslationRedirectInterface
   */
  protected $redirect;

  /**
   * The redirect Url object.
   *
   * @var \Drupal\Core\Url
   */
  protected $url;

  /**
   * ContentTranslationRedirectEvent constructor.
   *
   * @param \Drupal\Core\Routing\TrustedRedirectResponse $response
   *   The redirect response.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the redirect is being performed.
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The current content language.
   * @param \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $redirect
   *   The redirect entity.
   * @param \Drupal\Core\Url $url
   *   The redirect Url object.
   */
  public function __construct(TrustedRedirectResponse $response, ContentEntityInterface $entity, LanguageInterface $language, ContentTranslationRedirectInterface $redirect, Url $url) {
    $this->response = $response;
    $this->entity = $entity;
    $this->language = $language;
    $this->redirect = $redirect;
    $this->url = $url;
  }

  /**
   * Gets the redirect response.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   The redirect response.
   */
  public function getResponse(): TrustedRedirectResponse {
    return $this->response;
  }

  /**
   * Gets the entity for which the redirect is being performed.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity object.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * Gets the current content language.
   *
   * @return \Drupal\Core\Language\LanguageInterface
   *   The current content language.
   */
  public function getLanguage(): LanguageInterface {
    return $this->language;
  }

  /**
   * Gets the redirect entity.
   *
   * @return \Drupal\content_translation_redirect\ContentTranslationRedirectInterface
   *   The redirect entity object.
   */
  public function getRedirect(): ContentTranslationRedirectInterface {
    return $this->redirect;
  }

  /**
   * Gets the redirect Url object.
   *
   * @return \Drupal\Core\Url
   *   The redirect Url object.
   */
  public function getUrl(): Url {
    return $this->url;
  }

}

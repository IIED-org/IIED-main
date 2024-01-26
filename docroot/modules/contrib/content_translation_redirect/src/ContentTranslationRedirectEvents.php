<?php

namespace Drupal\content_translation_redirect;

/**
 * Defines events for Content Translation Redirect.
 */
final class ContentTranslationRedirectEvents {

  /**
   * The name of the event fired when performing the redirect.
   *
   * @Event
   *
   * @see \Drupal\content_translation_redirect\Event\ContentTranslationRedirectEvent
   * @see \Drupal\content_translation_redirect\EventSubscriber\ContentTranslationRedirectRequestSubscriber::onRequest()
   */
  public const REDIRECT = 'content_translation_redirect';

}

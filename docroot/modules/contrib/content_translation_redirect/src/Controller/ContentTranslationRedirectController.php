<?php

namespace Drupal\content_translation_redirect\Controller;

use Drupal\content_translation_redirect\ContentTranslationRedirectInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Controller routines for Content Translation Redirect routes.
 */
class ContentTranslationRedirectController extends ControllerBase {

  /**
   * Route title callback.
   *
   * @param \Drupal\content_translation_redirect\ContentTranslationRedirectInterface $content_translation_redirect
   *   The redirect entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title for the Content Translation Redirect edit form.
   */
  public function editTitle(ContentTranslationRedirectInterface $content_translation_redirect): TranslatableMarkup {
    return $this->t('Edit content translation redirect for @label', [
      '@label' => $content_translation_redirect->label(),
    ]);
  }

}

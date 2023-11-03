<?php

namespace Drupal\search_api_sorts_widget;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides a Block view alter.
 */
class BlockViewAlter implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * #pre_render callback for building a block.
   */
  public static function preRender($build) {
    $build['content'] = \Drupal::formBuilder()
      ->getForm(
        '\Drupal\search_api_sorts_widget\Form\WidgetForm'
      );
    return $build;
  }

}

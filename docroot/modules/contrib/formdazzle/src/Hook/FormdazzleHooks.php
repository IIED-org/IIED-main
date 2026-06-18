<?php

namespace Drupal\formdazzle\Hook;

use Drupal\formdazzle\Dazzler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for formdazzle.
 */
class FormdazzleHooks {

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public static function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    Dazzler::formAlter($form, $form_id);
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_form_element')]
  public static function preprocessFormElement(array &$variables): void {
    // The ['label']['#theme'] can't be altered until this function because it
    // is unconditionally created in template_preprocess_form_element().
    // @see template_preprocess_form_element()
    Dazzler::preprocessFormElement($variables);
  }

}

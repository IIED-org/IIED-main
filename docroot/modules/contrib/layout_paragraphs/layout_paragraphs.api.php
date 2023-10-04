<?php

/**
 * @file
 * API documentation for Layout Paragraphs module.
 *
 * As of version 2.x, all custom hooks have been removed from Layout Paragraphs
 * and replaced by native hook implementations (i.e. hook_form_alter,
 * hook_preprocess_HOOK, etc.). Some of the more common implementations are
 * listed below.
 *
 * For a full list of theme hooks refer to layout_paragraphs_theme() in
 * layout_paragraphs.module.
 * @see layout_paragraphs_theme()
 *
 * Developers can use hook_entity_view_alter() and check the presence of
 * $build['#layout_paragraphs_component'] to modify the build array for
 * Layout Paragraphs components. Additionally, UI elements (controls, insert
 * links, etc.) can be modified in hook_entity_view_alter() with
 * $build['drupalSettings']['lpBuilder']['uiElements'].
 * @see https://www.drupal.org/project/layout_paragraphs/issues/3256930
 *
 * In addition to native hook functions, Layout Paragraphs uses events to
 * allow developers to customize behavior.
 * @see \Drupal\layout_paragraphs\Event\LayoutParagraphsAllowedTypesEvent
 * @see \Drupal\layout_paragraphs\Event\LayoutParagraphsUpdateLayoutEvent
 * @see \Drupal\layout_paragraphs\EventSubscriber\LayoutParagraphsAllowedTypesSubscriber
 * @see \Drupal\layout_paragraphs\EventSubscriber\LayoutParagraphsUpdateLayoutSubscriber
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Alter the Layout Paragraph component form.
 *
 * Implements hook_form_FORM_ID_alter().
 *
 * @param array $form
 *   The Layout Paragraph component form.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The Layout Paragraph Component Form State.
 *
 * @see layout_paragraphs_form_layout_paragraphs_component_form_alter()
 * */
function hook_form_layout_paragraphs_component_form_alter(array &$form, FormStateInterface &$form_state) {
  // Make custom alterations to adjust the Layout Paragraph Component Form..
}

/**
 * Alter the Layout Paragraphs builder.
 *
 * Implements hook_preprocess_HOOK().
 *
 * @param array $variables
 *   The variables being passed to the template.
 *
 * @see \Drupal\layout_paragraphs\Element\LayoutParagraphsBuilder::preRender()
 */
function hook_preprocess_layout_paragraphs_builder(array &$variables) {
  // Make custom alterations to the Layout Paragraphs Builder.
}

/**
 * Alter the Layout Paragraphs component controls ui.
 *
 * Implements hook_preprocess_HOOK().
 *
 * @param array $variables
 *   The variables being passed to the template.
 *
 * @see layout_paragraphs_preprocess_layout_paragraphs_builder_controls()
 */
function hook_preprocess_layout_paragraphs_builder_controls(array &$variables) {
  // Alter the controls ui (move up / move down / edit / delete / etc.).
}

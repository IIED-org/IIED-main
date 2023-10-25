<?php

/**
 * @file
 * Theme and preprocess functions for forms.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function radix_theme_suggestions_form_alter(array &$suggestions, array &$variables) {
  $element = $variables['element'];

  // Add a suggestion based on the form id name.
  if (isset($element['#form_id'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . $element['#form_id'];
  }

  // Check to see if the form is layout builder form
  if (isset($element['#layout_builder_element_keys'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . 'layout__builder';
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function radix_theme_suggestions_form_element_alter(array &$suggestions, array &$variables) {
  $element = $variables['element'];

  // Add a suggestion based on the element type.
  if (isset($element['#type'])) {
    $suggestions[] = $variables['theme_hook_original'] . '__' . $element['#type'];
  }
}

/**
 * Implements hook_preprocess_input().
 */
function radix_preprocess_input(&$variables) {
  $element = $variables['element'];

  // Add the element type to the theme.
  $variables['type'] = $element['#type'];

  // Ensure there is no collision with Bootstrap 5 default class names
  // by replacing ".form-text" with ".form-textfield"
  $attributes = &$variables['attributes'];

  if ($element['#type'] === 'textfield' && !empty($attributes['class'])) {
    $classIndex = array_search('form-text', $attributes['class']);
    $attributes['class'][$classIndex] = 'form-textfield';
  }
}

/**
 * Implements hook_preprocess_form_element().
 */
function radix_preprocess_form_element(&$variables) {
  $element = $variables['element'];

  // Add required class for checkbox and radio labels.
  if (in_array($element['#type'], ['checkbox', 'radio'])) {
    $variables['label']['#attributes']['class'][] = 'form-check-label';
  } else {
    $variables['label']['#attributes']['class'][] = 'form-label';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function radix_form_search_block_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Update the placeholder and size of field.
  $form['keys']['#title'] = '';
  $form['keys']['#size'] = 20;
  $form['keys']['#placeholder'] = t('Search');
}

function radix_form_alter(&$form, FormStateInterface $form_state) {
  if (isset($form['actions']['submit']) && (count($form['actions'])) <= 2) {
    $form['actions']['submit']['#attributes']['class'][] = 'btn btn-primary';
  }

  if (isset($form['#attributes']['class'])) {
    $form['#attributes']['class'][] = 'needs-validation';
  }

  $form['#validate'][] = 'validateForm';
}

function validateForm(array &$form, FormStateInterface $form_state) {
  if (!empty($form_state->getErrors())) { 
    $form['#attributes']['class'][] = 'was-validated'; 
  } 
}

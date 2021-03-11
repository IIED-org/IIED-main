<?php

/**
 * @file
 * Hooks provided by Masonry.
 *
 * Sponsored by: www.freelance-drupal.com
 */

/**
 * Alter Masonry's default options.
 *
 * @param $options
 *   An associative array of option names and their default values.
 */
function hook_masonry_default_options_alter(&$options) {
  // Add default value for easing option
  $options['masonry_animation_easing'] = 'swing';
}

/**
 * Alter the Masonry options form.
 * This allows you to define UI configuration for a custom configuration.
 * @see hook_masonry_default_options_alter().
 *
 * @param $form
 *   A form array.
 * @param $default_values
 *   An array of default form values.
 */
function hook_masonry_options_form_alter(&$form, $default_values) {
  // Add form item for easing option
  $form['layoutAnimationEasing'] = [
    '#type' => 'select',
    '#title' => t('Animation easing'),
    '#description' => t("The easing function to use for animations."),
    '#options' => [
      'linear' => t('Linear'),
      'swing' => t('Swing'),
    ],
    '#default_value' => $default_values['masonry_animation_easing'],
    '#states' => [
      'visible' => [
        'input.form-checkbox[name*="isLayoutResizable"]' => ['checked' => TRUE],
        'input.form-checkbox[name*="isLayoutAnimated"]' => ['checked' => TRUE],
      ],
    ],
  ];
}

/**
 * Alter the Masonry script.
 *
 * @param $masonry
 *   An array of Masonry options to send to the script file.
 * @param $context
 *   An associative array of additional variables.
 *   Contains:
 *   - container: The CSS selector of the container element to apply Masonry to.
 *   - options: An associative array of Masonry options. See masonry_apply().
 */
function hook_masonry_script_alter(&$masonry, $context) {
  $container = $context['container'];
  $options = $context['options'];

  // Send easing option to the script file
  // Note: this new option has to be introduce via hook_masonry_options_form_alter()
  // otherwise use extra_options.
  $masonry['masonry'][$container]['animation_easing'] = $options['layoutAnimationEasing'];

  // Set the option "horizontalOrder" to true.
  // Note that this option is not included into the predefined options
  // @see MasonryService::getMasonryDefaultOptions
  $masonry['masonry'][$container]['extra_options']['horizontalOrder'] = TRUE;
}


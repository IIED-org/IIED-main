<?php

use \Drupal\layout_builder_styles\LayoutBuilderStyleInterface;

/**
 * @file
 * Post-update functions for Layout Builder Styles module.
 */

/**
 * Add newly-available layout restriction value to existing style entities.
 */
function layout_builder_styles_post_update_update_add_layout_restrictions() {
  $styles = \Drupal::entityTypeManager()
    ->getStorage('layout_builder_style')
    ->loadByProperties();
  foreach ($styles as $style) {
    // Re-save existing styles with empty layout restrictions.
    if ($style->getType() === LayoutBuilderStyleInterface::TYPE_SECTION) {
      $style->set('layout_restrictions', []);
      $style->save();
    }
  }
}

/**
 * Add new 'administer layout builder styles' perm to roles.
 */
function layout_builder_styles_post_update_add_new_perms() {
  // Grant our new permissions to any role with the
  // 'administer site configuration' permission, which is what was
  // previously used to control access to this module.
  $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
  foreach ($roles as $role) {
    /** @var \Drupal\user\RoleInterface $role */
    if ($role->hasPermission('administer site configuration')) {
      $role->grantPermission('manage layout builder styles');
      $role->grantPermission('administer layout builder styles configuration');
      $role->save();
    }
  }
}

/**
 * Add defaults for config if not already set.
 */
function layout_builder_styles_post_update_fix_missing_config() {
  $config = \Drupal::configFactory()->getEditable('layout_builder_styles.settings');
  $update = FALSE;
  if (!$config->get('multiselect')) {
    $config->set('multiselect', 'single');
    $update = TRUE;
  }
  if (!$config->get('form_type')) {
    $config->set('form_type', 'checkboxes');
    $update = TRUE;
  }
  if ($update) {
    $config->save();
  }
}

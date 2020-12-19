<?php

/**
 * @file
 * Responsive menu module APIs.
 */

/**
 * Alter the menu names used by the off-canvas responsive menu.
 *
 * @param string $menus
 *   Contains the machine names of all menus, separated by commas, to be
 *   concatenated into a single menu structure for the off-canvas menu.
 */
function hook_responsive_menu_off_canvas_menu_names_alter(&$menus) {
  // Display a different menu on the front page.
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    $menus = 'frontpage-menu';
  }
}

/**
 * Alter the menu name used by the horizontal responsive menu.
 *
 * @param string $menu_name
 *   The machine name of the menu configured for the horizontal menu.
 */
function hook_responsive_menu_horizontal_menu_name_alter(&$menu_name) {
  // Display a different horizontal menu for node/1.
  $current_path = \Drupal::service('path.current')->getPath();
  if (\Drupal::service('path.matcher')->matchPath($current_path, '/node/1')) {
    $menu_name = 'node-1-menu';
  }
}

/**
 * Alter the off-canvas menu tree.
 *
 * @param array $rendered_tree
 *   The built menu tree to be altered. This is provided as a render array.
 */
function hook_responsive_menu_off_canvas_tree_alter(array &$rendered_tree) {
  // Modify the off-canvas mobile menu tree and change the title of the
  // first item.
  $first = key($rendered_tree['#items']);
  $rendered_tree['#items'][$first]['title'] = 'first';
}

/**
 * Alter the horizontal menu tree.
 *
 * @param array $rendered_tree
 *   The built menu tree to be altered. This is provided as a render array.
 */
function hook_responsive_menu_horizontal_tree_alter(array &$rendered_tree) {
  // Modify the horizontal menu tree and change the title of the first item.
  $first = key($rendered_tree['#items']);
  $rendered_tree['#items'][$first]['title'] = 'first';
}

/**
 * Alter the manipulators before transforming the off canvas menu.
 *
 * @param array $manipulators
 *   The manipulators called when transforming the menu tree.
 */
function hook_responsive_menu_off_canvas_manipulators_alter(array &$manipulators) {
  // Add an another callable manipulator.
  $manipulators[] = ['callable' => 'my_module.custom_tree_manipulators:filterMenus'];
}

/**
 * Alter the manipulators before transforming the horizontal menu.
 *
 * @param array $manipulators
 *   The manipulators called when transforming the menu tree.
 */
function hook_responsive_menu_horizontal_manipulators_alter(array &$manipulators) {
  // Add an another callable manipulator.
  $manipulators[] = ['callable' => 'my_module.custom_tree_manipulators:filterMenus'];
}

/**
 * Alter the variable which decides whether the off-canvas menu should be shown.
 *
 * This hook is useful for developers who want some logic to determine whether
 * the off-canvas menu is added to the page. For example a site may use a
 * different menu for a member's area and the developer wants to disable the
 * off-canvas mmenu for that section.
 *
 * In this example the off-canvas menu is disabled when the Bartik
 * theme is active.
 *
 * @param bool $output
 *   A boolean that is TRUE by default. If FALSE the off-canvas menu will not
 *   be added to the DOM and the JavaScript libraries will not be loaded.
 */
function hook_responsive_menu_off_canvas_output_alter(bool &$output) {
  if (\Drupal::service('theme.manager')->getActiveTheme()->getName() === 'bartik') {
    $output = FALSE;
  }
}

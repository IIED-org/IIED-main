<?php

namespace Drupal\responsive_menu;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides the HorizontalMenu block.
 *
 * @Block(
 *   id = "responsive_menu_horizontal_menu",
 *   admin_label = @Translation("Horizontal menu")
 * )
 */
class OffCanvas implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * Pre render callback to assemble the menu as markup.
   *
   * @param array $build
   *   The render array to modify.
   *
   * @return array
   *   The built render array.
   */
  public static function preRender(array $build) {
    $off_canvas_menus = \Drupal::config('responsive_menu.settings')
      ->get('off_canvas_menus');

    // Other modules can modify the menu names so we need to take this into
    // account when building the menu.
    \Drupal::ModuleHandler()
      ->alter('responsive_menu_off_canvas_menu_names', $off_canvas_menus);

    $menus = explode(',', $off_canvas_menus);

    $combined_tree = [];
    $menu_tree = \Drupal::menuTree();
    $manipulators = [
      // Show links to nodes that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
      // Only show links that are accessible for the current user.
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      // Use the default sorting of menu links.
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];

    // Allow other modules to alter manipulators before transforming menu tree.
    \Drupal::ModuleHandler()
      ->alter('responsive_menu_off_canvas_manipulators', $manipulators);

    // Iterate over the menus and merge them together.
    foreach ($menus as $menu_name) {
      $menu_name = trim($menu_name);
      $parameters = $menu_tree->getCurrentRouteMenuTreeParameters($menu_name);
      // Force the entire tree to be build by setting expandParents to an
      // empty array.
      $parameters->expandedParents = [];
      $tree_items = $menu_tree->load($menu_name, $parameters);
      $tree_manipulated = $menu_tree->transform($tree_items, $manipulators);
      $combined_tree = array_merge($combined_tree, $tree_manipulated);
      $build['#cache']['contexts'][] = 'route.menu_active_trails:' . $menu_name;
      $build['#cache']['tags'][] = 'config:system.menu.' . $menu_name;
      $build['#cache']['tags'][] = 'offcanvas_render';
    }

    $menu = $menu_tree->build($combined_tree);
    // Manipulate the #theme element to provide a known working template, rather
    // than leave it up to the theme to provide one which may break the
    // off-canvas menu.
    $menu['#theme'] = 'responsive_menu_off_canvas';

    // Allow other modules to manipulate the built tree data.
    \Drupal::ModuleHandler()->alter('responsive_menu_off_canvas_tree', $menu);

    $build['#markup'] = \Drupal::service("renderer")->renderRoot($menu);

    return $build;
  }

}

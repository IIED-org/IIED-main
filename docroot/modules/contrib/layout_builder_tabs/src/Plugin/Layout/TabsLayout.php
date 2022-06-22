<?php

namespace Drupal\layout_builder_tabs\Plugin\Layout;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Tabs layout.
 */
class TabsLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = parent::build($regions);
    // If using Olivero, manually attach its tabs library.
    $theme_name = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    if ($theme_name == 'olivero') {
      $build['#attached']['library'][] = 'olivero/tabs';
    }
    return $build;
  }

}

<?php

namespace Drupal\views_templates\Plugin;

/**
 * Creates common interface for Builders that use View Template.
 *
 * This allows Views to be exported to CMI and then manually changed to Views
 * Templates by changing the.
 */
interface ViewsDuplicateBuilderPluginInterface extends ViewsBuilderPluginInterface {

  /**
   * Return the View Template id to be used by this Plugin.
   *
   * @return string
   *   Returns View Template id.
   */
  public function getViewTemplateId();

}

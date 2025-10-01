<?php

namespace Drupal\views_templates;

use Drupal\views_templates\Plugin\ViewsDuplicateBuilderPluginInterface;

/**
 * Provide interface for loading Views Templates for a builder.
 */
interface ViewsTemplateLoaderInterface {

  /**
   * Load template array values from file system for builder plugin.
   *
   * @param \Drupal\views_templates\Plugin\ViewsDuplicateBuilderPluginInterface $builder
   *   The Views Duplicate Builder Interface.
   *
   * @throws \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException
   *   If template does not exist.
   *
   * @return array
   *   Returns array values from file system.
   */
  public function load(ViewsDuplicateBuilderPluginInterface $builder);

}

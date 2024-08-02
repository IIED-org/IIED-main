<?php

namespace Drupal\layout_paragraphs_complex_permissions_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class LayoutParagraphsComplexPermissionsTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides the layout_paragraphs.builder_access service.
    if ($container->hasDefinition('layout_paragraphs.builder_access')) {
      $definition = $container->getDefinition('layout_paragraphs.builder_access');
      $definition->setClass('Drupal\layout_paragraphs_complex_permissions_test\Access\ReorderAccessByContentType');
    }
  }

}

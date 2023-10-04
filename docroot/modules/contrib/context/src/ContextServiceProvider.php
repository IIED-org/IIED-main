<?php

namespace Drupal\context;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Menu\MenuActiveTrail;

/**
 * Alter the service container to use a custom class.
 */
class ContextServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Override the menu active trail with a new class.
    $definition = $container->getDefinition('menu.active_trail');
    if (!is_subclass_of($definition, 'Drupal\Core\Menu\MenuActiveTrail') && $definition instanceof MenuActiveTrail) {
      $definition->setClass('Drupal\context\ContextMenuActiveTrail');
      $definition->addArgument($container->getDefinition('context.manager'));
    }
  }

}

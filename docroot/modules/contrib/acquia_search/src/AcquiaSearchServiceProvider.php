<?php

namespace Drupal\acquia_search;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

/**
 * Service provider to provide backward compatibility with service arguments.
 */
class AcquiaSearchServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // @todo Delete this when dropping Drupal 8 support.
    if (version_compare(\Drupal::VERSION, '9.0', '<')) {
      // @see https://www.drupal.org/node/3080612
      $container->getDefinition('acquia_search.possible_cores.acquia_hosting')
        ->setArgument(2, '@site.path');
      $container->getDefinition('acquia_search.possible_cores.default_core')
        ->setArgument(2, '@site.path');
    }
  }

}

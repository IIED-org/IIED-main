<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\ckeditor5_premium_features\ComposerInstaller\Installer;

/**
 * Defines a service provider for the CKEditor 5 Premium Features module.
 */
class Ckeditor5PremiumFeaturesServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $modules = $container->getParameter('container.modules');
    assert(is_array($modules));

    // Only register the installer service if the package_manager module is installed.
    if (array_key_exists('package_manager', $modules)) {
      $container->register('ckeditor5_premium_features.installer', Installer::class)
        ->setAutowired(TRUE);
    }
  }

}

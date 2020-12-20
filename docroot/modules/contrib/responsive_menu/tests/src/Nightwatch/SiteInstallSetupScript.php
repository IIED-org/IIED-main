<?php

namespace Drupal\TestSite;

/**
 * Setup file used by responsive_menu module Nightwatch tests.
 */
class SiteInstallSetupScript implements TestSetupInterface {

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install(['responsive_menu_test']);
  }

}

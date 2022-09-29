<?php

namespace Drupal\Tests\charts\Traits;

trait ConfigUpdateTrait {

  /**
   * Updates the foo configuration.
   *
   * @param string $value
   *   The value to set.
   *
   * @return void
   */
  protected function updateFooConfiguration(string $value, $library = 'charts_test_library'): void {
    $config = $this->config('charts.settings');
    $settings = $config->get('charts_default_settings');
    $settings['library'] = $library;
    $settings['library_config'] = ['foo' => $value];
    $config->set('charts_default_settings', $settings);
    $config->save();
  }

}

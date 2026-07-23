<?php

namespace Drupal\Tests\purge\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Overrides ::getConfigFactoryStub().
 *
 * @see \Drupal\Tests\UnitTestCase
 */
trait FixGetConfigFactoryStubTrait {

  /**
   * Overrides ::getConfigFactoryStub().
   *
   * @see \Drupal\Tests\UnitTestCase::getConfigFactoryStub
   *
   * @todo Fix lines 55-63 which have been added in order to make mutable stub calls
   *   work in unit tests, e.g.: ->getEditable()->set()->save().
   */
  public function getConfigFactoryStub(array $configs = []) {
    $config_get_map = [];
    $config_editable_map = [];
    // Construct the desired configuration object stubs, each with its own
    // desired return map.
    foreach ($configs as $config_name => $config_values) {
      $map = [];
      foreach ($config_values as $key => $value) {
        $map[] = [$key, $value];
      }
      // Also allow to pass in no argument.
      $map[] = ['', $config_values];

      $immutable_config_object = $this->createStub(ImmutableConfig::class);
      $immutable_config_object->method('get')
        ->willReturnMap($map);
      $config_get_map[] = [$config_name, $immutable_config_object];

      $mutable_config_object = $this->createStub(Config::class);
      $mutable_config_object->method('get')
        ->willReturnMap($map);
      $mutable_config_object->method('set')
        ->willReturn($mutable_config_object);
      $mutable_config_object->method('save')
        ->willReturn($mutable_config_object);
      $config_editable_map[] = [$config_name, $mutable_config_object];
    }
    // Construct a config factory with the array of configuration object stubs
    // as its return map.
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $config_factory->method('get')
      ->willReturnMap($config_get_map);
    $config_factory->method('getEditable')
      ->willReturnMap($config_editable_map);
    return $config_factory;
  }

}

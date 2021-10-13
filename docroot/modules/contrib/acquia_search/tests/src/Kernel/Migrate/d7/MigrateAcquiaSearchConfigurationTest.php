<?php

namespace Drupal\Tests\acquia_search\Kernel\Migrate\d7;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates various configuration objects owned by the acquia search module.
 *
 * @group migrate_drupal_7
 */
class MigrateAcquiaSearchConfigurationTest extends MigrateDrupal7TestBase {
  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'acquia_search',
    'search_api_solr',
    'views',
    'search_api',
  ];

  protected $expectedConfig = [
    'acquia_search.settings' => [
      'api_host' => 'https://api.sr-prod02.acquia.com',
      'read_only' => TRUE,
    ],
  ];
  protected $expectedIndexBundles = [
    'node' => ['page', 'article'],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
      DRUPAL_ROOT,
      drupal_get_path('module', 'acquia_search'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]));

    $migrations = [
      'd7_acquia_search_settings',
      'd7_acquia_search_index',
    ];
    $this->executeMigrations($migrations);
  }

  /**
   * Tests that all expected configuration gets migrated.
   */
  public function testConfigurationMigration() {
    foreach ($this->expectedConfig as $config_id => $values) {
      $actual = \Drupal::config($config_id)->get();
      $this->assertSame($values, $actual);
    }
  }

  /**
   * Tests that all search index entity bundles gets migrated.
   */
  public function testSearchIndex() {
    foreach ($this->expectedIndexBundles as $entityType => $bundle) {
      $path = 'datasource_settings.entity:' . $entityType . '.bundles.selected';
      $actual = \Drupal::config('search_api.index.acquia_search_index')->get($path);
      $this->assertEquals(sort($bundle), sort($actual));
    }
  }

}

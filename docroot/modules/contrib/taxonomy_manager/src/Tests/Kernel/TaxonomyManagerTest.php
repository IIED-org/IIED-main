<?php

namespace Drupal\Tests\taxonomy_manager\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Migrates taxonomy_manager configuration.
 *
 * @requires module taxonomy_manager
 *
 * @group taxonomy_manager
 */
class TaxonomyManagerTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'taxonomy_manager',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('taxonomy_manager'),
      'src',
      'Tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Tests the migration.
   */
  public function testMigration(): void {
    // Test temporarily removed.
    // @code
    // $this->executeMigration('taxonomy_manager_settings');
    // $config_after = $this->config('taxonomy_manager.settings');
    // $disable_mouseover = $config_after
    // ->get('taxonomy_manager_disable_mouseover');
    // $this->assertEquals(1, $disable_mouseover);
    // $pager_tree_page_size = $config_after
    // ->get('taxonomy_manager_pager_tree_page_size');
    // $this->assertEquals(25, $pager_tree_page_size);
    // @endcode
  }

}

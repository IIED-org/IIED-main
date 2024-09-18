<?php

namespace Drupal\Tests\readonlymode\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests read only mode migration.
 *
 * @group readonlymode
 */
class ReadOnlyModeMigrateTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'readonlymode',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('readonlymode'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Asserts that read only mode configuration is migrated.
   */
  public function testReadOnlyModemMigration() {
    $expected_config = [
      'enabled' => 1,
      'url' => '',
      'messages' => [
        'default' => 'drupal7ama.test is currently in maintenance. During this maintenance it is not possible to change site content (like comments, pages and users).',
        'not_saved' => 'Data not saved. drupal7ama.test is currently in maintenance. During maintenance it is not possible to change content. Please make a note of your changes and try again later.',
      ],
      'forms' => [
        'additional' => [
          'edit' => 'install_select_language_form',
          'view' => 'install_configure_form',
        ],
      ],
    ];
    $this->executeMigration('read_only_mode_settings');
    $config_after = $this->config('readonlymode.settings')->getRawData();
    $this->assertSame($expected_config, $config_after);
  }

}

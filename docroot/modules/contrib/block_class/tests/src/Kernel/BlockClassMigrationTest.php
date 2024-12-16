<?php

namespace Drupal\Tests\block_class\Kernel;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests Drupal 7 block class → Drupal 10 block class migrations.
 *
 * @group block_class
 */
class BlockClassMigrationTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   *
   * Visibility must be public for testing with Drupal core 8.9.
   */
  protected static $modules = [
    'block',
    'block_class',
    'block_content',
    'filter',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Skip this Kernel test for D11 (next major), as a temporary solution.
    if (version_compare(\Drupal::VERSION, '11', '<')) {
      $this->installEntitySchema('block_content');
      $this->installConfig(['block_content']);

      // Install the themes used for this test.
      $this->container->get('theme_installer')->install(['bartik', 'seven']);

      $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
        \Drupal::service('extension.list.module')->getPath('migrate_drupal'),
        'tests',
        'fixtures',
        'drupal7.php',
      ]));
    }
    else {
      // The Kernel tests for module's Migrate upgrade from D7 currently require
      // the Bartik and Seven contrib themes which are not yet compatible with
      // Drupal 11, at this time (#3428214, #3434485). Therefore, as a temporary
      // work-around these tests are disabled/skipped for D11.
      $this->markTestSkipped('All tests in this file should be inactive for Drupal 11, until required contrib themes Bartik and Seven become compatible.');
    }
  }

  /**
   * Tests D7 block migration with and without D7 block_class data.
   *
   * @param bool $with_block_class
   *   Whether block_class should be enabled on the source site (and should have
   *   block_class data).
   * @param array $expected_block_class_settings
   *   Expected block_class third_party_settings keyed by block config ID. If no
   *   block_class settings is expected, the value should be NULL. Otherwise it
   *   should be an array of block_class values keyed by the setting key.
   *
   * @dataProvider providerTest
   */
  public function testBlockClassMigration(bool $with_block_class, array $expected_block_class_settings) {
    if ($with_block_class) {
      $this->loadFixture(implode(DIRECTORY_SEPARATOR, [
        \Drupal::service('extension.list.module')->getPath('block_class'),
        'tests',
        'fixtures',
        'd7_block_class.php',
      ]));
    }

    $this->startCollectingMessages();
    $this->executeMigrations([
      'd7_user_role',
      'd7_user',
      'block_content_type',
      'block_content_body_field',
      'd7_filter_format',
      'd7_custom_block',
    ]);

    $this->startCollectingMessages();
    $this->executeMigrations(['d7_block']);
    $this->assertEmpty($this->migrateMessages);

    // Check the migrated block configurations.
    $entity_type_manager = $this->container->get('entity_type.manager');
    assert($entity_type_manager instanceof EntityTypeManagerInterface);
    $block_storage = $entity_type_manager->getStorage('block');
    assert($block_storage instanceof EntityStorageInterface);

    // Seven user login block shouldn't have block CSS classes.
    $expected_seven_user_login = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => ['user'],
        'theme' => ['seven'],
      ],
      'id' => 'seven_user_login',
      'theme' => 'seven',
      'region' => 'content',
      'weight' => 10,
      'provider' => NULL,
      'plugin' => 'user_login_block',
      'settings' => [
        'id' => 'user_login_block',
        'label' => 'User login title',
        'provider' => 'user',
        'label_display' => 'visible',
      ],
      'visibility' => [],
    ];
    if ($expected_block_class_settings['seven_user_login'] ?? NULL) {
      $expected_seven_user_login['third_party_settings']['block_class'] = $expected_block_class_settings['seven_user_login'];
      $expected_seven_user_login['dependencies']['module'][] = 'block_class';
      sort($expected_seven_user_login['dependencies']['module']);
    }
    $this->assertEquals(
      $expected_seven_user_login,
      array_diff_key($block_storage->load('seven_user_login')->toArray(), ['uuid' => 1])
    );

    // Bartik system main block should have block_class settings if the source
    // has block_class enabled.
    $expected_bartik_main = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'module' => ['system'],
        'theme' => ['bartik'],
      ],
      'id' => 'bartik_system_main',
      'theme' => 'bartik',
      'region' => 'content',
      'weight' => 0,
      'provider' => NULL,
      'plugin' => 'system_main_block',
      'settings' => [
        'id' => 'system_main_block',
        'label' => '',
        'provider' => 'system',
        'label_display' => '0',
      ],
      'visibility' => [],
    ];
    if ($expected_block_class_settings['bartik_system_main'] ?? NULL) {
      $expected_bartik_main['third_party_settings']['block_class'] = $expected_block_class_settings['bartik_system_main'];
      $expected_bartik_main['dependencies']['module'][] = 'block_class';
      sort($expected_bartik_main['dependencies']['module']);
    }
    $this->assertEquals(
      $expected_bartik_main,
      array_diff_key($block_storage->load('bartik_system_main')->toArray(), ['uuid' => 1])
    );
  }

  /**
   * Data provider for ::testBlockClassMigration.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerTest(): array {
    return [
      'Without D7 Block Class' => [
        'D7 Block Class installed' => FALSE,
        'Expected block_class third_party_settings per block config ID' => [],
      ],
      'D7 Block Class data' => [
        'D7 Block Class installed' => TRUE,
        'Expected block_class third_party_settings per block config ID' => [
          'bartik_system_main' => [
            'classes' => 'block-class__system system-main-block-class',
          ],
        ],
      ],
    ];
  }

}

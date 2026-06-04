<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\KernelTests\KernelTestBase;

require_once __DIR__ . '/../../../name.install';

/**
 * Direct coverage for procedural update hooks in name.install.
 *
 * @group name
 */
class HookUpdateInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * @covers \name_update_last_removed
   */
  public function testUpdateLastRemovedReturnsExpectedSchemaVersion(): void {
    $this->assertSame(8001, name_update_last_removed());
  }

  /**
   * @covers \name_update_8202
   */
  public function testUpdate8202InstallsNameListFormatEntityType(): void {
    name_update_8202();

    $storage = \Drupal::entityTypeManager()->getStorage('name_list_format');
    $entity = $storage->create([
      'id' => 'hook_update_8202',
      'label' => 'Hook Update 8202',
      'locked' => FALSE,
      'status' => TRUE,
    ]);
    $entity->save();

    $this->assertNotNull($storage->load('hook_update_8202'));
  }

  /**
   * @covers \name_update_8203
   */
  public function testUpdate8203MigratesExistingExamplesConfig(): void {
    $config_factory = $this->container->get('config.factory');
    $config_factory->getEditable('name.examples.default')
      ->set('examples', [[
        'title' => 'Dr',
        'given' => 'Jane',
        'family' => 'Smith',
      ],
      ])
      ->save(TRUE);

    name_update_8203();

    $components = $config_factory->get('name.generate.components');
    $preferred = $config_factory->get('name.generate.preferred');
    $examples = $config_factory->get('name.generate.examples')->get('examples');

    $this->assertNotEmpty($components->get('components'));
    $this->assertNotEmpty($components->get('gender'));
    $this->assertNotEmpty($preferred->get('preferred'));
    $this->assertSame('Dr', $examples[0]['title']);
    $this->assertSame('Jane', $examples[0]['given']);
    $this->assertSame('Joe', $examples[0]['preferred']);
    $this->assertSame('Lorem ipsum dolor', $examples[0]['alternative']);
    $this->assertTrue($config_factory->get('name.examples.default')->isNew());
  }

  /**
   * @covers \name_update_8203
   */
  public function testUpdate8203LoadsDefaultExamplesWhenLegacyConfigMissing(): void {
    $config_factory = $this->container->get('config.factory');

    $this->assertTrue($config_factory->get('name.examples.default')->isNew());
    $this->assertTrue($config_factory->get('name.generate.examples')->isNew());

    name_update_8203();

    $examples = $config_factory->get('name.generate.examples')->get('examples');
    $this->assertCount(3, $examples);
    $this->assertSame('Mr', $examples[0]['title']);
    $this->assertSame('John', $examples[0]['given']);
    $this->assertSame('Doe', $examples[0]['family']);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\KernelTests\KernelTestBase;

require_once __DIR__ . '/../../../name.install';

/**
 * Tests the 8204 update hook.
 *
 * @covers \name_update_8204
 *
 * @group name
 */
class HookUpdate8204Test extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
  ];

  /**
   * Verifies the update creates the generator configs from defaults.
   */
  public function testNameUpdate8204(): void {
    $config_factory = $this->container->get('config.factory');

    $this->assertTrue($config_factory->get('name.generate.components')->isNew());
    $this->assertTrue($config_factory->get('name.generate.preferred')->isNew());
    $this->assertTrue($config_factory->get('name.generate.examples')->isNew());

    name_update_8204();

    $components_config = $config_factory->get('name.generate.components');
    $this->assertFalse($components_config->isNew());
    $this->assertNotEmpty($components_config->get('components'));
    $this->assertNotEmpty($components_config->get('gender'));

    $preferred_config = $config_factory->get('name.generate.preferred');
    $this->assertFalse($preferred_config->isNew());
    $this->assertNotEmpty($preferred_config->get('preferred'));

    $examples_config = $config_factory->get('name.generate.examples');
    $this->assertFalse($examples_config->isNew());
    $examples = $examples_config->get('examples');
    $this->assertNotEmpty($examples);
    $this->assertCount(3, $examples);
    $this->assertEquals('Mr', $examples[0]['title']);
    $this->assertEquals('John', $examples[0]['given']);
    $this->assertEquals('Doe', $examples[0]['family']);
    $this->assertEquals('Joe', $examples[0]['preferred']);
    $this->assertEquals('Lorem ipsum dolor', $examples[0]['alternative']);
  }

  /**
   * Verifies legacy example config is migrated and removed.
   */
  public function testNameUpdate8204WithOldConfig(): void {
    $config_factory = $this->container->get('config.factory');

    $old_config = $config_factory->getEditable('name.examples.default');
    $old_config->set('examples', [
      [
        'title' => 'Dr',
        'given' => 'Jane',
        'middle' => 'Marie',
        'family' => 'Smith',
        'generational' => 'Sr',
        'credentials' => 'PhD',
      ],
    ]);
    $old_config->save(TRUE);

    $this->assertFalse($config_factory->get('name.examples.default')->isNew());

    name_update_8204();

    $examples_config = $config_factory->get('name.generate.examples');
    $this->assertFalse($examples_config->isNew());
    $examples = $examples_config->get('examples');
    $this->assertNotEmpty($examples);
    $this->assertCount(1, $examples);
    $this->assertEquals('Dr', $examples[0]['title']);
    $this->assertEquals('Jane', $examples[0]['given']);
    $this->assertEquals('Smith', $examples[0]['family']);
    $this->assertEquals('Joe', $examples[0]['preferred']);
    $this->assertEquals('Lorem ipsum dolor', $examples[0]['alternative']);
    $this->assertTrue($config_factory->get('name.examples.default')->isNew());
  }

  /**
   * Verifies the update does not overwrite new examples config.
   */
  public function testNameUpdate8204WithExistingNewConfig(): void {
    $config_factory = $this->container->get('config.factory');

    $existing_config = $config_factory->getEditable('name.generate.examples');
    $existing_config->set('examples', [
      [
        'title' => 'Existing',
        'given' => 'Data',
        'family' => 'Test',
      ],
    ]);
    $existing_config->save(TRUE);

    $this->assertFalse($config_factory->get('name.generate.examples')->isNew());

    name_update_8204();

    $examples_config = $config_factory->get('name.generate.examples');
    $examples = $examples_config->get('examples');
    $this->assertCount(1, $examples);
    $this->assertEquals('Existing', $examples[0]['title']);
    $this->assertEquals('Data', $examples[0]['given']);
    $this->assertEquals('Test', $examples[0]['family']);
    $this->assertArrayNotHasKey('preferred', $examples[0]);
    $this->assertArrayNotHasKey('alternative', $examples[0]);
  }

  /**
   * Verifies preferred config is only seeded when missing.
   */
  public function testNameUpdate8204PreferredConfigIsNew(): void {
    $config_factory = $this->container->get('config.factory');

    $existing_preferred = $config_factory->getEditable('name.generate.preferred');
    $existing_preferred->set('preferred', ['Existing' => 'Test']);
    $existing_preferred->save(TRUE);

    $this->assertFalse($config_factory->get('name.generate.preferred')->isNew());

    name_update_8204();

    $preferred_config = $config_factory->get('name.generate.preferred');
    $preferred = $preferred_config->get('preferred');
    $this->assertEquals(['Existing' => 'Test'], $preferred);
    $this->assertArrayNotHasKey('Abraham', $preferred);
    $this->assertArrayNotHasKey('Alan', $preferred);
  }

}

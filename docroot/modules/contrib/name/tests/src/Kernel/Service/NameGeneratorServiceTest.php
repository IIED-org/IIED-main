<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Service\GeneratorInterface;
use Drupal\name\Service\GeneratorService;

/**
 * @coversDefaultClass \Drupal\name\Service\GeneratorService
 *
 * @group name
 */
class NameGeneratorServiceTest extends KernelTestBase {

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
    $this->installConfig(['field', 'name']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests that name.generator is registered as GeneratorService.
   */
  public function testGeneratorService(): void {
    $service = $this->container->get('name.generator');
    $this->assertInstanceOf(GeneratorService::class, $service);
    $this->assertInstanceOf(GeneratorInterface::class, $service);
  }

  /**
   * @covers ::loadSampleValues
   */
  public function testLoadSampleValuesWithInstalledConfig(): void {
    /** @var \Drupal\name\Service\GeneratorService $generator */
    $generator = $this->container->get('name.generator');
    $result = $generator->loadSampleValues(2);
    $this->assertCount(2, $result);
    foreach ($result as $row) {
      $this->assertIsArray($row);
      $this->assertArrayHasKey('given', $row);
      $this->assertArrayHasKey('family', $row);
    }
  }

  /**
   * @covers ::generateSampleNames
   * @covers ::initComponents
   * @covers ::buildName
   * @covers ::pickRandom
   */
  public function testGenerateSampleNamesWithInstalledConfig(): void {
    /** @var \Drupal\name\Service\GeneratorService $generator */
    $generator = $this->container->get('name.generator');
    $names = $generator->generateSampleNames(3);
    $this->assertCount(3, $names);
    foreach ($names as $name) {
      foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $key) {
        $this->assertArrayHasKey($key, $name);
      }
      $this->assertNotSame('', $name['given']);
      $this->assertNotSame('', $name['family']);
    }
  }

  /**
   * @covers ::generateSampleNames
   * @covers ::initComponents
   * @covers ::buildName
   * @covers ::pickRandom
   * @covers ::filterByFieldSettings
   */
  public function testGenerateSampleNamesWithFieldFilter(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getName')->willReturn('field_name');
    $field->method('getSettings')->willReturn([
      'components' => [
        'title'        => FALSE,
        'given'        => TRUE,
        'middle'       => FALSE,
        'family'       => TRUE,
        'credentials'  => FALSE,
        'generational' => FALSE,
      ],
    ]);

    /** @var \Drupal\name\Service\GeneratorService $generator */
    $generator = $this->container->get('name.generator');
    $names = $generator->generateSampleNames(3, $field);
    $this->assertCount(3, $names);
    foreach ($names as $name) {
      $this->assertArrayHasKey('given', $name);
      $this->assertArrayHasKey('family', $name);
      $this->assertArrayNotHasKey('title', $name);
      $this->assertArrayNotHasKey('middle', $name);
      $this->assertArrayNotHasKey('credentials', $name);
      $this->assertArrayNotHasKey('generational', $name);
    }
  }

  /**
   * @covers ::loadSampleValues
   * @covers ::filterByFieldSettings
   */
  public function testLoadSampleValuesWithFieldFilter(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->method('getName')->willReturn('field_name');
    $field->method('getSettings')->willReturn([
      'components' => [
        'title'          => FALSE,
        'given'          => TRUE,
        'middle'         => FALSE,
        'family'         => TRUE,
        'credentials'    => FALSE,
        'generational'   => FALSE,
      ],
    ]);

    /** @var \Drupal\name\Service\GeneratorService $generator */
    $generator = $this->container->get('name.generator');
    $result = $generator->loadSampleValues(1, $field);
    $this->assertCount(1, $result);
    $this->assertArrayHasKey('given', $result[0]);
    $this->assertArrayHasKey('family', $result[0]);
    $this->assertArrayNotHasKey('title', $result[0]);
    $this->assertArrayNotHasKey('middle', $result[0]);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Service\NameComponentMetadataService;

/**
 * @coversDefaultClass \Drupal\name\Service\NameComponentMetadataService
 *
 * @group name
 */
class NameComponentMetadataServiceTest extends KernelTestBase {

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
   * @covers ::getTranslations
   */
  public function testServiceRegisteredAndTranslations(): void {
    $service = $this->container->get('name.component_metadata');
    $this->assertInstanceOf(NameComponentMetadataService::class, $service);
    $translations = $service->getTranslations();
    $this->assertSame([
      'title',
      'given',
      'middle',
      'family',
      'generational',
      'credentials',
    ], array_keys($translations));
    $subset = $service->getTranslations(['given' => '', 'middle' => '']);
    $this->assertSame(['given', 'middle'], array_keys($subset));
  }

  /**
   * @covers ::getFormatterOutputTypes
   * @covers ::getFormatterOutputOptions
   */
  public function testFormatterOutputMethods(): void {
    /** @var \Drupal\name\Service\NameComponentMetadataService $service */
    $service = $this->container->get('name.component_metadata');
    $this->assertSame(['default', 'plain', 'raw'], array_keys($service->getFormatterOutputTypes()));
    $this->assertSame(['default', 'plain', 'raw'], array_keys($service->getFormatterOutputOptions()));
  }

}

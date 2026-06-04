<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\NameComponentMetadataService;

/**
 * @coversDefaultClass \Drupal\name\Service\NameComponentMetadataService
 *
 * @group name
 */
class NameComponentMetadataServiceTest extends UnitTestCase {

  /**
   * @covers ::getTranslations
   */
  public function testGetTranslationsReturnsAllComponents(): void {
    $service = new NameComponentMetadataService($this->getStringTranslationStub());
    $translations = $service->getTranslations();
    $this->assertSame([
      'title',
      'given',
      'middle',
      'family',
      'generational',
      'credentials',
    ], array_keys($translations));
  }

  /**
   * @covers ::getTranslations
   */
  public function testGetTranslationsIntersect(): void {
    $service = new NameComponentMetadataService($this->getStringTranslationStub());
    $translations = $service->getTranslations([
      'given' => '',
      'family' => '',
    ]);
    $this->assertSame(['given', 'family'], array_keys($translations));
  }

  /**
   * @covers ::getFormatterOutputTypes
   */
  public function testGetFormatterOutputTypes(): void {
    $service = new NameComponentMetadataService($this->getStringTranslationStub());
    $types = $service->getFormatterOutputTypes();
    $this->assertSame(['default', 'plain', 'raw'], array_keys($types));
  }

  /**
   * @covers ::getFormatterOutputOptions
   */
  public function testGetFormatterOutputOptions(): void {
    $service = new NameComponentMetadataService($this->getStringTranslationStub());
    $options = $service->getFormatterOutputOptions();
    $this->assertSame(['default', 'plain', 'raw'], array_keys($options));
  }

}

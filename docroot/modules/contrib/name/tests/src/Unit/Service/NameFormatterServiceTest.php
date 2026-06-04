<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Entity\NameFormatInterface;
use Drupal\name\Entity\NameListFormatInterface;
use Drupal\name\Service\NameFormatParserInterface;
use Drupal\name\Service\NameFormatterService;

/**
 * @coversDefaultClass \Drupal\name\Service\NameFormatterService
 *
 * @group name
 * @group legacy
 */
class NameFormatterServiceTest extends UnitTestCase {

  /**
   * Expected deprecation message for getLastDelimitorTypes().
   *
   * @see \Drupal\name\Service\NameFormatterService::getLastDelimitorTypes()
   */
  private const DEPRECATION_GET_LAST_DELIMITOR_TYPES = 'getLastDelimitorTypes() is deprecated in name:8.x-1.1 and is removed from name:2.0.0. use getLastDelimiterTypes(). See https://www.drupal.org/project/name/issues/3518599';

  /**
   * Expected deprecation message for getLastDelimitorBehaviors().
   *
   * @see \Drupal\name\Service\NameFormatterService::getLastDelimitorBehaviors()
   */
  private const DEPRECATION_GET_LAST_DELIMITOR_BEHAVIORS = 'getLastDelimitorBehaviors() is deprecated in name:8.x-1.1 and is removed from name:2.0.0. use getLastDelimiterBehaviors(). See https://www.drupal.org/project/name/issues/3518599';

  /**
   * Builds a formatter with the given name and list format storages.
   */
  private function createFormatterWithStorages(
    EntityStorageInterface $nameFormatStorage,
    EntityStorageInterface $listFormatStorage,
  ): NameFormatterService {
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->willReturnMap([
        ['name_format', $nameFormatStorage],
        ['name_list_format', $listFormatStorage],
      ]);

    return new NameFormatterService(
      $entityTypeManager,
      $this->createMock(NameFormatParserInterface::class),
      $this->createMock(LanguageManagerInterface::class),
      $this->getStringTranslationStub(),
      $this->createConfigFactory(),
    );
  }

  /**
   * Builds a formatter with real list format storage mock for list tests.
   */
  private function createFormatterWithListStorage(EntityStorageInterface $listStorage): NameFormatterService {
    return $this->createFormatterWithStorages(
      $this->createMock(EntityStorageInterface::class),
      $listStorage,
    );
  }

  /**
   * Builds a formatter with generic entity storage mocks.
   */
  private function createFormatterService(): NameFormatterService {
    return $this->createFormatterWithListStorage(
      $this->createMock(EntityStorageInterface::class),
    );
  }

  /**
   * Config factory returning name.settings separator values.
   */
  private function createConfigFactory(): ConfigFactoryInterface {
    $factory = $this->createMock(ConfigFactoryInterface::class);
    $immutable = $this->createMock('Drupal\Core\Config\ImmutableConfig');
    $immutable->method('get')
      ->willReturnMap([
        ['sep1', ' '],
        ['sep2', ', '],
        ['sep3', ''],
      ]);
    $factory->method('get')
      ->with('name.settings')
      ->willReturn($immutable);

    return $factory;
  }

  /**
   * Asserts two option arrays match (keys and rendered strings).
   *
   * @param array<string, \Drupal\Core\StringTranslation\TranslatableMarkup> $expected
   *   Expected options from the non-deprecated API.
   * @param array<string, \Drupal\Core\StringTranslation\TranslatableMarkup> $actual
   *   Options from the code under test.
   */
  private function assertTranslatedOptionsEqual(array $expected, array $actual): void {
    $this->assertSame(array_keys($expected), array_keys($actual));
    foreach ($expected as $key => $markup) {
      $this->assertSame((string) $markup, (string) $actual[$key]);
    }
  }

  /**
   * @covers ::getListSettings
   */
  public function testGetListSettingsLoadsDefaultWhenRequestedListFormatMissing(): void {
    $defaultSettings = [
      'delimiter'   => ' | ',
      'and'         => 'text',
      'delimiter_precedes_last' => 'never',
      'el_al_min'   => 3,
      'el_al_first' => 1,
    ];

    $defaultEntity = $this->createMock(NameListFormatInterface::class);
    $defaultEntity->method('listSettings')->willReturn($defaultSettings);

    $loadIds = [];
    $listStorage = $this->createMock(EntityStorageInterface::class);
    $listStorage->method('load')
      ->willReturnCallback(function (string $id) use (&$loadIds, $defaultEntity) {
        $loadIds[] = $id;
        if ($id === 'nonexistent_list_format') {
          return NULL;
        }
        if ($id === 'default') {
          return $defaultEntity;
        }
        return NULL;
      });

    $formatter = $this->createFormatterWithListStorage($listStorage);
    $method = new \ReflectionMethod(NameFormatterService::class, 'getListSettings');
    $method->setAccessible(TRUE);
    $result = $method->invoke($formatter, 'nonexistent_list_format');

    $this->assertSame($defaultSettings, $result);
    $this->assertSame(['nonexistent_list_format', 'default'], $loadIds);
  }

  /**
   * @covers ::getNameFormatString
   */
  public function testGetNameFormatStringLoadsDefaultWhenRequestedFormatMissing(): void {
    $pattern = '%t %g %f';
    $defaultEntity = $this->createMock(NameFormatInterface::class);
    $defaultEntity->method('get')->with('pattern')->willReturn($pattern);

    $loadIds = [];
    $nameStorage = $this->createMock(EntityStorageInterface::class);
    $nameStorage->method('load')
      ->willReturnCallback(function (string $id) use (&$loadIds, $defaultEntity) {
        $loadIds[] = $id;
        if ($id === 'nonexistent_name_format') {
          return NULL;
        }
        if ($id === 'default') {
          return $defaultEntity;
        }
        return NULL;
      });

    $formatter = $this->createFormatterWithStorages(
      $nameStorage,
      $this->createMock(EntityStorageInterface::class),
    );
    $method = new \ReflectionMethod(NameFormatterService::class, 'getNameFormatString');
    $method->setAccessible(TRUE);
    $result = $method->invoke($formatter, 'nonexistent_name_format');

    $this->assertSame($pattern, $result);
    $this->assertSame(['nonexistent_name_format', 'default'], $loadIds);
  }

  /**
   * @covers ::getLastDelimitorTypes
   */
  public function testGetLastDelimitorTypesMatchesGetLastDelimiterTypes(): void {
    $formatter = $this->createFormatterService();

    $this->expectDeprecation(self::DEPRECATION_GET_LAST_DELIMITOR_TYPES);
    $this->expectDeprecation(self::DEPRECATION_GET_LAST_DELIMITOR_TYPES);

    $this->assertTranslatedOptionsEqual(
      $formatter->getLastDelimiterTypes(TRUE),
      $formatter->getLastDelimitorTypes(TRUE),
    );
    $this->assertTranslatedOptionsEqual(
      $formatter->getLastDelimiterTypes(FALSE),
      $formatter->getLastDelimitorTypes(FALSE),
    );
  }

  /**
   * @covers ::getLastDelimitorBehaviors
   */
  public function testGetLastDelimitorBehaviorsMatchesGetLastDelimiterBehaviors(): void {
    $formatter = $this->createFormatterService();

    $this->expectDeprecation(self::DEPRECATION_GET_LAST_DELIMITOR_BEHAVIORS);
    $this->expectDeprecation(self::DEPRECATION_GET_LAST_DELIMITOR_BEHAVIORS);

    $this->assertTranslatedOptionsEqual(
      $formatter->getLastDelimiterBehaviors(TRUE),
      $formatter->getLastDelimitorBehaviors(TRUE),
    );
    $this->assertTranslatedOptionsEqual(
      $formatter->getLastDelimiterBehaviors(FALSE),
      $formatter->getLastDelimitorBehaviors(FALSE),
    );
  }

  /**
   * @covers ::getLastDelimitorTypes
   */
  public function testGetLastDelimitorTypesTriggersDeprecation(): void {
    // cspell:ignore delimitor
    $this->expectDeprecation(self::DEPRECATION_GET_LAST_DELIMITOR_TYPES);

    $formatter = $this->createFormatterService();
    $types = $formatter->getLastDelimitorTypes(TRUE);

    $this->assertArrayHasKey('text', $types);
    $this->assertArrayHasKey('symbol', $types);
    $this->assertArrayHasKey('inherit', $types);
  }

  /**
   * @covers ::getLastDelimitorBehaviors
   */
  public function testGetLastDelimitorBehaviorsTriggersDeprecation(): void {
    // cspell:ignore delimitor
    $this->expectDeprecation(self::DEPRECATION_GET_LAST_DELIMITOR_BEHAVIORS);

    $formatter = $this->createFormatterService();
    $behaviors = $formatter->getLastDelimitorBehaviors(TRUE);

    $this->assertArrayHasKey('never', $behaviors);
    $this->assertArrayHasKey('always', $behaviors);
    $this->assertArrayHasKey('contextual', $behaviors);
  }

}

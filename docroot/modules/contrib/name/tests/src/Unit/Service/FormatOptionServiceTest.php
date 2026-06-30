<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\FormatOptionService;

/**
 * Tests FormatOptionService.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Service\FormatOptionService
 */
class FormatOptionServiceTest extends UnitTestCase {

  /**
   * @covers ::getCustomFormatOptions
   */
  public function testGetCustomFormatOptionsSortsByLabel(): void {
    $entities = [
      'zzz' => $this->formatEntityMock('zzz', 'zebra'),
      'aaa' => $this->formatEntityMock('aaa', 'apple'),
      'aab' => $this->formatEntityMock('aab', 'Banana'),
    ];
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn($entities);

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->expects($this->once())
      ->method('getStorage')
      ->with('name_format')
      ->willReturn($storage);

    $service = new FormatOptionService($etm);
    $options = $service->getCustomFormatOptions();

    $this->assertSame([
      'aaa' => 'apple',
      'aab' => 'Banana',
      'zzz' => 'zebra',
    ], $options);
  }

  /**
   * @covers ::getCustomFormatOptions
   */
  public function testGetCustomFormatOptionsEmpty(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([]);

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->expects($this->once())
      ->method('getStorage')
      ->with('name_format')
      ->willReturn($storage);

    $service = new FormatOptionService($etm);
    $this->assertSame([], $service->getCustomFormatOptions());
  }

  /**
   * @covers ::getCustomListFormatOptions
   */
  public function testGetCustomListFormatOptionsSortsByLabel(): void {
    $entities = [
      'z' => $this->formatEntityMock('z', 'Z'),
      'a' => $this->formatEntityMock('a', 'a'),
    ];
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn($entities);

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->expects($this->once())
      ->method('getStorage')
      ->with('name_list_format')
      ->willReturn($storage);

    $service = new FormatOptionService($etm);
    $options = $service->getCustomListFormatOptions();

    $this->assertSame([
      'a' => 'a',
      'z' => 'Z',
    ], $options);
  }

  /**
   * @covers ::getFormatPatternByMachineName
   */
  public function testGetFormatPatternByMachineNameFound(): void {
    // Avoid mocking EntityInterface::get() (not on interface) and avoid
    // MockBuilder::addMethods() (deprecated in PHPUnit 11, removed in 12).
    $entity = new class() {

      /**
       * Tracks invocations of get() for assertions.
       *
       * @var int
       */
      public int $getCallCount = 0;

      /**
       * Mimics config entity get() for unit testing.
       *
       * @param string $key
       *   The config key.
       *
       * @return string
       *   The pattern when $key is pattern; otherwise an empty string.
       */
      public function get(string $key): string {
        $this->getCallCount++;
        return $key === 'pattern' ? '(((ti)+ig)+if)+is' : '';
      }

    };

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with('full')
      ->willReturn($entity);

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->expects($this->once())
      ->method('getStorage')
      ->with('name_format')
      ->willReturn($storage);

    $service = new FormatOptionService($etm);
    $this->assertSame('(((ti)+ig)+if)+is', $service->getFormatPatternByMachineName('full'));
    $this->assertSame(1, $entity->getCallCount, 'get() must be invoked once for pattern.');
  }

  /**
   * @covers ::normalizeLegacyFormatMachineName
   *
   * @dataProvider providerNormalizeLegacyFormatMachineName
   */
  public function testNormalizeLegacyFormatMachineName(mixed $raw, ?string $expected): void {
    $this->assertSame($expected, FormatOptionService::normalizeLegacyFormatMachineName($raw));
  }

  /**
   * Data provider for ::testNormalizeLegacyFormatMachineName().
   *
   * @return array<string, array{0: mixed, 1: string|null}>
   *   Test cases: raw input and expected normalized id or NULL.
   */
  public static function providerNormalizeLegacyFormatMachineName(): array {
    return [
      'null' => [NULL, NULL],
      'empty string' => ['', NULL],
      'array' => [[], NULL],
      'stdClass object' => [new \stdClass(), NULL],
      'false stringifies empty' => [FALSE, NULL],
      'true stringifies' => [TRUE, '1'],
      'integer' => [42, '42'],
      'float' => [1.5, '1.5'],
      'valid machine name' => ['full', 'full'],
      'stringable' => [
        self::legacyStringableWithValue('custom'),
        'custom',
      ],
      'stringable empty' => [
        self::legacyStringableWithValue(''),
        NULL,
      ],
    ];
  }

  /**
   * @covers ::normalizeLegacyFormatMachineName
   */
  public function testNormalizeLegacyFormatMachineNameRejectsResource(): void {
    $handle = fopen('php://memory', 'rb');
    $this->assertNotFalse($handle);
    $this->assertNull(FormatOptionService::normalizeLegacyFormatMachineName($handle));
    fclose($handle);
  }

  /**
   * @covers ::getFormatPatternByMachineName
   */
  public function testGetFormatPatternByMachineNameNotFound(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with('missing')
      ->willReturn(NULL);

    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $etm->expects($this->once())
      ->method('getStorage')
      ->with('name_format')
      ->willReturn($storage);

    $service = new FormatOptionService($etm);
    $this->assertNull($service->getFormatPatternByMachineName('missing'));
  }

  /**
   * Builds a mock format entity with id() and label().
   *
   * @return \Drupal\Core\Entity\EntityInterface&\PHPUnit\Framework\MockObject\MockObject
   *   Mock entity stub.
   */
  private function formatEntityMock(string $id, string $label): object {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn($id);
    $entity->method('label')->willReturn($label);
    return $entity;
  }

  /**
   * Builds a Stringable object for legacy normalization data cases.
   *
   * @param string $value
   *   The value __toString() returns.
   *
   * @return \Stringable
   *   Anonymous stringable instance.
   */
  private static function legacyStringableWithValue(string $value): \Stringable {
    return new class($value) implements \Stringable {

      /**
       * Constructs a test stringable.
       *
       * @param string $value
       *   String returned from __toString().
       */
      public function __construct(
        private string $value,
      ) {}

      /**
       * {@inheritdoc}
       */
      public function __toString(): string {
        return $this->value;
      }

    };
  }

}

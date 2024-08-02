<?php

namespace Drupal\Tests\hal\Unit;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\Plugin\DataType\FieldItem;
use Drupal\hal\Normalizer\FieldItemNormalizer;
use Drupal\hal\Normalizer\FieldNormalizer;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;

/**
 * Tests the exceptions thrown by FieldNormalizer and FieldItemNormalizer.
 *
 * @group hal
 */
class FieldNormalizerDenormalizeExceptionsTest extends UnitTestCase {

  /**
   * Tests that the FieldNormalizer::denormalize() throws proper exceptions.
   *
   * @covers \Drupal\hal\Normalizer\FieldNormalizer
   *
   * @dataProvider providerNormalizerDenormalizeExceptions
   */
  public function testFieldNormalizerDenormalizeExceptions(bool $with_context) {
    $field_item_normalizer = new FieldNormalizer();
    $data = [];
    $class = [];
    $this->expectException(InvalidArgumentException::class);

    $context = [];
    if ($with_context) {
      $mock = $this->createMock(FieldItemBase::class);
      $mock->expects($this->any())
        ->method('getParent')
        ->willReturn(NULL);
      $context['target_instance'] = $mock;
    }

    $field_item_normalizer->denormalize($data, $class, NULL, $context);
  }

  /**
   * Tests that the FieldItemNormalizer::denormalize() throws proper exceptions.
   *
   * @covers \Drupal\hal\Normalizer\FieldItemNormalizer
   *
   * @dataProvider providerNormalizerDenormalizeExceptions
   */
  public function testFieldItemNormalizerDenormalizeExceptions(bool $with_context) {
    $field_item_normalizer = new FieldItemNormalizer();
    $data = [];
    $class = [];
    $this->expectException(InvalidArgumentException::class);

    $context = [];
    if ($with_context) {
      $mock = $this->createMock(FieldItemBase::class);
      $mock->expects($this->any())
        ->method('getParent')
        ->willReturn(NULL);
      $context['target_instance'] = $mock;
    }

    $field_item_normalizer->denormalize($data, $class, NULL, $context);
  }

  /**
   * Provides data for field normalization tests.
   *
   * @return array
   *   The context of the normalizer.
   */
  public static function providerNormalizerDenormalizeExceptions(): array {
    return [
      [TRUE],
      [FALSE],
    ];
  }

}

<?php

namespace Drupal\Tests\purge\Unit\Counter;

use Drupal\purge\Counter\Counter;
use Drupal\purge\Plugin\Purge\Purger\Exception\BadBehaviorException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Counter class.
 *
 * @coversDefaultClass \Drupal\purge\Counter\Counter
 * @group purge
 */
#[CoversClass(Counter::class)]
#[Group('purge')]
class CounterTest extends UnitTestCase {

  /**
   * Tests that decrement can be disabled.
   */
  public function testDisableDecrement(): void {
    $counter = new Counter();
    $counter->disableDecrement();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No ::decrement() permission on this object.');
    $counter->decrement();
  }

  /**
   * Tests that increment can be disabled.
   */
  public function testDisableIncrement(): void {
    $counter = new Counter();
    $counter->disableIncrement();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No ::increment() permission on this object.');
    $counter->increment();
  }

  /**
   * Tests that set can be disabled.
   */
  public function testDisableSet(): void {
    $counter = new Counter();
    $counter->disableSet();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('No ::set() permission on this object.');
    $counter->set(5);
  }

  /**
   * Tests getting the counter value as a float.
   *
   * @dataProvider providerTestGet
   */
  #[DataProvider('providerTestGet')]
  public function testGet($value): void {
    $counter = new Counter($value);
    $this->assertEquals($value, $counter->get());
    $this->assertTrue(is_float($counter->get()));
    $this->assertFalse(is_int($counter->get()));
  }

  /**
   * Provides test data for testGet().
   */
  public static function providerTestGet(): array {
    return [
      [0],
      [5],
      [1.3],
      [8.9],
    ];
  }

  /**
   * Tests getting the counter value as an integer.
   *
   * @dataProvider providerTestGetInteger
   */
  #[DataProvider('providerTestGetInteger')]
  public function testGetInteger($value): void {
    $counter = new Counter($value);
    $this->assertEquals((int) $value, $counter->getInteger());
    $this->assertFalse(is_float($counter->getInteger()));
    $this->assertTrue(is_int($counter->getInteger()));
  }

  /**
   * Provides test data for testGetInteger().
   */
  public static function providerTestGetInteger(): array {
    return [
      [0],
      [5],
      [1.3],
      [8.9],
    ];
  }

  /**
   * Tests that set rejects non-numeric values.
   *
   * @dataProvider providerTestSetNotFloatOrInt
   */
  #[DataProvider('providerTestSetNotFloatOrInt')]
  public function testSetNotFloatOrInt($value): void {
    $counter = new Counter();
    $this->expectException(BadBehaviorException::class);
    $this->expectExceptionMessage('Given $value is not a integer or float.');
    $counter->set($value);
  }

  /**
   * Provides test data for testSetNotFloatOrInt().
   */
  public static function providerTestSetNotFloatOrInt(): array {
    return [
      [FALSE],
      ["0"],
      [NULL],
    ];
  }

  /**
   * Tests that set rejects negative values.
   */
  public function testSetNegative(): void {
    $counter = new Counter();
    $this->expectException(BadBehaviorException::class);
    $this->expectExceptionMessage('Given $value can only be zero or positive.');
    $counter->set(-0.000001);
  }

  /**
   * Tests setting the counter value.
   *
   * @dataProvider providerTestSet
   */
  #[DataProvider('providerTestSet')]
  public function testSet($value): void {
    $counter = new Counter();
    $counter->set($value);
    $this->assertEquals($value, $counter->get());
  }

  /**
   * Provides test data for testSet().
   */
  public static function providerTestSet(): array {
    return [
      [0],
      [5],
      [1.3],
      [8.9],
    ];
  }

  /**
   * Tests decrementing the counter.
   *
   * @dataProvider providerTestDecrement
   */
  #[DataProvider('providerTestDecrement')]
  public function testDecrement($start, $subtract, $result): void {
    $counter = new Counter($start);
    $counter->decrement($subtract);
    $this->assertEquals($result, $counter->get());
  }

  /**
   * Provides test data for testDecrement().
   */
  public static function providerTestDecrement(): array {
    return [
      [4.0, 0.2, 3.8],
      [2, 1, 1],
      [1, 1, 0],
    ];
  }

  /**
   * Tests that decrement rejects zero or negative values.
   *
   * @dataProvider providerTestDecrementInvalidValue
   */
  #[DataProvider('providerTestDecrementInvalidValue')]
  public function testDecrementInvalidValue($value): void {
    $counter = new Counter(10);
    $this->expectException(BadBehaviorException::class);
    $this->expectExceptionMessage('Given $amount is zero or negative.');
    $counter->decrement($value);
  }

  /**
   * Provides test data for testDecrementInvalidValue().
   */
  public static function providerTestDecrementInvalidValue(): array {
    return [
      [0],
      [0.0],
      [-1],
    ];
  }

  /**
   * Tests that decrement rejects non-numeric values.
   *
   * @dataProvider providerTestDecrementNotFloatOrInt
   */
  #[DataProvider('providerTestDecrementNotFloatOrInt')]
  public function testDecrementNotFloatOrInt($value): void {
    $counter = new Counter(10);
    $this->expectException(BadBehaviorException::class);
    $this->expectExceptionMessage('Given $amount is not a integer or float.');
    $counter->decrement($value);
  }

  /**
   * Provides test data for testDecrementNotFloatOrInt().
   */
  public static function providerTestDecrementNotFloatOrInt(): array {
    return [
      [FALSE],
      ["0"],
      [NULL],
    ];
  }

  /**
   * Tests incrementing the counter.
   *
   * @dataProvider providerTestIncrement
   */
  #[DataProvider('providerTestIncrement')]
  public function testIncrement($start, $add, $result): void {
    $counter = new Counter($start);
    $counter->increment($add);
    $this->assertEquals($result, $counter->get());
  }

  /**
   * Provides test data for testIncrement().
   */
  public static function providerTestIncrement(): array {
    return [
      [4.0, 0.2, 4.2],
      [0.1, 1, 1.1],
      [2, 1, 3],
    ];
  }

  /**
   * Tests that increment rejects zero or negative values.
   *
   * @dataProvider providerTestIncrementInvalidValue
   */
  #[DataProvider('providerTestIncrementInvalidValue')]
  public function testIncrementInvalidValue($value): void {
    $counter = new Counter(10);
    $this->expectException(BadBehaviorException::class);
    $this->expectExceptionMessage('Given $amount is zero or negative.');
    $counter->increment($value);
  }

  /**
   * Provides test data for testIncrementInvalidValue().
   */
  public static function providerTestIncrementInvalidValue(): array {
    return [
      [0],
      [0.0],
      [-1],
    ];
  }

  /**
   * Tests that increment rejects non-numeric values.
   *
   * @dataProvider providerTestIncrementNotFloatOrInt
   */
  #[DataProvider('providerTestIncrementNotFloatOrInt')]
  public function testIncrementNotFloatOrInt($value): void {
    $counter = new Counter(10);
    $this->expectException(BadBehaviorException::class);
    $this->expectExceptionMessage('Given $amount is not a integer or float.');
    $counter->increment($value);
  }

  /**
   * Provides test data for testIncrementNotFloatOrInt().
   */
  public static function providerTestIncrementNotFloatOrInt(): array {
    return [
      [FALSE],
      ["0"],
      [NULL],
    ];
  }

  /**
   * Tests that the write callback is invoked on value changes.
   *
   * @dataProvider providerTestSetWriteCallback
   */
  #[DataProvider('providerTestSetWriteCallback')]
  public function testSetWriteCallback($value_start, $call, $value_end): void {
    $counter = new Counter($value_start);

    // Pass a callback that modifies the local $passed_value.
    $passed_value = NULL;
    $callback = function ($_value) use (&$passed_value) {
      $passed_value = $_value;
    };
    $counter->setWriteCallback($callback);

    // Call the requested callback and verify that the results match.
    $method = array_shift($call);
    call_user_func_array([$counter, $method], $call);
    $this->assertEquals($passed_value, $value_end);
  }

  /**
   * Provides test data for testSetWriteCallback().
   */
  public static function providerTestSetWriteCallback(): array {
    return [
      [0, ['set', 5], 5],
      [1.8, ['increment', 2.3], 4.1],
      [1.6, ['decrement', 0.3], 1.3],
    ];
  }

}

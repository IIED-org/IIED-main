<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameComponents;

/**
 * @coversDefaultClass \Drupal\name\Utility\NameComponents
 *
 * @group name
 */
class NameComponentsTest extends UnitTestCase {

  /**
   * @covers ::coreKeys
   */
  public function testCoreKeys(): void {
    $this->assertSame([
      'title' => 'title',
      'given' => 'given',
      'middle' => 'middle',
      'family' => 'family',
      'credentials' => 'credentials',
      'generational' => 'generational',
    ], NameComponents::coreKeys());
  }

  /**
   * @covers ::sanitizeValue
   */
  public function testSanitizeValueEscapesDefault(): void {
    $this->assertSame(
      '&lt;script&gt;x&lt;/script&gt;',
      NameComponents::sanitizeValue('<script>x</script>', NULL, 'default'),
    );
  }

  /**
   * @covers ::sanitizeValue
   */
  public function testSanitizeValuePlainStripsTags(): void {
    $this->assertSame('x', NameComponents::sanitizeValue('<b>x</b>', NULL, 'plain'));
  }

  /**
   * @covers ::sanitizeValue
   */
  public function testSanitizeValueRawUnchanged(): void {
    $this->assertSame('<b>x</b>', NameComponents::sanitizeValue('<b>x</b>', NULL, 'raw'));
  }

  /**
   * @covers ::sanitizeValue
   */
  public function testSanitizeValueArrayColumn(): void {
    $item = ['given' => '<i>a</i>'];
    $this->assertSame('&lt;i&gt;a&lt;/i&gt;', NameComponents::sanitizeValue($item, 'given', 'default'));
  }

  /**
   * @covers ::sanitizeValue
   */
  public function testSanitizeValueUsesPrecomputedSafeKey(): void {
    $item = [
      'given' => 'ignored',
      'safe_plain' => ['given' => 'from-cache'],
    ];
    $this->assertSame('from-cache', NameComponents::sanitizeValue($item, 'given', 'plain'));
  }

  /**
   * @covers ::applyLayout
   */
  public function testApplyLayoutAsianHidesGenerational(): void {
    $element = [
      'family' => [],
      'generational' => [
        '#default_value' => 'Jr',
        '#access' => TRUE,
      ],
    ];
    NameComponents::applyLayout($element, 'asian');
    $this->assertSame('', $element['generational']['#default_value']);
    $this->assertFalse($element['generational']['#access']);
    $this->assertSame(1, $element['family']['#weight']);
  }

}

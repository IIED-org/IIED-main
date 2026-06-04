<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Utility;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Utility\NameFormatModifiers;

/**
 * Kernel tests for NameFormatModifiers.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatModifiers
 *
 * @group name
 */
class NameFormatModifiersTest extends KernelTestBase {

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
   * @covers ::apply
   * @covers ::stripSpanWrapper
   */
  public function testApplyPreservesSpanWrapper(): void {
    $input  = '<span class="given">JANE</span>';
    $result = NameFormatModifiers::apply($input, 'L');

    $this->assertStringContainsString('class="given"', $result);
    $this->assertStringContainsString('>jane<', $result);
  }

  /**
   * @covers ::apply
   * @covers ::applySingleModifier
   */
  public function testApplyUnknownModifierReturnsUnchanged(): void {
    $this->assertSame('Jane', NameFormatModifiers::apply('Jane', 'Z'));
  }

  /**
   * @covers ::apply
   * @covers ::stripSpanWrapper
   */
  public function testApplyWithoutSpanWrapperModifiesPlainString(): void {
    $this->assertSame('jane', NameFormatModifiers::apply('JANE', 'L'));
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameFormatModifiers;

/**
 * Unit tests for NameFormatModifiers.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatModifiers
 *
 * @group name
 */
class NameFormatModifiersTest extends UnitTestCase {

  /**
   * @covers ::apply
   */
  public function testApplyLowercaseUppercaseAndTrim(): void {
    $this->assertSame('john', NameFormatModifiers::apply('JOHN', 'L'));
    $this->assertSame('JOHN', NameFormatModifiers::apply('john', 'U'));
    $this->assertSame('John doe', NameFormatModifiers::apply(' John doe ', 'T'));
  }

  /**
   * @covers ::apply
   * @covers ::stripSpanWrapper
   */
  public function testApplyPreservesSpanWrapper(): void {
    $input  = '<span class="given">JOHN</span>';
    $result = NameFormatModifiers::apply($input, 'L');
    $this->assertStringContainsString('class="given"', $result);
    $this->assertStringContainsString('>john<', $result);
  }

  /**
   * @covers ::apply
   */
  public function testApplyEmptyStringReturnsEmpty(): void {
    $this->assertSame('', NameFormatModifiers::apply('', 'LU'));
  }

  /**
   * @covers ::apply
   */
  public function testApplyNoModifiersReturnsUnchanged(): void {
    $this->assertSame('John', NameFormatModifiers::apply('John', ''));
  }

  /**
   * @covers ::apply
   * @covers ::applySingleModifier
   */
  public function testApplyUnknownModifierReturnsUnchanged(): void {
    $this->assertSame('John', NameFormatModifiers::apply('John', 'Z'));
  }

  /**
   * @covers ::apply
   * @covers ::stripSpanWrapper
   */
  public function testApplyWithoutSpanWrapperModifiesPlainString(): void {
    $this->assertSame('john', NameFormatModifiers::apply('JOHN', 'L'));
  }

  /**
   * @covers ::apply
   */
  public function testApplyFirstLetterModifiers(): void {
    $this->assertSame('John', NameFormatModifiers::apply('john', 'F'));
    $this->assertSame('John Doe', NameFormatModifiers::apply('john doe', 'G'));
  }

  /**
   * @covers ::apply
   */
  public function testApplyWordBoundaryModifiers(): void {
    $this->assertSame('John', NameFormatModifiers::apply('John Doe', 'B'));
    $this->assertSame('Doe', NameFormatModifiers::apply('John Doe', 'b'));
  }

}

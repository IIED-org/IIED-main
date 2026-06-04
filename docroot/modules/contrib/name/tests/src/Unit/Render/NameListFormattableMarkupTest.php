<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Render;

use Drupal\Core\Render\Markup;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Render\NameListFormattableMarkup;

/**
 * @coversDefaultClass \Drupal\name\Render\NameListFormattableMarkup
 *
 * @group name
 */
class NameListFormattableMarkupTest extends UnitTestCase {

  /**
   * @covers ::jsonSerialize
   * @covers ::__toString
   */
  public function testJsonSerializeMatchesToStringWhenEmpty(): void {
    $markup = new NameListFormattableMarkup();
    $this->assertSame('', $markup->__toString());
    $this->assertSame('', $markup->jsonSerialize());
  }

  /**
   * @covers ::jsonSerialize
   * @covers ::__toString
   */
  public function testJsonSerializeMatchesToStringWithNamesAndSeparator(): void {
    $markup = new NameListFormattableMarkup(['Ada', 'Grace'], '; ');
    $this->assertSame('Ada; Grace', $markup->__toString());
    $this->assertSame('Ada; Grace', $markup->jsonSerialize());
  }

  /**
   * @covers ::jsonSerialize
   * @covers ::__toString
   */
  public function testJsonSerializeMatchesToStringWithEscaping(): void {
    $markup = new NameListFormattableMarkup(['<b>A</b>', 'B&Co'], ', ');
    $this->assertSame('&lt;b&gt;A&lt;/b&gt;, B&amp;Co', $markup->__toString());
    $this->assertSame('&lt;b&gt;A&lt;/b&gt;, B&amp;Co', $markup->jsonSerialize());
  }

  /**
   * @covers ::jsonSerialize
   * @covers ::__toString
   */
  public function testJsonSerializeEscapesUntrustedSeparatorMarkup(): void {
    $markup = new NameListFormattableMarkup(['Ada', 'Grace'], '<b>, </b>');
    $this->assertSame('Ada&lt;b&gt;, &lt;/b&gt;Grace', $markup->__toString());
    $this->assertSame('Ada&lt;b&gt;, &lt;/b&gt;Grace', $markup->jsonSerialize());
  }

  /**
   * @covers ::jsonSerialize
   * @covers ::__toString
   */
  public function testJsonSerializePreservesTrustedSeparatorMarkup(): void {
    $separator = Markup::create('<span class="sep">, </span>');
    $markup = new NameListFormattableMarkup(['Ada', 'Grace'], $separator);
    $this->assertSame('Ada<span class="sep">, </span>Grace', $markup->__toString());
    $this->assertSame('Ada<span class="sep">, </span>Grace', $markup->jsonSerialize());
  }

}

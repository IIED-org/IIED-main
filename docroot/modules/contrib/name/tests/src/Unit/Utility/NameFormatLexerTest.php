<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameFormatLexer;

/**
 * Unit tests for NameFormatLexer.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatLexer
 *
 * @group name
 */
class NameFormatLexerTest extends UnitTestCase {

  /**
   * @covers ::isModifierChar
   */
  public function testIsModifierCharIdentifiesModifierCharacters(): void {
    foreach (['L', 'U', 'F', 'T', 'S', 'G', 'B', 'b'] as $char) {
      $this->assertTrue(NameFormatLexer::isModifierChar($char));
    }

    $this->assertFalse(NameFormatLexer::isModifierChar('g'));
    $this->assertFalse(NameFormatLexer::isModifierChar('+'));
  }

  /**
   * @covers ::isConditionChar
   */
  public function testIsConditionCharIdentifiesConditionCharacters(): void {
    foreach (['=', '^', '|', '+', '-', '~'] as $char) {
      $this->assertTrue(NameFormatLexer::isConditionChar($char));
    }

    $this->assertFalse(NameFormatLexer::isConditionChar('g'));
    $this->assertFalse(NameFormatLexer::isConditionChar('L'));
  }

  /**
   * @covers ::processBracketGroup
   */
  public function testProcessBracketGroupParsesMatchedGroup(): void {
    $result = NameFormatLexer::processBracketGroup('(g)', 0, ['g' => 'John'], 'L', '-');

    $this->assertIsArray($result['piece']);
    $this->assertSame('john', $result['piece']['value']);
    $this->assertSame('-', $result['piece']['conditions']);
    $this->assertSame(2, $result['advance']);
  }

  /**
   * @covers ::processBracketGroup
   */
  public function testProcessBracketGroupPreservesUnmatchedOpeningBracket(): void {
    $result = NameFormatLexer::processBracketGroup('(g', 0, ['g' => 'John'], '', '');

    $this->assertIsArray($result['piece']);
    $this->assertSame('(', $result['piece']['value']);
    $this->assertSame(0, $result['advance']);
  }

  /**
   * @covers ::processBracketGroup
   */
  public function testProcessBracketGroupPreservesClosingBracket(): void {
    $result = NameFormatLexer::processBracketGroup(')', 0, [], '', '');

    $this->assertIsArray($result['piece']);
    $this->assertSame(')', $result['piece']['value']);
    $this->assertSame(0, $result['advance']);
  }

  /**
   * @covers ::closingBracketPosition
   */
  public function testClosingBracketPositionFindsMatchingClose(): void {
    $this->assertSame(4, NameFormatLexer::closingBracketPosition('(abc)'));
    // Nested brackets: outer closes at position 7.
    $this->assertSame(7, NameFormatLexer::closingBracketPosition('(a(bc)d)'));
  }

  /**
   * @covers ::closingBracketPosition
   */
  public function testClosingBracketPositionReturnsFalseWhenUnmatched(): void {
    $this->assertFalse(NameFormatLexer::closingBracketPosition('(abc'));
  }

}

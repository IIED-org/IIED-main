<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameFormatParser;

/**
 * Unit tests for NameFormatParser (facade).
 *
 * Covers the public static surface of the facade and verifies that each
 * delegator produces the same result as the underlying implementation
 * class. Comprehensive behavior tests live in the implementation test
 * classes: NameFormatLexerTest, NameFormatModifiersTest,
 * NameFormatAssemblerTest, and NameFormatTokensTest.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatParser
 *
 * @group name
 */
class NameFormatParserTest extends UnitTestCase {

  /**
   * @covers ::format
   */
  public function testFormatEmptyStringReturnsEmpty(): void {
    $this->assertSame('', NameFormatParser::format('', ['g' => 'John']));
  }

  /**
   * @covers ::format
   */
  public function testFormatResolvesTokensAndAppliesConditions(): void {
    $tokens = ['g' => 'John', 'f' => 'Doe', 'i' => ' '];
    $this->assertSame('John Doe', NameFormatParser::format('gif', $tokens));
  }

  /**
   * Facade delegates applyModifiers correctly.
   *
   * @covers ::applyModifiers
   */
  public function testApplyModifiersDelegatesToModifiers(): void {
    $this->assertSame('john', NameFormatParser::applyModifiers('JOHN', 'L'));
    $this->assertSame('JOHN', NameFormatParser::applyModifiers('john', 'U'));
    $input  = '<span class="given">JOHN</span>';
    $result = NameFormatParser::applyModifiers($input, 'L');
    $this->assertStringContainsString('class="given"', $result);
    $this->assertStringContainsString('>john<', $result);
  }

  /**
   * Facade delegates closingBracketPosition correctly.
   *
   * @covers ::closingBracketPosition
   */
  public function testClosingBracketPositionDelegatesToLexer(): void {
    $this->assertSame(4, NameFormatParser::closingBracketPosition('(abc)'));
    $this->assertFalse(NameFormatParser::closingBracketPosition('(abc'));
  }

  /**
   * Facade delegates isModifierChar correctly.
   *
   * @covers ::isModifierChar
   */
  public function testIsModifierCharDelegatesToLexer(): void {
    $this->assertTrue(NameFormatParser::isModifierChar('L'));
    $this->assertFalse(NameFormatParser::isModifierChar('g'));
  }

  /**
   * Facade delegates isConditionChar correctly.
   *
   * @covers ::isConditionChar
   */
  public function testIsConditionCharDelegatesToLexer(): void {
    $this->assertTrue(NameFormatParser::isConditionChar('+'));
    $this->assertFalse(NameFormatParser::isConditionChar('L'));
  }

  /**
   * Facade delegates resolveTokenValue correctly.
   *
   * @covers ::resolveTokenValue
   */
  public function testResolveTokenValueDelegatesToTokens(): void {
    $tokens = ['g' => 'John', 'd' => NULL];
    $this->assertSame('John', NameFormatParser::resolveTokenValue('g', $tokens));
    $this->assertSame('', NameFormatParser::resolveTokenValue('d', $tokens));
    $this->assertSame('x', NameFormatParser::resolveTokenValue('x', $tokens));
  }

  /**
   * Facade delegates applyConditions correctly.
   *
   * @covers ::applyConditions
   */
  public function testApplyConditionsDelegatesToAssembler(): void {
    $pieces = [
      ['value' => 'John', 'conditions' => ''],
      ['value' => ' ', 'conditions' => '+'],
      ['value' => 'Doe', 'conditions' => ''],
    ];
    $this->assertSame('John Doe', NameFormatParser::applyConditions($pieces));
  }

  /**
   * Facade delegates pieceConditionMet correctly.
   *
   * @covers ::pieceConditionMet
   */
  public function testPieceConditionMetDelegatesToAssembler(): void {
    $this->assertTrue(NameFormatParser::pieceConditionMet('+', 'John', 'Doe'));
    $this->assertFalse(NameFormatParser::pieceConditionMet('+', '', 'Doe'));
  }

  /**
   * Facade delegates processBracketGroup correctly.
   *
   * @covers ::processBracketGroup
   */
  public function testProcessBracketGroupDelegatesToLexer(): void {
    $result = NameFormatParser::processBracketGroup('(g)', 0, ['g' => 'John'], 'L', '-');
    $this->assertSame('john', $result['piece']['value']);
    $this->assertSame(2, $result['advance']);
  }

}

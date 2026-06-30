<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameFormatAssembler;

/**
 * Unit tests for NameFormatAssembler.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatAssembler
 *
 * @group name
 */
class NameFormatAssemblerTest extends UnitTestCase {

  /**
   * @covers ::assemble
   *
   * @dataProvider assembleProvider
   */
  public function testAssembleAppliesConditionalFlags(
    array $pieces,
    string $expected,
  ): void {
    $this->assertSame($expected, NameFormatAssembler::assemble($pieces));
  }

  /**
   * Provides test cases for conditional piece handling.
   *
   * @return array[]
   *   The test cases.
   */
  public static function assembleProvider(): array {
    return [
      'unconditional pieces are included' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' ', 'conditions' => ''],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        'John Doe',
      ],
      'plus condition includes with both neighbors' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '+'],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        'John Doe',
      ],
      'plus condition excludes with empty neighbor' => [
        [
          ['value' => '', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '+'],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        'Doe',
      ],
      'minus condition includes after present neighbor' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '-'],
          ['value' => '', 'conditions' => ''],
        ],
        'John ',
      ],
      'minus condition excludes after empty neighbor' => [
        [
          ['value' => '', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '-'],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        'Doe',
      ],
      'tilde condition includes after empty neighbor' => [
        [
          ['value' => '', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '~'],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        ' Doe',
      ],
      'tilde condition excludes after present neighbor' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '~'],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        'JohnDoe',
      ],
      'caret condition includes before empty neighbor' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' Jr.', 'conditions' => '^'],
          ['value' => '', 'conditions' => ''],
        ],
        'John Jr.',
      ],
      'caret condition excludes before present neighbor' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' Jr.', 'conditions' => '^'],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        'JohnDoe',
      ],
      'equals condition includes before present neighbor' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '='],
          ['value' => 'Doe', 'conditions' => ''],
        ],
        'John Doe',
      ],
      'equals condition excludes before empty neighbor' => [
        [
          ['value' => 'John', 'conditions' => ''],
          ['value' => ' ', 'conditions' => '='],
          ['value' => '', 'conditions' => ''],
        ],
        'John',
      ],
      'fallback condition includes after empty neighbor' => [
        [
          ['value' => '', 'conditions' => ''],
          ['value' => 'Fallback', 'conditions' => '|'],
          ['value' => '', 'conditions' => ''],
        ],
        'Fallback',
      ],
      'fallback condition excludes after present neighbor' => [
        [
          ['value' => 'Primary', 'conditions' => ''],
          ['value' => 'Fallback', 'conditions' => '|'],
          ['value' => '', 'conditions' => ''],
        ],
        'Primary',
      ],
      'fallback condition overrides matching condition' => [
        [
          ['value' => 'Primary', 'conditions' => ''],
          ['value' => 'Fallback', 'conditions' => '|-'],
          ['value' => '', 'conditions' => ''],
        ],
        'Primary',
      ],
      'escaped backslashes are converted to tabs' => [
        [
          ['value' => 'John\\\\Doe', 'conditions' => ''],
        ],
        "John\tDoe",
      ],
    ];
  }

  /**
   * @covers ::pieceConditionMet
   * @covers ::conditionMet
   * @covers ::plusConditionMet
   * @covers ::minusConditionMet
   * @covers ::tildeConditionMet
   * @covers ::caretConditionMet
   * @covers ::equalsConditionMet
   *
   * @dataProvider pieceConditionMetProvider
   */
  public function testPieceConditionMetChecksSurroundingPieces(
    string $conditions,
    string|false $last_component,
    string|false $next_component,
    bool $expected,
  ): void {
    $this->assertSame(
      $expected,
      NameFormatAssembler::pieceConditionMet($conditions, $last_component, $next_component),
    );
  }

  /**
   * Provides test cases for condition matching.
   *
   * @return array[]
   *   The test cases.
   */
  public static function pieceConditionMetProvider(): array {
    return [
      'plus matches with both neighbors'        => ['+', 'John', 'Doe', TRUE],
      'plus misses without previous neighbor'   => ['+', '', 'Doe', FALSE],
      'minus matches with previous neighbor'    => ['-', 'John', FALSE, TRUE],
      'minus misses without previous neighbor'  => ['-', '', FALSE, FALSE],
      'tilde matches without previous neighbor' => ['~', '', 'Doe', TRUE],
      'tilde matches with false previous value' => ['~', FALSE, 'Doe', TRUE],
      'tilde misses with previous neighbor'     => ['~', 'John', 'Doe', FALSE],
      'caret matches without next neighbor'     => ['^', 'John', '', TRUE],
      'caret misses with next neighbor'         => ['^', 'John', 'Doe', FALSE],
      'equals matches with next neighbor'       => ['=', 'John', 'Doe', TRUE],
      'equals misses without next neighbor'     => ['=', 'John', '', FALSE],
      'unrecognized condition returns false'    => ['|', 'John', 'Doe', FALSE],
    ];
  }

}

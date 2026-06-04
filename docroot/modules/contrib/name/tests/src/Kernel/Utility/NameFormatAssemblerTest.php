<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Utility;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Utility\NameFormatAssembler;

/**
 * Kernel tests for NameFormatAssembler.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatAssembler
 *
 * @group name
 */
class NameFormatAssemblerTest extends KernelTestBase {

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
   * @covers ::pieceConditionMet
   * @covers ::conditionMet
   * @covers ::tildeConditionMet
   */
  public function testPieceConditionMetTildeCondition(): void {
    $this->assertTrue(NameFormatAssembler::pieceConditionMet('~', '', 'Doe'));
    $this->assertFalse(NameFormatAssembler::pieceConditionMet('~', 'John', 'Doe'));
  }

  /**
   * @covers ::pieceConditionMet
   */
  public function testPieceConditionMetUnrecognizedConditionReturnsFalse(): void {
    $this->assertFalse(NameFormatAssembler::pieceConditionMet('|', 'John', 'Doe'));
  }

}

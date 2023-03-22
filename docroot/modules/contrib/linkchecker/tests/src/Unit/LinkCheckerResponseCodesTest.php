<?php

namespace Drupal\Tests\linkchecker\Unit;

use Drupal\linkchecker\LinkCheckerResponseCodes;
use Drupal\Tests\UnitTestCase;

/**
 * Tests \Drupal\linkchecker\LinkCheckerResponseCodes.
 *
 * @group linkchecker
 *
 * @coversDefaultClass \Drupal\linkchecker\LinkCheckerResponseCodes
 */
class LinkCheckerResponseCodesTest extends UnitTestCase {

  /**
   * The linkchecker response codes service.
   *
   * @var \Drupal\linkchecker\LinkCheckerResponseCodes
   */
  protected $linkCheckerResponseCodes;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->linkCheckerResponseCodes = new LinkCheckerResponseCodes();
  }

  /**
   * Tests the the ::isValid method.
   *
   * @param int $code
   *   The HTTP response code.
   * @param bool $result
   *   The expected result from calling the function.
   *
   * @dataProvider isValidDataProvider
   *
   * @covers ::isValid
   */
  public function testIsValid(int $code, bool $result) {
    $this->assertEquals($result, $this->linkCheckerResponseCodes->isValid($code));
  }

  /**
   * Data provider for testIsValid().
   *
   * @see testIsValid()
   */
  public function isValidDataProvider() {
    return [
      [100, TRUE],
      [101, TRUE],
      [102, FALSE],
      [103, FALSE],
      [200, TRUE],
      [201, TRUE],
      [202, TRUE],
      [203, TRUE],
      [204, TRUE],
      [205, TRUE],
      [206, TRUE],
      [207, FALSE],
      [208, FALSE],
      [226, FALSE],
      [300, TRUE],
      [301, TRUE],
      [302, TRUE],
      [303, TRUE],
      [304, TRUE],
      [305, TRUE],
      [306, FALSE],
      [307, TRUE],
      [308, FALSE],
      [400, TRUE],
      [401, TRUE],
      [402, TRUE],
      [403, TRUE],
      [404, TRUE],
      [405, TRUE],
      [406, TRUE],
      [407, TRUE],
      [408, TRUE],
      [409, TRUE],
      [410, TRUE],
      [411, TRUE],
      [412, TRUE],
      [413, TRUE],
      [414, TRUE],
      [415, TRUE],
      [416, TRUE],
      [417, TRUE],
      [418, FALSE],
      [421, FALSE],
      [422, FALSE],
      [423, FALSE],
      [424, FALSE],
      [425, FALSE],
      [426, FALSE],
      [428, FALSE],
      [429, FALSE],
      [431, FALSE],
      [451, FALSE],
      [500, TRUE],
      [501, TRUE],
      [502, TRUE],
      [503, TRUE],
      [504, TRUE],
      [505, TRUE],
      [506, FALSE],
      [507, FALSE],
      [508, FALSE],
      [510, FALSE],
      [511, FALSE],
    ];
  }

}

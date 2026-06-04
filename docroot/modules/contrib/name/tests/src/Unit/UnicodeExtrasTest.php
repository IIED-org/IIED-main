<?php

namespace Drupal\Tests\name\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\UnicodeExtras;

/**
 * Tests the unicode additional functions.
 *
 * @group name
 */
class UnicodeExtrasTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'UnicodeExtras Test',
      'description' => 'Test UnicodeExtras additional functions',
      'group' => 'Name',
    ];
  }

  /**
   * Convert names() to PHPUnit compatible format.
   *
   * @return array
   *   An array of names.
   */
  public static function patternDataProvider() {
    $data = [];

    foreach (static::languageStrings() as $language => $info) {
      $data[] = [
        $language,
        $info['string'],
        $info['count'],
        $info['initials'],
      ];
    }

    return $data;
  }

  /**
   * Test UnicodeExtras functions.
   *
   * @dataProvider patternDataProvider
   */
  public function testUnicodeExtras($language, $text, $count, $expected_initials) {
    $parts = UnicodeExtras::explode($text);
    $initials = UnicodeExtras::initials($text);
    $this->assertEquals(count($parts), $count, 'Count parts for ' . $language . ' explode match.');
    $this->assertEquals($initials, $expected_initials, 'Initials for ' . $language . ' explode match. ' . $initials . ' ');
  }

  /**
   * Helper function to provide data for the tests.
   *
   * @return array
   *   A keyed array of test data in misc languages.
   */
  protected static function languageStrings() {
    return [
      'english' => [
        'string' => 'A fat cat sat on the mat',
        'count' => 7,
        'initials' => 'AFCSOTM',
      ],
      'greek' => [
        'string' => 'Σὲ γνωρίζω ἀπὸ τὴν κόψη',
        'count' => 5,
        'initials' => 'ΣΓἈΤΚ',
      ],
      /*
      'georgian' => [
        'string' => 'გთხოვთ ახლავე გაიაროთ რეგისტრაცია',
        'count' => 4,
        'initials' => 'გაგრ',
      ],
       */
      'russian' => [
        'string' => 'Зарегистрируйтесь сейчас на Десятую Международную Конференцию',
        'count' => 6,
        'initials' => 'ЗСНДМК',
      ],
      'ethiopian' => [
        'string' => 'ሰማይ አይታረስ ንጉሥ አይከሰስ',
        'count' => 4,
        'initials' => 'ሰአንአ',
      ],
    ];
  }

}

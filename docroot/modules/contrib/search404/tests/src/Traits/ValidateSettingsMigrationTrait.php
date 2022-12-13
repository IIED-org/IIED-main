<?php

namespace Drupal\Tests\search404\Traits;

/**
 * Functions common to settings migration tests.
 */
trait ValidateSettingsMigrationTrait {

  /**
   * Return a random boolean value.
   *
   * @return bool
   *   A random boolean value.
   */
  protected function randomBoolean() {
    return boolval(mt_rand(0, 1));
  }

  /**
   * Return a space-separated list of random file extensions.
   *
   * @param int $count
   *   The number of file extensions to return.
   *
   * @return bool
   *   A string containing a number of space-separated words of 2-5 characters.
   */
  protected function randomFileExtensions($count = 5) {
    return $this->randomSpaceSeparatedWords($count, 2, 5);
  }

  /**
   * Returns a random regular expression.
   *
   * The regular expression is essentially just a random string that has been
   * regex-escaped.
   *
   * @param string $delimiter
   *   The regex delimiter to use.
   * @param int $length
   *   The length of the regular expression, excluding the delimiter. Note the
   *   result will be at least 2 characters longer than this number (i.e.:
   *   because it has the delimiter at the start and end); possibly more if any
   *   regular-expression control characters appear in the string (i.e.: because
   *   those will be escaped).
   *
   * @return string
   *   A random regular expression.
   */
  protected function randomRegex($delimiter = '/', $length = 8) {
    return $delimiter . preg_quote($this->randomString($length), $delimiter) . $delimiter;
  }

  /**
   * Return a space-separated list of random words.
   *
   * @param int $wordCount
   *   The number of words to return.
   * @param int $wordMinLength
   *   The minimum length of words.
   * @param int $wordMaxLength
   *   The maximum length of words.
   *
   * @return string
   *   A space-separated list of words.
   */
  protected function randomSpaceSeparatedWords($wordCount = 5, $wordMinLength = 2, $wordMaxLength = 10) {
    $answerArray = [];

    for ($i = 0; $i <= $wordCount; $i++) {
      $answerArray[] = $this->getRandomGenerator()->word(mt_rand($wordMinLength, $wordMaxLength));
    }

    return implode(' ', $answerArray);
  }

}

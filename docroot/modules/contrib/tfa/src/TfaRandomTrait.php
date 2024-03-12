<?php

namespace Drupal\tfa;

/**
 * Trait TfaRandomTrait for generating cryptographically secure random data.
 *
 * @package Drupal\tfa
 */
trait TfaRandomTrait {

  /**
   * Numbers allowed during random string generation.
   *
   * Note that the numbers '0' and '1' have been remove to avoid confusion with
   * the letters 'O' and 'l'.
   *
   * @var string
   */
  protected $allowedRandomNumbers = '23456789';

  /**
   * Letters allowed during random string generation.
   *
   * Note that the letters 'O' and 'l' have been removed to avoid confusion with
   * the numbers '0' and '1'.
   *
   * @var string
   */
  protected $allowedRandomLetters = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';

  /**
   * Generate a random integer of the given character length.
   *
   * @param int $length
   *   Length of returned value.
   *
   * @return int
   *   Random integer of given length.
   *
   * @throws \Exception
   */
  public function randomInteger($length) {
    return $this->randomCharacters($length, $this->allowedRandomNumbers);
  }

  /**
   * Generate a random string of the given character length.
   *
   * @param int $length
   *   Length of returned value.
   *
   * @return string
   *   Random integer of given length.
   *
   * @throws \Exception
   */
  public function randomString($length) {
    return $this->randomCharacters($length, $this->allowedRandomLetters);
  }

  /**
   * Generate random characters of the given length and allowable characters.
   *
   * @param int $length
   *   The desired length of the returned string.
   * @param string $allowable_characters
   *   Characters that are allowed to be return in the generated string.
   *
   * @return string
   *   Random string of given length and allowed characters.
   *
   * @throws \Exception
   */
  protected function randomCharacters($length, $allowable_characters) {
    // Zero-based count of characters in the allowable list:
    $len = strlen($allowable_characters) - 1;

    // Start with a blank string.
    $characters = '';

    // Loop the number of times specified by $length.
    for ($i = 0; $i < $length; $i++) {
      do {
        // Find a secure random number within the range needed.
        $index = ord(random_bytes(1));
      } while ($index > $len);

      // Each iteration, pick a random character from the
      // allowable string and append it to the string we're building.
      $characters .= $allowable_characters[$index];
    }

    return $characters;
  }

}

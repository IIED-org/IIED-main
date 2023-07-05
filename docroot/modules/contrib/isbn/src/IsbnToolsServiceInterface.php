<?php

namespace Drupal\isbn;

/**
 * Interface for the service "isbn.isbn_service".
 */
interface IsbnToolsServiceInterface {

  /**
   * Formats an ISBN number.
   *
   * @param string $isbn
   *   The ISBN-10 or ISBN-13 number.
   *
   * @return string|null
   *   The formatted ISBN number or null if the ISBN is invalid.
   */
  public function format(string $isbn): ?string;

  /**
   * Returns whether the given ISBN is a valid ISBN-10 or ISBN-13.
   *
   * @param string $isbn
   *   The unformatted ISBN.
   *
   * @return bool
   *   True if the ISBN is considered valid, false otherwise.
   */
  public function isValidIsbn(string $isbn): bool;

  /**
   * Converts an ISBN-10 to an ISBN-13.
   *
   * @param string $isbn
   *   The ISBN-10 to convert.
   *
   * @return string|null
   *   The converted, unformatted ISBN-13 or null if the ISBN is invalid.
   */
  public function convertIsbn10to13(string $isbn): ?string;

  /**
   * Converts an ISBN-13 to an ISBN-10.
   *
   * Only ISBN-13 numbers starting with 978 can be converted to an ISBN-10.
   * If the input ISBN is a valid ISBN-13 but does not start with 978, null is
   * returned.
   *
   * @param string $isbn
   *   The ISBN-13 to convert.
   *
   * @return string|null
   *   The converted, unformatted ISBN-10 or null if the ISBN is invalid.
   */
  public function convertIsbn13to10(string $isbn): ?string;

  /**
   * Cleans up isbn value by removing invalid characters from it.
   *
   * @return string
   *   The cleaned string.
   */
  public function cleanup(string $isbn): string;

}

<?php

namespace Drupal\isbn;

use Nicebooks\Isbn\Exception\InvalidIsbnException;
use Nicebooks\Isbn\IsbnTools;

/**
 * Wrapper around the IsbnTools class, provided as a Drupal service.
 */
class IsbnToolsService implements IsbnToolsServiceInterface {

  /**
   * A IsbnTools object.
   *
   * @var \Nicebooks\Isbn\IsbnTools
   */
  protected $isbnTools;

  /**
   * Constructs a new IsbnToolsService object.
   *
   * @throws \RuntimeException
   *   In case the IsbnTools class cannot be found.
   */
  public function __construct() {
    if (!class_exists(IsbnTools::class)) {
      throw new \RuntimeException('The ISBN module requires the nicebooks/isbn library.');
    }
    $this->isbnTools = new IsbnTools();
  }

  /**
   * {@inheritdoc}
   */
  public function format(string $isbn): ?string {
    try {
      return $this->isbnTools->format($isbn);
    }
    catch (InvalidIsbnException $e) {
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidIsbn(string $isbn): bool {
    return $this->isbnTools->isValidIsbn($isbn);
  }

  /**
   * {@inheritdoc}
   */
  public function convertIsbn10to13(string $isbn): ?string {
    try {
      return $this->isbnTools->convertIsbn10to13($isbn);
    }
    catch (InvalidIsbnException $e) {
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function convertIsbn13to10(string $isbn): ?string {
    try {
      return $this->isbnTools->convertIsbn13to10($isbn);
    }
    catch (\Exception $e) {
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup(string $isbn): string {
    $result = preg_replace('/[^0-9a-zA-Z]/', '', $isbn);
    if (is_null($result)) {
      return '';
    }
    return $result;
  }

}

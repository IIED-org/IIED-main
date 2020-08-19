<?php

namespace Drupal\isbn;


use Nicebooks\Isbn\Exception\InvalidIsbnException;
use Nicebooks\Isbn\IsbnTools;

class IsbnToolsService {

  protected $isbn_tools;

  /**
   * ISBNService constructor.
   */
  public function __construct() {
    $this->isbn_tools = new IsbnTools();
  }

  public function format($number) {
    try {
      return $this->isbn_tools->format($number);
    } catch (InvalidIsbnException $e) {

    }
  }

  public function isValidIsbn($number) {
    return $this->isbn_tools->isValidIsbn($number);
  }

  public function convertIsbn10to13($number) {
    try {
      return $this->isbn_tools->convertIsbn10to13($number);
    } catch (InvalidIsbnException $e) {
    }
  }

  public function convertIsbn13to10($number) {
    try {
      return $this->isbn_tools->convertIsbn13to10($number);
    } catch (\Exception $e) {
    }
  }

  public function cleanup($number) {
    return preg_replace('/[^0-9a-zA-Z]/', '', $number);
  }

}

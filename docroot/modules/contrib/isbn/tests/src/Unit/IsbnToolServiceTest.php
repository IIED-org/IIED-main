<?php

namespace Drupal\Tests\isbn\Unit;

use Drupal\isbn\IsbnToolsService;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\isbn\IsbnToolsService
 * @group isbn
 */
class IsbnToolServiceTest extends UnitTestCase {

  /**
   * The ISBN Tools service.
   *
   * @var \Drupal\isbn\IsbnToolsService
   */
  protected $isbnTools;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->isbnTools = new IsbnToolsService();
  }

  /**
   * @covers ::format
   */
  public function testFormat() {
    $this->assertEquals('0-12-345678-9', $this->isbnTools->format('0123456789'));
  }

  /**
   * @covers ::format
   */
  public function testFormatWithInvalidValue() {
    $this->assertNull($this->isbnTools->format('abc'));
  }

  /**
   * @covers ::isValidIsbn
   */
  public function testIsValidIsbn() {
    $this->assertTrue($this->isbnTools->isValidIsbn('0123456789'));
    $this->assertFalse($this->isbnTools->isValidIsbn('abc'));
  }

  /**
   * @covers ::convertIsbn10to13
   */
  public function testConvertIsbn10to13() {
    $this->assertEquals('9780123456786', $this->isbnTools->convertIsbn10to13('0123456789'));
  }

  /**
   * @covers ::convertIsbn10to13
   */
  public function testConvertIsbn10to13WithInvalidValue() {
    $this->assertNull($this->isbnTools->convertIsbn10to13('abc'));
  }

  /**
   * @covers ::convertIsbn13to10
   */
  public function testConvertIsbn13to10() {
    $this->assertEquals('0123456789', $this->isbnTools->convertIsbn13to10('9780123456786'));
  }

  /**
   * @covers ::convertIsbn13to10
   */
  public function testConvertIsbn13to10WithInvalidValue() {
    $this->assertNull($this->isbnTools->convertIsbn13to10('abc'));
  }

}

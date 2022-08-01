<?php

namespace Drupal\Tests\search_api_sorts\Unit;

use Drupal\search_api_sorts\SortsField;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the sorts field.
 *
 * @group search_api_sorts
 */
class SortsFieldTest extends UnitTestCase {

  /**
   * Tests for the value object, with just a field.
   */
  public function testSortsField() {
    $field = new SortsField('donkey');

    $this->assertEquals('donkey', $field->getFieldName());
    $this->assertEquals('asc', $field->getOrder());
  }

  /**
   * Tests with a sorts direction.
   *
   * @dataProvider provideSortOrders
   */
  public function testGetActiveSort($order_argument, $expected) {
    $field = new SortsField('monkey', $order_argument);

    $this->assertEquals('monkey', $field->getFieldName());
    $this->assertEquals($expected, $field->getOrder());
  }

  /**
   * Tests getters and setters.
   */
  public function testSetters() {
    $field = new SortsField('donkey');

    $field->setOrder('owl');
    $this->assertEquals('asc', $field->getOrder());

    $field->setOrder('desc');
    $this->assertEquals('desc', $field->getOrder());

    $field->setOrder('asc');
    $this->assertEquals('asc', $field->getOrder());

    $this->assertEquals('donkey', $field->getFieldName());
    $field->setFieldName('owl');
    $this->assertEquals('owl', $field->getFieldName());
  }

  /**
   * Provides mock data and expected results for ::testActiveSortOrder.
   *
   * @return array
   *   An array of mocked data.
   */
  public function provideSortOrders() {
    return [
      ['asc', 'asc'],
      ['desc', 'desc'],
      ['aaa', 'asc'],
      [NULL, 'asc'],
    ];
  }

}

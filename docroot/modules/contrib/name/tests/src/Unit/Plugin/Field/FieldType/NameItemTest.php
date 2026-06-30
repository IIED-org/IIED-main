<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Plugin\Field\FieldType;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Plugin\Field\FieldType\NameItem;

/**
 * Tests static behavior of the Name field item plugin.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Plugin\Field\FieldType\NameItem
 */
final class NameItemTest extends UnitTestCase {

  /**
   * @covers ::mainPropertyName
   */
  public function testMainPropertyNameIsNull(): void {
    $this->assertNull(NameItem::mainPropertyName());
  }

}

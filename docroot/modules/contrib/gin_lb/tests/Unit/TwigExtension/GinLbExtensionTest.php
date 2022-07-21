<?php

namespace Drupal\Tests\gin_lb\Unit\TwigExtension;

use Drupal\Core\Template\Attribute;
use Drupal\gin_lb\TwigExtension\GinLbExtension;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\gin_lb\TwigExtension\GinLbExtension
 * @group gin_lb
 */
class GinLbExtensionTest extends UnitTestCase {

  /**
   * @covers ::calculateDependencies
   */
  public function testGinClasses() {
    $attributes = new Attribute();
    $attributes->addClass('form-item');
    $attributes->addClass('form-item-2');
    $attributes->addClass('js-form-item');

    $cleaned_attributes = GinLbExtension::ginClasses($attributes);
    $this->assertSame('class="glb-form-item glb-form-item-2 js-form-item"', $cleaned_attributes->getClass()->render());
  }

}

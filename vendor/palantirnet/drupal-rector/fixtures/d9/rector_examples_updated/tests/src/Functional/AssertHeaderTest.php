<?php

namespace Drupal\Tests\rector_examples\Functional;

use Drupal\Tests\BrowserTestBase;

class AssertHeaderTest extends BrowserTestBase {

    public function testExample() {
        $this->assertSession()->responseHeaderEquals('Foo', 'Bar');
    }

}

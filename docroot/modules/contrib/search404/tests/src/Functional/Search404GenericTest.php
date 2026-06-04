<?php

namespace Drupal\Tests\search404\Functional;

use Drupal\Tests\system\Functional\Module\GenericModuleTestBase;

/**
 * Generic module test for search404.
 *
 * @group search404
 */
class Search404GenericTest extends GenericModuleTestBase {

  /**
   * {@inheritDoc}
   */
  protected function assertHookHelp(string $module): void {
    // Don't do anything here. Just overwrite this useless method, so we do
    // don't have to implement hook_help().
  }

}

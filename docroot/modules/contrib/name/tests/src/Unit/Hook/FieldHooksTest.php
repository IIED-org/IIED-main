<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Hook;

use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Hook\FieldHooks;

/**
 * @coversDefaultClass \Drupal\name\Hook\FieldHooks
 *
 * @group name
 */
final class FieldHooksTest extends UnitTestCase {

  /**
   * @covers ::fieldTypeCategoryInfoAlter
   */
  public function testFieldTypeCategoryInfoAlterAppendsLibrary(): void {
    $hooks = new FieldHooks();
    $fallback = FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY;
    $definitions = [
      $fallback => [
        'libraries' => ['existing/library'],
      ],
    ];

    $hooks->fieldTypeCategoryInfoAlter($definitions);

    $this->assertSame(
      ['existing/library', 'name/field_ui'],
      $definitions[$fallback]['libraries'],
    );
  }

  /**
   * @covers ::fieldTypeCategoryInfoAlter
   */
  public function testFieldTypeCategoryInfoAlterCreatesLibrariesEntry(): void {
    $hooks = new FieldHooks();
    $fallback = FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY;
    $definitions = [
      $fallback => [],
    ];

    $hooks->fieldTypeCategoryInfoAlter($definitions);

    $this->assertSame(
      ['name/field_ui'],
      $definitions[$fallback]['libraries'],
    );
  }

  /**
   * @covers ::fieldTypeCategoryInfoAlter
   */
  public function testFieldTypeCategoryInfoAlterLeavesOtherCategoriesUntouched(): void {
    $hooks = new FieldHooks();
    $fallback = FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY;
    $definitions = [
      'other' => [
        'libraries' => ['other/library'],
      ],
      $fallback => [
        'libraries' => [],
      ],
    ];

    $hooks->fieldTypeCategoryInfoAlter($definitions);

    $this->assertSame(['other/library'], $definitions['other']['libraries']);
    $this->assertContains('name/field_ui', $definitions[$fallback]['libraries']);
  }

}

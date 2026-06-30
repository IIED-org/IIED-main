<?php

declare(strict_types=1);

namespace Drupal\Tests\components\Kernel;

use Drupal\components\Template\ComponentsDebugNodeVisitor;
use Drupal\components\Template\TwigExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the TwigExtension.
 *
 * @coversDefaultClass \Drupal\components\Template\TwigExtension
 * @group components
 */
#[
  Group('components'), /* @phpstan-ignore attribute.notFound */
  CoversClass(TwigExtension::class), /* @phpstan-ignore attribute.notFound */
  CoversClass(ComponentsDebugNodeVisitor::class) /* @phpstan-ignore attribute.notFound */
]
class TwigExtensionTest extends ComponentsKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'components',
    'components_twig_extension_test',
  ];

  /**
   * Ensures the Twig template() function works inside a Drupal instance.
   *
   * @throws \Exception
   */
  public function testTemplateFunction() {
    try {
      $element = [
        '#theme' => 'components_twig_extension_test_template_function',
        '#items' => [
          'first item',
          'second item',
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }
    $this->assertStringContainsString('<ul><li>first item</li><li>second item</li></ul>', $result);
  }

  /**
   * Ensures the Twig "recursive_merge" filter works inside a Drupal instance.
   *
   * @dataProvider providerTestRecursiveMergeFilter
   */
  #[DataProvider('providerTestRecursiveMergeFilter') /* @phpstan-ignore attribute.notFound */]
  public function testRecursiveMergeFilter(string $theme_hook, string $expected) {
    try {
      $element = [
        '#theme' => $theme_hook,
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'the_element_id',
                'class' => ['original-container-class'],
              ],
            ],
          ],
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }
    $this->assertStringContainsString($expected, $result);
  }

  /**
   * Data provider for ::testRecursiveMergeFilter().
   *
   * @see testRecursiveMergeFilter()
   */
  public static function providerTestRecursiveMergeFilter(): array {
    return [
      'Uses positional arguments' => [
        'theme_hook' => 'components_twig_extension_test_recursive_merge_filter',
        'expected' => '<div id="the_element_id" class="new-class"></div>',
      ],
      'Uses named arguments' => [
        'theme_hook' => 'components_twig_extension_test_recursive_merge_filter_named_arguments',
        'expected' => '<div id="the_element_id" class="new-class"></div>',
      ],
    ];
  }

  /**
   * Ensures the Twig "set" filter works inside a Drupal instance.
   *
   * @dataProvider providerTestSetFilter
   */
  #[DataProvider('providerTestSetFilter') /* @phpstan-ignore attribute.notFound */]
  public function testSetFilter(string $theme_hook, string $expected) {
    try {
      $element = [
        '#theme' => $theme_hook,
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            [
              '#type' => 'container',
              '#attributes' => [
                'id' => 'the_element_id',
                'class' => ['original-container-class'],
              ],
            ],
          ],
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }
    $this->assertStringContainsString($expected, $result);
  }

  /**
   * Data provider for ::testSetFilter().
   *
   * @see testSetFilter()
   */
  public static function providerTestSetFilter(): array {
    return [
      'Uses positional arguments' => [
        'theme_hook' => 'components_twig_extension_test_set_filter',
        'expected' => '<div class="new-class"></div>',
      ],
      'Uses named arguments' => [
        'theme_hook' => 'components_twig_extension_test_set_filter_named_arguments',
        'expected' => '<div class="new-class"></div>',
      ],
    ];
  }

  /**
   * Ensures the Twig "add" filter works inside a Drupal instance.
   *
   * @dataProvider providerTestAddFilter
   */
  #[DataProvider('providerTestAddFilter') /* @phpstan-ignore attribute.notFound */]
  public function testAddFilter(string $theme_hook, string $expected) {
    try {
      $element = [
        '#theme' => $theme_hook,
        'list' => [
          '#theme' => 'item_list',
          '#items' => [
            [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['original-container-class'],
              ],
            ],
          ],
        ],
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }
    $this->assertStringContainsString($expected, $result);
  }

  /**
   * Data provider for ::testAddFilter().
   *
   * @see testAddFilter()
   */
  public static function providerTestAddFilter(): array {
    return [
      'Uses positional arguments' => [
        'theme_hook' => 'components_twig_extension_test_add_filter',
        'expected' => '<div class="original-container-class new-class"></div>',
      ],
      'Uses named arguments' => [
        'theme_hook' => 'components_twig_extension_test_add_filter_named_arguments',
        'expected' => '<div class="original-container-class new-class"></div>',
      ],
      'Uses "values" named argument' => [
        'theme_hook' => 'components_twig_extension_test_add_filter_plural_named_arguments',
        'expected' => '<div class="original-container-class new-class-1 new-class-2"></div>',
      ],
    ];
  }

  /**
   * Ensures the Twig debug comments works inside a Drupal instance.
   */
  public function testComponentsDebugNodeVisitor() {
    try {
      $element = [
        '#theme' => 'components_twig_extension_test_debug_comments',
      ];
      $result = $this->render($element);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }

    foreach ([
      'This is the template for the components_twig_extension_test_debug_comments hook.',
      'This is the components-twig-extension-debug.twig file.',
      "<!-- THEME DEBUG -->\n"
      . "<!-- COMPONENT: @components_twig_extension_test_ns/components-twig-extension-debug.twig -->\n"
      . "<!-- 💡 BEGIN ⚙️ COMPONENT TEMPLATE OUTPUT from '",
      "tests/modules/components_twig_extension_test/components/components-twig-extension-debug.twig' -->\n<p>\n",
      "<!-- END ⚙️ COMPONENT TEMPLATE OUTPUT from '",
      "tests/modules/components_twig_extension_test/components/components-twig-extension-debug.twig' -->\n\n",
    ] as $foundString) {
      $this->assertStringContainsString($foundString, $result);
    }
  }

}

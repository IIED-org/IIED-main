<?php

declare(strict_types=1);

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\TwigExtension;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Twig\Extension\CoreExtension;

/**
 * Tests the TwigExtension's filters.
 *
 * @coversDefaultClass \Drupal\components\Template\TwigExtension
 * @group components
 */
#[
  Group('components'), /* @phpstan-ignore attribute.notFound */
  CoversClass(TwigExtension::class) /* @phpstan-ignore attribute.notFound */
]
class TwigExtensionFiltersTest extends UnitTestCase {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The system under test.
   *
   * @var \Drupal\components\Template\TwigExtension
   */
  protected TwigExtension $systemUnderTest;

  /**
   * The Twig CoreExtension.
   *
   * @var \Twig\Extension\CoreExtension
   */
  protected CoreExtension $coreExtension;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $this->systemUnderTest = new TwigExtension($this->createMock('Drupal\components\Template\ComponentsRegistry'));

    // Load the Twig CoreExtension as its file contains static functions used by
    // TwigExtension.
    $this->coreExtension = new CoreExtension();
  }

  /**
   * Tests exceptions during recursive_merge filter.
   */
  public function testRecursiveMergeFilterException() {
    try {
      TwigExtension::recursiveMergeFilter('not-an-array', ['key' => 'value']);
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('The recursive_merge filter only works on arrays or "Traversable" objects, got "string".', $e->getMessage());
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests the recursive_merge filter.
   *
   * @param array $element
   *   The element to alter.
   * @param array $value
   *   The value to set.
   * @param array $expected
   *   The expected result.
   *
   * @dataProvider providerTestRecursiveMergeFilter
   */
  #[DataProvider('providerTestRecursiveMergeFilter') /* @phpstan-ignore attribute.notFound */]
  public function testRecursiveMergeFilter(array $element, array $value, array $expected) {
    try {
      $result = TwigExtension::recursiveMergeFilter($element, $value);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }

    $this->assertEquals($expected, $result);
    $this->assertEquals(array_replace_recursive($element, $value), $result);
  }

  /**
   * Data provider for ::testRecursiveMergeFilter().
   *
   * @see testRecursiveMergeFilter()
   */
  public static function providerTestRecursiveMergeFilter(): array {
    return [
      'Recursively sets values' => [
        'element' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
        ],
        'value' => [
          'extra' => 'extra-value',
          'element' => [
            '#attributes' => [
              'class' => ['new-value'],
              'placeholder' => 'Label',
            ],
          ],
        ],
        'expected' => [
          'existing' => 'value',
          'extra' => 'extra-value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['new-value', 'old-value-2'],
              'id' => 'element',
              'placeholder' => 'Label',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests exceptions during set filter.
   */
  public function testSetFilterException() {
    try {
      TwigExtension::setFilter('not-an-array', 'key', 'value');
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('The "set" filter only works on arrays or "Traversable" objects, got "string".', $e->getMessage());
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests the set filter.
   *
   * @param array $element
   *   The element to alter.
   * @param string $at
   *   The dotted-path to the deeply nested element to set.
   * @param mixed $value
   *   The value to set.
   * @param array $expected
   *   The expected result.
   *
   * @dataProvider providerTestSetFilter
   */
  #[DataProvider('providerTestSetFilter') /* @phpstan-ignore attribute.notFound */]
  public function testSetFilter(array $element, string $at, mixed $value, array $expected) {
    try {
      $result = TwigExtension::setFilter($element, $at, $value);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }

    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for ::testSetFilter().
   *
   * @see testSetFilter()
   */
  public static function providerTestSetFilter(): array {
    return [
      'Sets a new value' => [
        'element' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
        ],
        'at' => 'element.#attributes.placeholder',
        'value' => 'Label',
        'expected' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
              'placeholder' => 'Label',
            ],
          ],
        ],
      ],
      'Replaces a targeted array' => [
        'element' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
        ],
        'at' => 'element.#attributes.class',
        'value' => ['new-value'],
        'expected' => [
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['new-value'],
              'id' => 'element',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Tests exceptions during add filter.
   */
  public function testAddFilterException() {
    try {
      TwigExtension::addFilter('not-an-array', 'key', 'value');
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('The "add" filter only works on arrays or "Traversable" objects, got "string".', $e->getMessage());
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests the add filter.
   *
   * @param string $at
   *   The dotted-path to the deeply nested element to add.
   * @param mixed $value
   *   The value(s) to add.
   * @param array $expected
   *   The expected render array.
   *
   * @dataProvider providerTestAddFilter
   */
  #[DataProvider('providerTestAddFilter') /* @phpstan-ignore attribute.notFound */]
  public function testAddFilter(string $at, mixed $value, array $expected) {
    $element = [
      'existing' => 'value',
      'element' => [
        '#type' => 'element',
        '#attributes' => [
          'class' => ['old-value-1', 'old-value-2'],
          'id' => 'element',
        ],
      ],
    ];

    $result = NULL;
    try {
      $result = TwigExtension::addFilter($element, $at, $value);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }
    $this->assertEquals($expected, $result, 'Failed to replace a value.');
  }

  /**
   * Data provider for ::testAddFilter().
   *
   * @see testAddFilter()
   */
  public static function providerTestAddFilter(): array {
    return [
      'replacing a value' => [
        'at' => 'element.#attributes.id',
        'value' => 'new-value',
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'new-value',
            ],
          ],
        ],
      ],
      'setting a new property on an existing array' => [
        'at' => 'element.#attributes.placeholder',
        'value' => 'new-value',
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
              'placeholder' => 'new-value',
            ],
          ],
        ],
      ],
      'targeting an existing array with a string' => [
        'at' => 'element.#attributes.class',
        'value' => 'new-value',
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2', 'new-value'],
              'id' => 'element',
            ],
          ],
        ],
      ],
      'targeting an existing array with an array' => [
        'at' => 'element.#attributes.class',
        'value' => ['new-value-1', 'new-value-2'],
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => [
                'old-value-1',
                'old-value-2',
                'new-value-1',
                'new-value-2',
              ],
              'id' => 'element',
            ],
          ],
        ],
      ],
      'targeting a non-existent parent property' => [
        'at' => 'new-element.#attributes.class',
        'value' => ['new-value'],
        'expected' => [
          'existing' => 'value',
          'element' => [
            '#type' => 'element',
            '#attributes' => [
              'class' => ['old-value-1', 'old-value-2'],
              'id' => 'element',
            ],
          ],
          'new-element' => ['#attributes' => ['class' => ['new-value']]],
        ],
      ],
    ];
  }

}

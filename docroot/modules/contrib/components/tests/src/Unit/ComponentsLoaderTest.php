<?php

declare(strict_types=1);

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\ComponentsRegistry;
use Drupal\components\Template\Loader\ComponentsLoader;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Tests the ComponentsLoader.
 *
 * @coversDefaultClass \Drupal\components\Template\Loader\ComponentsLoader
 * @group components
 */
#[
  Group('components'), /* @phpstan-ignore attribute.notFound */
  UsesClass(ComponentsRegistry::class), /* @phpstan-ignore attribute.notFound */
  CoversClass(ComponentsLoader::class) /* @phpstan-ignore attribute.notFound */
]
class ComponentsLoaderTest extends UnitTestCase {

  /**
   * The components registry service.
   *
   * @var \Drupal\components\Template\ComponentsRegistry
   */
  protected ComponentsRegistry $componentsRegistry;

  /**
   * The system under test.
   *
   * @var \Drupal\components\Template\Loader\ComponentsLoader
   */
  protected ComponentsLoader $systemUnderTest;

  /**
   * Invokes a protected or private method of an object.
   *
   * @param object|null $obj
   *   The instantiated object (or null for static methods.)
   * @param string $method
   *   The method to invoke.
   * @param mixed $args
   *   The parameters to be passed to the method.
   *
   * @return mixed
   *   The return value of the method.
   *
   * @throws \ReflectionException
   */
  public function invokeProtectedMethod(?object $obj, string $method, ...$args): mixed {
    // Use reflection to test a protected method.
    $methodUnderTest = new \ReflectionMethod($obj, $method);
    $methodUnderTest->setAccessible(TRUE);
    return $methodUnderTest->invokeArgs($obj, $args);
  }

  /**
   * Tests finding a template.
   *
   * @dataProvider providerTestFindTemplate
   */
  #[DataProvider('providerTestFindTemplate') /* @phpstan-ignore attribute.notFound */]
  public function testFindTemplate(string $name, bool $throw, ?string $getTemplate, ?string $expected, ?string $exception = NULL) {
    // Mock services.
    $componentsRegistry = $this->createMock('\Drupal\components\Template\ComponentsRegistry');
    $componentsRegistry
      ->method('getTemplate')
      ->willReturn($getTemplate);

    $this->systemUnderTest = new ComponentsLoader($componentsRegistry);

    try {
      // Use reflection to test protected methods and properties.
      $result = $this->invokeProtectedMethod($this->systemUnderTest, 'findTemplate', $name, $throw);

      if ($exception) {
        $this->fail('No Exception thrown, but "' . $exception . '" expected.');
      }
      else {
        $this->assertEquals($expected, $result);
      }
    }
    catch (\Exception $e) {
      if ($exception) {
        $this->assertEquals($exception, $e->getMessage());
      }
      else {
        $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
      }
    }
  }

  /**
   * Provides test data to ::testFindTemplate().
   *
   * @see testFindTemplate()
   */
  public static function providerTestFindTemplate(): array {
    return [
      'returns path when template is found' => [
        'name' => '@ns/template.twig',
        'throw' => TRUE,
        'getTemplate' => 'themes/contrib/example/ns/template.twig',
        'expected' => 'themes/contrib/example/ns/template.twig',
        'exception' => NULL,
      ],
      'returns NULL when template not found' => [
        'name' => '@ns/template.twig',
        'throw' => FALSE,
        'getTemplate' => NULL,
        'expected' => NULL,
        'exception' => NULL,
      ],
      'exception when template not found and $throw = TRUE' => [
        'name' => '@ns/template.twig',
        'throw' => TRUE,
        'getTemplate' => NULL,
        'expected' => NULL,
        'exception' => 'Unable to find template "@ns/template.twig" in the components registry.',
      ],
      'exception when invalid template name and $throw = TRUE' => [
        'name' => '@ns/template.txt',
        'throw' => TRUE,
        'getTemplate' => NULL,
        'expected' => NULL,
        'exception' => 'Malformed namespaced template name "@ns/template.txt" (expecting "@namespace/template_name.twig").',
      ],
    ];
  }

  /**
   * Tests checking if a template exists.
   *
   * @dataProvider providerTestExists
   */
  #[DataProvider('providerTestExists') /* @phpstan-ignore attribute.notFound */]
  public function testExists(string $template, ?string $getTemplate, bool $expected) {
    // Mock services.
    $componentsRegistry = $this->createMock('\Drupal\components\Template\ComponentsRegistry');
    $componentsRegistry
      ->method('getTemplate')
      ->willReturn($getTemplate);

    $this->systemUnderTest = new ComponentsLoader($componentsRegistry);

    $result = $this->systemUnderTest->exists($template);
    $this->assertEquals($expected, $result);
  }

  /**
   * Provides test data to ::testExists().
   *
   * @see testExists()
   */
  public static function providerTestExists(): array {
    return [
      'confirms a template does exist' => [
        'template' => '@ns/example-exists.twig',
        'getTemplate' => 'themes/contrib/example/ns/example-exists.twig',
        'expected' => TRUE,
      ],
      'confirms a template does not exists' => [
        'template' => '@ns/example-does-not-exist.twig',
        'getTemplate' => NULL,
        'expected' => FALSE,
      ],
    ];
  }

}

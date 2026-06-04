<?php

declare(strict_types=1);

namespace Drupal\Tests\components\Unit;

use Drupal\components\Template\TwigExtension;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Loader\StringLoader;
use Drupal\Core\Template\TwigExtension as CoreTwigExtension;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Twig\Environment;

/**
 * Tests the TwigExtension.
 *
 * @coversDefaultClass \Drupal\components\Template\TwigExtension
 * @group components
 */
#[
  Group('components'), /* @phpstan-ignore attribute.notFound */
  CoversClass('\Drupal\components\Template\TwigExtension') /* @phpstan-ignore attribute.notFound */
]
class TwigExtensionFunctionsTest extends UnitTestCase {

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
   * The Twig environment.
   *
   * @var \Twig\Environment
   */
  protected Environment $twigEnvironment;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->renderer = $this->createMock('\Drupal\Core\Render\RendererInterface');
    $urlGenerator = $this->createMock('\Drupal\Core\Routing\UrlGeneratorInterface');
    $themeManager = $this->createMock('\Drupal\Core\Theme\ThemeManagerInterface');
    $dateFormatter = $this->createMock('\Drupal\Core\Datetime\DateFormatterInterface');
    $fileURLGenerator = $this->createMock('\Drupal\Core\File\FileUrlGeneratorInterface');

    $this->systemUnderTest = new TwigExtension($this->createMock('Drupal\components\Template\ComponentsRegistry'));
    $coreTwigExtension = new CoreTwigExtension($this->renderer, $urlGenerator, $themeManager, $dateFormatter, $fileURLGenerator);

    $loader = new StringLoader();
    $this->twigEnvironment = new Environment($loader);
    $this->twigEnvironment->setExtensions([
      $coreTwigExtension,
      $this->systemUnderTest,
    ]);
  }

  /**
   * Tests incorrectly using a Twig namespaced template name.
   */
  public function testTemplateNamespaceException() {
    $this->renderer->expects($this->exactly(0))
      ->method('render');

    try {
      $this->twigEnvironment->render(
        '{{ template("@stable/item-list.html.twig", items = [ link ] ) }}',
        ['link' => '']
      );
      $exception = FALSE;
    }
    catch (\Exception $e) {
      $this->assertStringContainsString('Templates with namespaces are not supported; "@stable/item-list.html.twig" given.', $e->getMessage());
      $exception = TRUE;
    }
    if (!$exception) {
      $this->fail('Expected Exception, none was thrown.');
    }
  }

  /**
   * Tests creating #theme render arrays within a Twig template.
   *
   * @param string $template
   *   The inline template to render.
   * @param array $variables
   *   An array of variables to provide to the template.
   * @param array $expected
   *   The render array expected to be returned.
   * @param string $rendered_output
   *   The HTML output from the rendered $expected array.
   *
   * @dataProvider providerTestTemplate
   */
  #[DataProvider('providerTestTemplate') /* @phpstan-ignore attribute.notFound */]
  public function testTemplate(
    string $template,
    array $variables,
    array $expected,
    string $rendered_output,
  ) {
    $this->renderer
      ->expects($this->exactly(1))
      ->method('render')
      ->with($expected)
      ->willReturn($rendered_output);

    try {
      $result = $this->twigEnvironment->render($template, $variables);
    }
    catch (\Exception $e) {
      $this->fail('No Exception expected but the following was thrown: "' . $e->getMessage() . '"');
    }
    $this->assertEquals($rendered_output, $result);
  }

  /**
   * Data provider for ::testTemplate().
   *
   * @see testTemplate()
   */
  public static function providerTestTemplate(): array {
    $link = [
      '#type' => 'link',
      '#title' => 'example link',
      '#url' => 'https://example.com',
    ];

    return [
      'Works with template name' => [
        'template' => '{{ template("item-list.html.twig", items = [ link ] ) }}',
        'variables' => ['link' => $link],
        'expected' => [
          '#theme' => 'item_list',
          '#items' => [$link],
          '#printed' => FALSE,
        ],
        'rendered_output' => '<ul><li><a href="https://example.com">example link</a></li></ul>',
      ],
      'Works with theme hook' => [
        'template' => '{{ template("item_list", items = [ link ] ) }}',
        'variables' => ['link' => $link],
        'expected' => [
          '#theme' => 'item_list',
          '#items' => [$link],
          '#printed' => FALSE,
        ],
        'rendered_output' => '<ul><li><a href="https://example.com">example link</a></li></ul>',
      ],
      'Works with an array of theme hooks' => [
        'template' => '{{ template([ "item_list__dogs", "item_list__cats" ], items = [ link ] ) }}',
        'variables' => ['link' => $link],
        'expected' => [
          '#theme' => ['item_list__dogs', 'item_list__cats'],
          '#items' => [$link],
          '#printed' => FALSE,
        ],
        'rendered_output' => '<ul><li><a href="https://example.com">example link</a></li></ul>',
      ],
    ];
  }

}

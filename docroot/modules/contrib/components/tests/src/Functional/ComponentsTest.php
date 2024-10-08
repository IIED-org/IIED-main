<?php

namespace Drupal\Tests\components\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the components module in a fully loaded Drupal instance.
 *
 * @group components
 */
class ComponentsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'components',
    'components_test',
  ];

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'components_test_theme';

  /**
   * Renders a render array.
   *
   * @param array $elements
   *   The elements to render.
   *
   * @return string
   *   The rendered string output (typically HTML).
   */
  protected function render(array &$elements): string {
    return $this->container->get('renderer')->renderRoot($elements);
  }

  /**
   * Ensures component templates can be loaded inside a Drupal instance.
   */
  public function testLoadTemplate() {
    $element = [
      // The templates/components-test.html.twig file determines which
      // templates are loaded.
      '#theme' => 'components_test',
    ];
    $result = $this->render($element);

    // The following templates are in paths defined in .info namespace
    // definitions.
    foreach ([
      'This is the "@components_test/components-test.twig" template from the components_test module.',
      'This is the "@components/components-test-active-theme.twig" template from the components_test_theme theme.',
      'This is the "@components/components-test-base-theme.twig" template from the components_test_base_theme theme.',
      'This is the "@components/components-test-module.twig" template from the components_test module.',
      'This is the "@components/nested/components-test-nested.twig" template from the components_test_theme theme.',
      'This is the "@components/nested1/components-test-conflicting-file-name.twig" template from the components_test_theme theme.',
      'This is the "@components/nested2/components-test-conflicting-file-name.twig" template from the components_test_theme theme.',
    ] as $foundString) {
      $this->assertStringContainsString($foundString, $result);
    }
    // The following templates are in paths defined in .info namespace
    // definitions, but are overridden by the templates above.
    foreach ([
      'This is the "@components/components-test-active-theme.twig" template from the components_test_base_theme theme.',
      'This is the "@components/components-test-active-theme.twig" template from the components_test module.',
      'This is the "@components/components-test-base-theme.twig" template from the components_test module.',
      'This is the "@components/nested3/components-test-conflicting-file-name.twig" template from the components_test_theme theme.',
    ] as $notFoundString) {
      $this->assertStringNotContainsString($notFoundString, $result);
    }

    // This template is found using hook_components_namespaces_alter().
    $this->assertStringContainsString('This is the "@components/components-test-namespaces-alter.twig" template from the components_test module.', $result);

    // This template is found using hook_protected_twig_namespaces_alter().
    $this->assertStringContainsString('This is the "@system/components-test-protected-twig-namespaces-alter.twig" template from the components_test module.', $result);
  }

}

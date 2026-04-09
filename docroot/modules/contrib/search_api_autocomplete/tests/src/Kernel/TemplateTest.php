<?php

namespace Drupal\Tests\search_api_autocomplete\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests correctness of our Twig templates.
 *
 * @group search_api_autocomplete
 */
class TemplateTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search_api_autocomplete',
  ];

  /**
   * Tests that the suggestion template works correctly.
   *
   * @see search-api-autocomplete-suggestion.html.twig
   */
  public function testSuggestionTemplate(): void {
    $build = [
      '#theme' => 'search_api_autocomplete_suggestion',
      '#label' => 'Label',
      '#note' => '(Note & advice)',
      '#results_count' => 10,
      '#suggestion_prefix' => 'foo',
      '#suggestion_suffix' => 'baz',
      '#user_input' => 'bar',
    ];
    $text = \Drupal::getContainer()->get('renderer')
      ->renderInIsolation($build);

    $this->assertStringContainsString(' <span class="autocomplete-suggestion-note">(Note &amp; advice)</span>', $text);
    $this->assertStringContainsString(' <span class="autocomplete-suggestion-label">Label</span>', $text);
    $expected = '<span class="autocomplete-suggestion-suggestion-prefix">foo</span>'
      . '<span class="autocomplete-suggestion-user-input">bar</span>'
      . '<span class="autocomplete-suggestion-suggestion-suffix">baz</span>';
    $this->assertStringContainsString($expected, $text);
    $this->assertStringContainsString(' <span class="autocomplete-suggestion-results-count">10</span>', $text);
    $this->assertStringNotContainsString('{', $text);
    $this->assertStringNotContainsString('}', $text);
  }

}

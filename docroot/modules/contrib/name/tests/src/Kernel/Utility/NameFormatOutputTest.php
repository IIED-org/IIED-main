<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Utility;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Utility\NameFormatOutput;

/**
 * Kernel tests for NameFormatOutput.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatOutput
 *
 * @group name
 */
class NameFormatOutputTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['name']);
  }

  /**
   * Confirms FormattableMarkup modes behave correctly under full bootstrap.
   *
   * @covers ::wrap
   */
  public function testWrapFormattableMarkupModesInKernel(): void {
    $cases = [
      'simple'    => '<span class="given">John</span>',
      'rdfa'      => '<span property="schema:givenName">John</span>',
      'microdata' => '<span itemprop="givenName">John</span>',
    ];

    foreach ($cases as $markup => $input) {
      $expected = (new FormattableMarkup($input, []))->jsonSerialize();
      $this->assertSame(
        $expected,
        NameFormatOutput::wrap($input, $markup),
        "wrap() with markup='{$markup}' must equal FormattableMarkup->jsonSerialize()."
      );
    }
  }

  /**
   * HTML in a simple-mode result is preserved when rendered.
   *
   * Builds a render array using the wrap() output as #markup and asserts
   * that the renderer outputs the original tags, not escaped versions.
   *
   * @covers ::wrap
   */
  public function testWrapSimpleMarkupRendersWithoutDoubleEscaping(): void {
    $html = '<span class="given">Jane</span>';
    $out  = NameFormatOutput::wrap($html, 'simple');

    $build    = ['#markup' => $out];
    $rendered = (string) $this->container
      ->get('renderer')
      ->renderInIsolation($build);

    $this->assertStringContainsString('<span class="given">Jane</span>', $rendered);
    $this->assertStringNotContainsString('&lt;span', $rendered);
  }

}

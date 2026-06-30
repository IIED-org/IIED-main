<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Utility;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameFormatOutput;

/**
 * Unit tests for NameFormatOutput.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatOutput
 *
 * @group name
 */
class NameFormatOutputTest extends UnitTestCase {

  /**
   * Provides HTML strings paired with each FormattableMarkup markup mode.
   *
   * @return array[]
   *   Each case: [markup mode, input HTML string].
   */
  public static function formattableMarkupModesProvider(): array {
    return [
      'simple mode' => [
        'simple',
        '<span class="given">John</span> <span class="family">Doe</span>',
      ],
      'rdfa mode' => [
        'rdfa',
        '<span property="schema:givenName">John</span>'
        . ' <span property="schema:familyName">Doe</span>',
      ],
      'microdata mode' => [
        'microdata',
        '<span itemprop="givenName">John</span>'
        . ' <span itemprop="familyName">Doe</span>',
      ],
    ];
  }

  /**
   * FormattableMarkup modes return the same value as FormattableMarkup itself.
   *
   * @covers ::wrap
   *
   * @dataProvider formattableMarkupModesProvider
   */
  public function testWrapFormattableMarkupModes(
    string $markup,
    string $input,
  ): void {
    $expected = (new FormattableMarkup($input, []))->jsonSerialize();
    $this->assertSame($expected, NameFormatOutput::wrap($input, $markup));
  }

  /**
   * FormattableMarkup modes preserve pre-escaped HTML without re-escaping.
   *
   * Verifies that line 41 (the simple/rdfa/microdata branch) passes the
   * string through without double-encoding, while the 'none' branch
   * (HtmlEscapedText) would escape the same tags.
   *
   * @covers ::wrap
   */
  public function testWrapFormattableMarkupPreservesHtml(): void {
    $html = '<span class="given">A&amp;B</span>';

    foreach (['simple', 'rdfa', 'microdata'] as $markup) {
      $out = NameFormatOutput::wrap($html, $markup);
      $this->assertStringContainsString(
        '<span class="given">',
        (string) $out,
        "Markup mode '{$markup}' must preserve HTML tags."
      );
      $this->assertStringContainsString(
        'A&amp;B',
        (string) $out,
        "Markup mode '{$markup}' must not double-encode entities."
      );
    }

    // The 'none' branch escapes the same input — proving line 41 differs.
    $plain = (string) NameFormatOutput::wrap($html, 'none');
    $this->assertStringNotContainsString('<span', $plain);
    $this->assertStringContainsString('&lt;span', $plain);
  }

}

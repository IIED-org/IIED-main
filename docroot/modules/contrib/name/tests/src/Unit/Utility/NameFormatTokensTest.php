<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Utility;

use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameFormatTokens;

/**
 * Unit tests for NameFormatTokens.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatTokens
 *
 * @group name
 */
class NameFormatTokensTest extends UnitTestCase {

  /**
   * @covers ::resolveValue
   */
  public function testResolveValueUsesStringTokensAndLiteralFallback(): void {
    $tokens = [
      'g' => 'John',
      'd' => NULL,
    ];

    $this->assertSame('John', NameFormatTokens::resolveValue('g', $tokens));
    $this->assertSame('', NameFormatTokens::resolveValue('d', $tokens));
    $this->assertSame('x', NameFormatTokens::resolveValue('x', $tokens));
  }

  /**
   * @covers ::build
   */
  public function testBuildReturnsExpectedTokenKeys(): void {
    $tokens = NameFormatTokens::build(
      ['given' => 'John', 'family' => 'Doe'],
      ' ',
      ', ',
      '',
      'none',
    );

    $expected_keys = [
      't', 'g', 'p', 'q', 'm', 'f', 'c', 'a', 's',
      'v', 'w', 'x', 'y', 'z', 'A', 'I', 'J', 'K', 'M',
      'i', 'j', 'k', 'd', 'D', 'e', 'E',
    ];
    foreach ($expected_keys as $key) {
      $this->assertArrayHasKey($key, $tokens);
    }
    $this->assertSame('John', $tokens['g']);
    $this->assertSame('Doe', $tokens['f']);
    $this->assertSame(' ', $tokens['i']);
  }

  /**
   * Simple markup wraps value in a span with the component class.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   * @covers ::wrapSimpleSpan
   */
  public function testRenderComponentSimpleMarkupWrapsClassSpan(): void {
    $result = NameFormatTokens::renderComponent('John', 'given', 'simple');
    $this->assertSame('<span class="given">John</span>', $result);
  }

  /**
   * Microdata markup adds itemprop for a schema-mapped component key.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   * @covers ::wrapMicrodataSpan
   * @covers ::schemaAttribute
   */
  public function testRenderComponentMicrodataAddsItempropForMappedKey(): void {
    $result = NameFormatTokens::renderComponent('John', 'given', 'microdata');
    $this->assertSame(
      '<span class="given" itemprop="givenName">John</span>',
      $result,
    );
  }

  /**
   * Microdata markup omits itemprop for an unmapped component key.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   * @covers ::wrapMicrodataSpan
   * @covers ::schemaAttribute
   */
  public function testRenderComponentMicrodataOmitsItempropForUnmappedKey(): void {
    $result = NameFormatTokens::renderComponent('Jr', 'generational', 'microdata');
    $this->assertSame('<span class="generational">Jr</span>', $result);
    $this->assertStringNotContainsString('itemprop=', $result);
  }

  /**
   * RDFa markup adds schema property for a schema-mapped component key.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   * @covers ::wrapRdfaSpan
   * @covers ::schemaAttribute
   */
  public function testRenderComponentRdfaAddsSchemaPropertyForMappedKey(): void {
    $result = NameFormatTokens::renderComponent('John', 'given', 'rdfa');
    $this->assertSame(
      '<span class="given" property="schema:givenName">John</span>',
      $result,
    );
  }

  /**
   * RDFa markup omits schema property for an unmapped component key.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   * @covers ::wrapRdfaSpan
   * @covers ::schemaAttribute
   */
  public function testRenderComponentRdfaOmitsPropertyForUnmappedKey(): void {
    $result = NameFormatTokens::renderComponent('Jr', 'generational', 'rdfa');
    $this->assertSame('<span class="generational">Jr</span>', $result);
    $this->assertStringNotContainsString('property="schema:', $result);
  }

  /**
   * All markup modes HTML-escape special characters in the value.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   * @covers ::wrapSimpleSpan
   * @covers ::wrapMicrodataSpan
   * @covers ::wrapRdfaSpan
   */
  public function testRenderComponentEscapesHtmlInMarkupModes(): void {
    foreach (['simple', 'microdata', 'rdfa'] as $markup) {
      $result = NameFormatTokens::renderComponent('A&B', 'given', $markup);
      $this->assertStringContainsString(
        'A&amp;B',
        $result,
        "Markup mode '{$markup}' must HTML-escape the value.",
      );
      $this->assertStringNotContainsString(
        'A&B',
        $result,
        "Markup mode '{$markup}' must not emit unescaped ampersand.",
      );
    }
  }

  /**
   * None markup returns the original value without wrappers.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   */
  public function testRenderComponentNoneMarkupReturnsPlainValue(): void {
    $result = NameFormatTokens::renderComponent('John', 'given', 'none');
    $this->assertSame('John', $result);
  }

  /**
   * RenderFirstComponent() returns NULL when all candidates are empty.
   *
   * @covers ::renderFirstComponent
   */
  public function testRenderFirstComponentReturnsNullWhenAllCandidatesEmpty(): void {
    $result = NameFormatTokens::renderFirstComponent(
      ['', NULL, ''],
      'given',
      'simple',
    );
    $this->assertNull($result);
  }

  /**
   * RenderFirstComponent() returns the first non-empty rendered value.
   *
   * @covers ::renderFirstComponent
   */
  public function testRenderFirstComponentReturnsFirstNonEmptyRenderedValue(): void {
    $result = NameFormatTokens::renderFirstComponent(
      ['', 'John'],
      'given',
      'simple',
    );
    $this->assertSame(
      NameFormatTokens::renderComponent('John', 'given', 'simple'),
      $result,
    );
  }

}

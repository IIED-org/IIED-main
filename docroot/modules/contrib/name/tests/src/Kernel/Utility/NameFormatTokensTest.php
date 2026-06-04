<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Utility;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Utility\NameFormatTokens;

/**
 * Kernel tests for NameFormatTokens.
 *
 * Verifies markup-mode output under a full Drupal bootstrap so that
 * Html::escape() and any autoloaded dependencies behave identically to
 * production.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatTokens
 *
 * @group name
 */
class NameFormatTokensTest extends KernelTestBase {

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
   * Simple markup wraps value in a span with the component class.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   * @covers ::wrapSimpleSpan
   */
  public function testRenderComponentSimpleMarkupWrapsClassSpan(): void {
    $result = NameFormatTokens::renderComponent('Jane', 'given', 'simple');
    $this->assertSame('<span class="given">Jane</span>', $result);
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
    $result = NameFormatTokens::renderComponent('Jane', 'given', 'microdata');
    $this->assertSame(
      '<span class="given" itemprop="givenName">Jane</span>',
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
    $result = NameFormatTokens::renderComponent('Jane', 'given', 'rdfa');
    $this->assertSame(
      '<span class="given" property="schema:givenName">Jane</span>',
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
   * None markup returns the original value without wrappers.
   *
   * @covers ::renderComponent
   * @covers ::formatWithMarkup
   */
  public function testRenderComponentNoneMarkupReturnsPlainValue(): void {
    $result = NameFormatTokens::renderComponent('Jane', 'given', 'none');
    $this->assertSame('Jane', $result);
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
   * Build() with simple markup produces wrapped HTML in token values.
   *
   * @covers ::build
   * @covers ::renderComponent
   */
  public function testBuildWithSimpleMarkupProducesWrappedTokens(): void {
    $tokens = NameFormatTokens::build(
      ['given' => 'Jane', 'family' => 'Doe'],
      ' ',
      ', ',
      '',
      'simple',
    );
    $this->assertSame('<span class="given">Jane</span>', $tokens['g']);
    $this->assertSame('<span class="family">Doe</span>', $tokens['f']);
  }

  /**
   * Build() with microdata markup adds itemprop to mapped token values.
   *
   * @covers ::build
   * @covers ::renderComponent
   */
  public function testBuildWithMicrodataMarkupAddsItempropToTokens(): void {
    $tokens = NameFormatTokens::build(
      ['given' => 'Jane'],
      ' ',
      ', ',
      '',
      'microdata',
    );
    $this->assertSame(
      '<span class="given" itemprop="givenName">Jane</span>',
      $tokens['g'],
    );
  }

  /**
   * Build() with rdfa markup adds schema property to mapped token values.
   *
   * @covers ::build
   * @covers ::renderComponent
   */
  public function testBuildWithRdfaMarkupAddsSchemaPropertyToTokens(): void {
    $tokens = NameFormatTokens::build(
      ['given' => 'Jane'],
      ' ',
      ', ',
      '',
      'rdfa',
    );
    $this->assertSame(
      '<span class="given" property="schema:givenName">Jane</span>',
      $tokens['g'],
    );
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Utility;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Utility\NameFormatHelp;

/**
 * Unit tests for NameFormatHelp.
 *
 * @coversDefaultClass \Drupal\name\Utility\NameFormatHelp
 *
 * @group name
 */
class NameFormatHelpTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::markupOptions
   */
  public function testMarkupOptionsReturnsExpectedKeys(): void {
    $options = NameFormatHelp::markupOptions();

    $this->assertSame(
      ['none', 'raw', 'simple', 'microdata', 'rdfa'],
      array_keys($options)
    );

    foreach ($options as $value) {
      $this->assertInstanceOf(TranslatableMarkup::class, $value);
    }

    $this->assertStringContainsString('No markup', (string) $options['none']);
  }

  /**
   * @covers ::tokenHelp
   * @covers ::tokenHelpPlain
   */
  public function testTokenHelpIncludesAllExpectedTokens(): void {
    $expected_keys = [
      't', 'p', 'q', 'g', 'm', 'f', 'c', 's', 'a', 'v', 'w', 'x', 'y', 'z',
      'A', 'I', 'J', 'K', 'M',
      'd', 'D', 'e', 'E',
      'i', 'j', 'k',
      '\\',
      'L', 'U', 'F', 'G', 'T', 'S', 'B', 'b',
      '+', '-', '~', '=', '^', '|',
      '(', ')',
    ];

    $this->assertSame($expected_keys, array_keys(NameFormatHelp::tokenHelpPlain()));
  }

  /**
   * @covers ::tokenHelpPlain
   */
  public function testTokenHelpPlainOmitsCaseHints(): void {
    $tokens = NameFormatHelp::tokenHelpPlain();

    $this->assertStringNotContainsString('<br><small>', (string) $tokens['g']);
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['I']);
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['+']);
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['\\']);
  }

  /**
   * @covers ::tokenHelp
   */
  public function testTokenHelpAppendsCaseHints(): void {
    $tokens = NameFormatHelp::tokenHelp();

    // Lowercase letters get a "(lowercase X)" hint.
    $this->assertStringContainsString('<br><small>', (string) $tokens['g']);
    $this->assertStringContainsString('(lowercase G)</small>', (string) $tokens['g']);

    // Uppercase letters get an "(uppercase X)" hint.
    $this->assertStringContainsString('<br><small>', (string) $tokens['I']);
    $this->assertStringContainsString('(uppercase I)</small>', (string) $tokens['I']);

    // Non-alphabetic tokens are unchanged.
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['+']);
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['\\']);
  }

  /**
   * @covers ::renderableTokenReference
   */
  public function testRenderableTokenReferenceStructure(): void {
    $build = NameFormatHelp::renderableTokenReference();

    $this->assertSame('name_format_parameter_help', $build['#theme']);
    $this->assertArrayHasKey('g', $build['#tokens']);
    $this->assertStringContainsString(
      '(lowercase G)',
      (string) $build['#tokens']['g']
    );
    // No #type key — this is not wrapped in a details element.
    $this->assertArrayNotHasKey('#type', $build);
  }

  /**
   * @covers ::renderableTokenHelp
   */
  public function testRenderableTokenHelpStructure(): void {
    $build = NameFormatHelp::renderableTokenHelp();

    $this->assertSame('details', $build['#type']);
    $this->assertTrue($build['#collapsible']);
    $this->assertTrue($build['#collapsed']);
    $this->assertSame([], $build['#parents']);
    $this->assertSame(
      'name_format_parameter_help',
      $build['format_parameters']['#theme']
    );
    $this->assertArrayHasKey('g', $build['format_parameters']['#tokens']);
    // Tokens in the renderable array use describe=TRUE by default.
    $this->assertStringContainsString(
      '(lowercase G)',
      (string) $build['format_parameters']['#tokens']['g']
    );
  }

}

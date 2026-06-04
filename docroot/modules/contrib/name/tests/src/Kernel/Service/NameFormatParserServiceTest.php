<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Service\NameFormatParserService;

/**
 * @coversDefaultClass \Drupal\name\Service\NameFormatParserService
 *
 * @group name
 */
class NameFormatParserServiceTest extends KernelTestBase {

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
   * @covers ::getMarkupOptions
   */
  public function testFormatParserServiceFromContainer(): void {
    $service = $this->container->get('name.format_parser');
    $this->assertInstanceOf(NameFormatParserService::class, $service);
    $options = $service->getMarkupOptions();
    $this->assertArrayHasKey('microdata', $options);
    $this->assertArrayHasKey('rdfa', $options);
  }

  /**
   * @covers ::parse
   */
  public function testParseWithMicrodataMarkupIntegration(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $components = [
      'given' => 'John',
      'family' => 'Doe',
    ];
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
      'markup' => 'microdata',
    ];
    $out = (string) $parser->parse($components, 'gif', $settings);
    $this->assertStringContainsString('itemprop="givenName"', $out);
    $this->assertStringContainsString('itemprop="familyName"', $out);
  }

  /**
   * @covers ::parse
   * @covers ::renderComponent
   */
  public function testParseWithRdfaMarkupIntegration(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $components = [
      'given' => 'John',
      'family' => 'Doe',
    ];
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
      'markup' => 'rdfa',
    ];
    $out = (string) $parser->parse($components, 'gif', $settings);
    $this->assertStringContainsString('property="schema:givenName"', $out);
    $this->assertStringContainsString('property="schema:familyName"', $out);
  }

  /**
   * @covers ::renderableTokenHelp
   */
  public function testRenderableTokenHelpRendersTheme(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $build = $parser->renderableTokenHelp();
    $output = (string) $this->container->get('renderer')->renderRoot($build);
    $this->assertStringContainsString('recognized in the format parameter string', $output);
    $this->assertStringContainsString('<dl>', $output);
    $this->assertStringContainsString('<dt>g</dt>', $output);
  }

  /**
   * @covers ::parse
   * @covers ::format
   */
  public function testParseEmptyFormatReturnsEmptyString(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $out = (string) $parser->parse([
      'given' => 'John',
      'family' => 'Doe',
    ], '');

    $this->assertSame('', $out);
  }

  /**
   * @covers ::parse
   * @covers ::applyConditions
   */
  public function testParseConditionalTildeWhenPreviousIsEmpty(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];

    // "~" is inserted when the previous component is empty.
    $out = (string) $parser->parse(['given' => 'John'], '~ig', $settings);
    $this->assertSame(' John', $out);
  }

  /**
   * @covers ::parse
   * @covers ::applyConditions
   *
   * @dataProvider conditionalFormatProvider
   */
  public function testParseConditionalFormats(
    array $components,
    string $format,
    string $expected,
  ): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];

    $out = (string) $parser->parse($components, $format, $settings);
    $this->assertSame($expected, $out);
  }

  /**
   * Provides conditional format integration cases.
   *
   * @return array[]
   *   The conditional format cases.
   */
  public static function conditionalFormatProvider(): array {
    return [
      'plus condition between present components' => [
        [
          'given'  => 'John',
          'family' => 'Doe',
        ],
        'g+( )f',
        'John Doe',
      ],
      'minus condition after present component' => [
        [
          'given' => 'John',
        ],
        'g-( Jr.)f',
        'John Jr.',
      ],
      'caret condition before empty component' => [
        [
          'given' => 'John',
        ],
        'g^( Jr.)f',
        'John Jr.',
      ],
      'equals condition before present component' => [
        [
          'given'  => 'John',
          'family' => 'Doe',
        ],
        'g=( )f',
        'John Doe',
      ],
      'fallback condition uses later component' => [
        [
          'given' => 'John',
        ],
        'q|g',
        'John',
      ],
    ];
  }

}

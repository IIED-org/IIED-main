<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Service\NameFormatParserService;
use Drupal\name\Utility\NameFormatHelp;

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
    $options = NameFormatHelp::markupOptions();
    $this->assertArrayHasKey('microdata', $options);
    $this->assertArrayHasKey('rdfa', $options);
  }

  /**
   * @covers ::getMarkupOptions
   */
  public function testGetMarkupOptionsTriggersDeprecation(): void {
    $options = $this->container->get('name.format_parser')->getMarkupOptions();
    $this->assertArrayHasKey('microdata', $options);
    $this->assertArrayHasKey('rdfa', $options);
    $this->assertArrayHasKey('none', $options);
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
   * Invokes a protected method on a NameFormatParserService instance.
   *
   * @param \Drupal\name\Service\NameFormatParserService $parser
   *   The service instance.
   * @param string $method
   *   The protected method name.
   * @param array $arguments
   *   Positional arguments for the method.
   *
   * @return mixed
   *   The invoked method return value.
   */
  protected function callProtectedParserMethod(
    NameFormatParserService $parser,
    string $method,
    array $arguments = [],
  ): mixed {
    $reflection = new \ReflectionMethod(NameFormatParserService::class, $method);
    $reflection->setAccessible(TRUE);
    return $reflection->invokeArgs($parser, $arguments);
  }

  /**
   * AddComponent() applies modifiers and resets the by-ref params to ''.
   *
   * @covers ::addComponent
   */
  public function testAddComponentAppliesModifiersAndClearsByRefParams(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser     = $this->container->get('name.format_parser');
    $modifiers  = 'L';
    $conditions = '+';
    $method     = new \ReflectionMethod(
      NameFormatParserService::class,
      'addComponent',
    );
    $method->setAccessible(TRUE);
    $piece = $method->invokeArgs(
      $parser,
      ['JOHN', &$modifiers, &$conditions],
    );

    $this->assertSame(['value' => 'john', 'conditions' => '+'], $piece);
    $this->assertSame('', $modifiers, 'modifiers must be reset by-ref');
    $this->assertSame('', $conditions, 'conditions must be reset by-ref');
  }

  /**
   * ApplyModifiers() uses the instance boundary regexp for B/b modifiers.
   *
   * @covers ::applyModifiers
   */
  public function testApplyModifiersUsesInstanceBoundaryRegExp(): void {
    // DeprecationHandler dedupes identical messages within a request.
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');

    $this->assertSame(
      'john',
      $this->callProtectedParserMethod($parser, 'applyModifiers', ['JOHN', 'L']),
    );

    // Default boundary regexp '/[\b,\s]/' does not split on '/'.
    $this->assertSame(
      'foo/bar',
      $this->callProtectedParserMethod($parser, 'applyModifiers', ['foo/bar', 'B']),
    );

    // Override the instance regexp to split on '/' instead.
    $prop = new \ReflectionProperty(NameFormatParserService::class, 'boundaryRegExp');
    $prop->setAccessible(TRUE);
    $prop->setValue($parser, '/[\/]/');

    $this->assertSame(
      'foo',
      $this->callProtectedParserMethod($parser, 'applyModifiers', ['foo/bar', 'B']),
    );
  }

  /**
   * ClosingBracketPosition() delegates to NameFormatParser.
   *
   * @covers ::closingBracketPosition
   */
  public function testClosingBracketPositionDelegatesToParser(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');

    $this->assertSame(
      4,
      $this->callProtectedParserMethod(
        $parser,
        'closingBracketPosition',
        ['(abc)'],
      ),
    );
    $this->assertFalse(
      $this->callProtectedParserMethod(
        $parser,
        'closingBracketPosition',
        ['(abc'],
      ),
    );
  }

  /**
   * RenderComponent() uses the instance markup mode set by a prior parse().
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentUsesInstanceMarkup(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $parser->parse(['given' => 'x'], 'g', [
      'markup' => 'microdata',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $parser,
      'renderComponent',
      ['Jane', 'given'],
    );
    $this->assertStringContainsString('itemprop="givenName"', $result);
    $this->assertStringContainsString('>Jane<', $result);
  }

  /**
   * RenderComponent() with the 'initial' modifier returns the first letter.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentInitialModifierReturnsFirstLetter(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $parser->parse(['given' => 'x'], 'g', [
      'markup' => 'none',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $parser,
      'renderComponent',
      ['Jane', 'given', 'initial'],
    );
    $this->assertSame('J', $result);
  }

  /**
   * RenderFirstComponent() returns NULL when all candidates are empty.
   *
   * @covers ::renderFirstComponent
   */
  public function testRenderFirstComponentReturnsNullWhenAllEmpty(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $parser->parse(['given' => 'x'], 'g', [
      'markup' => 'microdata',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $parser,
      'renderFirstComponent',
      [['', NULL, ''], 'given'],
    );
    $this->assertNull($result);
  }

  /**
   * RenderFirstComponent() returns the first non-empty value with markup.
   *
   * @covers ::renderFirstComponent
   */
  public function testRenderFirstComponentReturnsFirstNonEmptyWithMarkup(): void {
    /** @var \Drupal\name\Service\NameFormatParserService $parser */
    $parser = $this->container->get('name.format_parser');
    $parser->parse(['given' => 'x'], 'g', [
      'markup' => 'microdata',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $parser,
      'renderFirstComponent',
      [['', 'Jane'], 'given'],
    );
    $this->assertStringContainsString('itemprop="givenName"', $result);
    $this->assertStringContainsString('>Jane<', $result);
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

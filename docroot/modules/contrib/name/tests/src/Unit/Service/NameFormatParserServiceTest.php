<?php

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\NameFormatParserService;
use Drupal\name\Utility\NameFormatHelp;
use Drupal\name\Utility\NameFormatTokens;

/**
 * Tests the name formatter.
 *
 * @coversDefaultClass \Drupal\name\Service\NameFormatParserService
 *
 * @group name
 */
class NameFormatParserServiceTest extends UnitTestCase {
  /**
   * The name format parser.
   *
   * @var \Drupal\name\Service\NameFormatParserService
   */
  protected $parser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $test_settings = [
      'name.settings' => [
        'sep1' => ',',
        'sep2' => ' ',
        'sep3' => '',
      ],
    ];
    $config_factory = $this->getConfigFactoryStub($test_settings);
    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->parser = new NameFormatParserService($config_factory);
  }

  /**
   * Convert names() to PHPUnit compatible format.
   *
   * @return array
   *   An array of names.
   */
  public static function patternDataProvider() {
    $data = [];

    foreach (static::names() as $dataSet) {
      foreach ($dataSet['tests'] as $pattern => $expected) {
        $data[] = [
          $dataSet['components'],
          $pattern,
          $expected,
        ];
      }
    }

    return $data;
  }

  /**
   * Test NameFormatParserService.
   *
   * @dataProvider patternDataProvider
   */
  public function testParser($components, $pattern, $expected) {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];

    $formatted = $this->parser->parse($components, $pattern, $settings);
    $this->assertEquals($expected, $formatted);
  }

  /**
   * Tests parsing a name with special characters.
   */
  public function testParseUsingRawFormat() {
    $components = [
      'given' => 'Bobo',
      'middle' => "'t",
      'family' => "K'nijn",
    ];
    $pattern = '((((t+ig)+im)+if)+is)+jc';
    $settings = [
      'markup' => 'raw',
    ];
    $formatted = $this->parser->parse($components, $pattern, $settings);
    $this->assertInstanceOf(MarkupInterface::class, $formatted);
    $this->assertEquals("Bobo 't K'nijn", (string) $formatted);
  }

  /**
   * Tests raw markup mode strips unsafe tags and keeps allowed markup.
   */
  public function testParseUsingRawFormatSanitizesScriptTags(): void {
    $components = [
      'given' => '<script>alert(1)</script><em>Alice</em>',
      'family' => 'Doe',
    ];
    $settings = [
      'markup' => 'raw',
    ];

    $formatted = $this->parser->parse($components, 'gif', $settings);

    $this->assertInstanceOf(MarkupInterface::class, $formatted);
    $this->assertStringNotContainsString('<script', (string) $formatted);
    $this->assertStringContainsString('<em>Alice</em>', (string) $formatted);
    $this->assertStringContainsString('Doe', (string) $formatted);
  }

  /**
   * Tests that an empty settings argument loads separators from name.settings.
   */
  public function testParseUsesConfigWhenNoSettingsPassed(): void {
    $components = [
      'given' => 'John',
      'family' => 'dOE',
    ];
    // Stub config uses sep1 comma and sep2 space (see setUp()).
    $pattern = 'gijf';
    $formatted = $this->parser->parse($components, $pattern);
    $this->assertEquals('John, dOE', (string) $formatted);
  }

  /**
   * Tests legacy construction with no args uses the global config factory.
   */
  public function testConstructorOmitsConfigFactoryUsesDrupalContainer(): void {
    $parser = new NameFormatParserService();
    $components = ['given' => 'John', 'family' => 'dOE'];
    $formatted = $parser->parse($components, 'gijf');
    $this->assertEquals('John, dOE', (string) $formatted);
  }

  /**
   * @covers ::getMarkupOptions
   */
  public function testGetMarkupOptionsMatchesUtility(): void {
    $options = $this->parser->getMarkupOptions();
    $this->assertTranslatedOptionsEqual(NameFormatHelp::markupOptions(), $options);
  }

  /**
   * @covers ::getMarkupOptions
   */
  public function testGetMarkupOptionsTriggersDeprecation(): void {
    $options = $this->parser->getMarkupOptions();
    $this->assertArrayHasKey('microdata', $options);
    $this->assertArrayHasKey('rdfa', $options);
    $this->assertArrayHasKey('none', $options);
  }

  /**
   * @covers ::tokenHelp
   */
  public function testTokenHelpWithoutDescribeDoesNotAppendLetterHint(): void {
    $tokens = $this->parser->tokenHelp(FALSE);
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['g']);
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['+']);
    $this->assertStringNotContainsString('<br><small>', (string) $tokens['\\']);
  }

  /**
   * @covers ::tokenHelp
   */
  public function testTokenHelpAppendsLetterHints(): void {
    $tokens = $this->parser->tokenHelp();
    $this->assertStringContainsString('<br><small>', (string) $tokens['g']);
    $this->assertStringContainsString('(lowercase G)</small>', (string) $tokens['g']);
    $this->assertStringContainsString('<br><small>', (string) $tokens['I']);
    $this->assertStringContainsString('(uppercase I)</small>', (string) $tokens['I']);
  }

  /**
   * @covers ::renderableTokenHelp
   */
  public function testRenderableTokenHelpStructure(): void {
    $build = $this->parser->renderableTokenHelp();
    $this->assertSame('details', $build['#type']);
    $this->assertTrue($build['#collapsible']);
    $this->assertTrue($build['#collapsed']);
    $this->assertSame('name_format_parameter_help', $build['format_parameters']['#theme']);
    $this->assertArrayHasKey('g', $build['format_parameters']['#tokens']);
  }

  /**
   * @covers ::parse
   */
  public function testParseWithMicrodataMarkup(): void {
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
    $out = (string) $this->parser->parse($components, 'gif', $settings);
    $this->assertStringContainsString('itemprop="givenName"', $out);
    $this->assertStringContainsString('itemprop="familyName"', $out);
    $this->assertStringContainsString('>John<', $out);
    $this->assertStringContainsString('>Doe<', $out);
  }

  /**
   * @covers ::parse
   */
  public function testParseWithRdfaMarkup(): void {
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
    $out = (string) $this->parser->parse($components, 'gif', $settings);
    $this->assertStringContainsString('property="schema:givenName"', $out);
    $this->assertStringContainsString('property="schema:familyName"', $out);
  }

  /**
   * @covers ::parse
   */
  public function testParseMicrodataEscapesHtmlInValues(): void {
    $components = ['given' => 'A&B', 'family' => 'Doe'];
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
      'markup' => 'microdata',
    ];
    $out = (string) $this->parser->parse($components, 'gif', $settings);
    $this->assertStringContainsString('A&amp;B', $out);
    $this->assertStringNotContainsString('A&B', $out);
  }

  /**
   * Unmapped schema keys omit itemprop (e.g. generational).
   *
   * @covers ::parse
   */
  public function testParseMicrodataGenerationalOmitsItemprop(): void {
    $components = ['generational' => 'Jr'];
    $settings = ['markup' => 'microdata'];
    $out = (string) $this->parser->parse($components, 's', $settings);
    $this->assertStringContainsString('class="generational"', $out);
    $this->assertStringNotContainsString('itemprop=', $out);
  }

  /**
   * Tilde conditional: insert separator when there is no previous component.
   *
   * @covers ::parse
   */
  public function testParseConditionalTildeInsertsSeparatorWhenPreviousEmpty(): void {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];
    $out = (string) $this->parser->parse(['given' => 'John'], '~ig', $settings);
    $this->assertSame(' John', $out);
  }

  /**
   * Tilde conditional: omit separator when the previous component is non-empty.
   *
   * @covers ::parse
   */
  public function testParseConditionalTildeOmitsSeparatorWhenPreviousNotEmpty(): void {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];
    $out = (string) $this->parser->parse(
      [
        'given' => 'John',
        'family' => 'Doe',
      ],
      'g~if',
      $settings
    );
    $this->assertSame('JohnDoe', $out);
    $with_sep = (string) $this->parser->parse(
      [
        'given' => 'John',
        'family' => 'Doe',
      ],
      'gif',
      $settings
    );
    $this->assertSame('John Doe', $with_sep);
  }

  /**
   * Lowercase modifier preserves a single outer span from markup output.
   *
   * @covers ::parse
   */
  public function testParseModifierLowercasePreservesSimpleMarkupWrapper(): void {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
      'markup' => 'simple',
    ];
    $out = (string) $this->parser->parse(['given' => 'JOHN'], 'Lg', $settings);
    $this->assertStringContainsString('class="given"', $out);
    $this->assertStringContainsString('>john<', $out);
    $this->assertStringNotContainsString('>JOHN<', $out);
  }

  /**
   * Lowercase modifier keeps microdata attributes on the outer span.
   *
   * @covers ::parse
   */
  public function testParseModifierLowercasePreservesMicrodataAttributes(): void {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
      'markup' => 'microdata',
    ];
    $out = (string) $this->parser->parse(['given' => 'JOHN'], 'Lg', $settings);
    $this->assertStringContainsString('itemprop="givenName"', $out);
    $this->assertStringContainsString('>john<', $out);
  }

  /**
   * Preferred token is empty when both preferred and given are empty.
   *
   * @covers ::parse
   */
  public function testParsePreferredTokenEmptyWhenPreferredAndGivenEmpty(): void {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];
    $out = (string) $this->parser->parse(
      [
        'given' => '',
        'preferred' => '',
      ],
      'p',
      $settings
    );
    $this->assertSame('', $out);
  }

  /**
   * First-letter preferred-or-given token is empty when both are empty.
   *
   * @covers ::parse
   */
  public function testParsePreferredOrGivenInitialTokenEmptyWhenPreferredAndGivenEmpty(): void {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];
    $out = (string) $this->parser->parse(
      [
        'given' => '',
        'preferred' => '',
      ],
      'w',
      $settings
    );
    $this->assertSame('', $out);
  }

  /**
   * Simple markup wraps components in class spans with escaped text.
   *
   * @covers ::parse
   */
  public function testParseWithSimpleMarkup(): void {
    $components = [
      'given' => 'John',
      'family' => 'Doe',
    ];
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
      'markup' => 'simple',
    ];
    $out = (string) $this->parser->parse($components, 'gif', $settings);
    $this->assertStringContainsString('class="given"', $out);
    $this->assertStringContainsString('class="family"', $out);
    $this->assertStringContainsString('>John<', $out);
    $this->assertStringContainsString('>Doe<', $out);
  }

  /**
   * Unmapped RDFa keys omit schema property (e.g. generational).
   *
   * @covers ::parse
   */
  public function testParseRdfaGenerationalOmitsProperty(): void {
    $components = ['generational' => 'Jr'];
    $settings = ['markup' => 'rdfa'];
    $out = (string) $this->parser->parse($components, 's', $settings);
    $this->assertStringContainsString('class="generational"', $out);
    $this->assertStringNotContainsString('property="schema:', $out);
  }

  /**
   * Empty format returns an empty string.
   *
   * @covers ::parse
   */
  public function testParseWithEmptyFormatReturnsEmptyString(): void {
    $settings = [
      'sep1' => ' ',
      'sep2' => ', ',
      'sep3' => '',
    ];

    $out = (string) $this->parser->parse(['given' => 'John'], '', $settings);
    $this->assertSame('', $out);
  }

  /**
   * Asserts two option arrays match (keys and rendered strings).
   *
   * @param array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string> $expected
   *   Expected options.
   * @param array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string> $actual
   *   Options from the code under test.
   */
  private function assertTranslatedOptionsEqual(array $expected, array $actual): void {
    $this->assertSame(array_keys($expected), array_keys($actual));
    foreach ($expected as $key => $markup) {
      $this->assertSame((string) $markup, (string) $actual[$key]);
    }
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
    $modifiers  = 'L';
    $conditions = '+';
    $method     = new \ReflectionMethod(
      NameFormatParserService::class,
      'addComponent',
    );
    $method->setAccessible(TRUE);
    $piece = $method->invokeArgs(
      $this->parser,
      ['JOHN', &$modifiers, &$conditions],
    );

    $this->assertSame(['value' => 'john', 'conditions' => '+'], $piece);
    $this->assertSame('', $modifiers, 'modifiers must be reset by-ref');
    $this->assertSame('', $conditions, 'conditions must be reset by-ref');
  }

  /**
   * AddComponent() with no modifiers returns the value unchanged.
   *
   * @covers ::addComponent
   */
  public function testAddComponentNoModifiersReturnsValueUnchanged(): void {
    $modifiers  = '';
    $conditions = '';
    $method     = new \ReflectionMethod(
      NameFormatParserService::class,
      'addComponent',
    );
    $method->setAccessible(TRUE);
    $piece = $method->invokeArgs(
      $this->parser,
      ['John', &$modifiers, &$conditions],
    );

    $this->assertSame(['value' => 'John', 'conditions' => ''], $piece);
  }

  /**
   * ApplyModifiers() delegates L/U to the utility.
   *
   * Uses the instance boundary regexp for the B/b modifiers.
   *
   * @covers ::applyModifiers
   */
  public function testApplyModifiersUsesInstanceBoundaryRegExp(): void {
    // DeprecationHandler dedupes identical messages within a request.
    $this->assertSame(
      'john',
      $this->callProtectedParserMethod($this->parser, 'applyModifiers', ['JOHN', 'L']),
    );
    $this->assertSame(
      'JOHN',
      $this->callProtectedParserMethod($this->parser, 'applyModifiers', ['john', 'U']),
    );

    // Default boundary regexp '/[\b,\s]/' does not split on '/'.
    $this->assertSame(
      'foo/bar',
      $this->callProtectedParserMethod($this->parser, 'applyModifiers', ['foo/bar', 'B']),
    );

    // Override the instance regexp to split on '/' instead.
    $prop = new \ReflectionProperty(NameFormatParserService::class, 'boundaryRegExp');
    $prop->setAccessible(TRUE);
    $prop->setValue($this->parser, '/[\/]/');

    $this->assertSame(
      'foo',
      $this->callProtectedParserMethod($this->parser, 'applyModifiers', ['foo/bar', 'B']),
    );
  }

  /**
   * ClosingBracketPosition() delegates to NameFormatParser.
   *
   * @covers ::closingBracketPosition
   */
  public function testClosingBracketPositionDelegatesToParser(): void {
    $this->assertSame(
      4,
      $this->callProtectedParserMethod(
        $this->parser,
        'closingBracketPosition',
        ['(abc)'],
      ),
    );
    $this->assertFalse(
      $this->callProtectedParserMethod(
        $this->parser,
        'closingBracketPosition',
        ['(abc'],
      ),
    );
  }

  /**
   * RenderComponent() passes the instance markup mode to the utility.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentUsesInstanceMarkup(): void {
    // Prime markup state via parse().
    $this->parser->parse(['given' => 'x'], 'g', [
      'markup' => 'simple',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $this->parser,
      'renderComponent',
      ['John', 'given'],
    );
    $this->assertSame(
      '<span class="given">John</span>',
      $result,
    );
  }

  /**
   * RenderComponent() with an 'initial' modifier returns only the first letter.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentInitialModifierReturnsFirstLetter(): void {
    $this->parser->parse(['given' => 'x'], 'g', [
      'markup' => 'none',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $this->parser,
      'renderComponent',
      ['JoHn', 'given', 'initial'],
    );
    $this->assertSame('J', $result);
  }

  /**
   * RenderFirstComponent() returns NULL when all candidates are empty.
   *
   * @covers ::renderFirstComponent
   */
  public function testRenderFirstComponentReturnsNullWhenAllEmpty(): void {
    $this->parser->parse(['given' => 'x'], 'g', [
      'markup' => 'simple',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $this->parser,
      'renderFirstComponent',
      [['', NULL, ''], 'given'],
    );
    $this->assertNull($result);
  }

  /**
   * RenderFirstComponent() returns the first non-empty rendered value.
   *
   * Uses the instance markup mode.
   *
   * @covers ::renderFirstComponent
   */
  public function testRenderFirstComponentReturnsFirstNonEmptyWithMarkup(): void {
    $this->parser->parse(['given' => 'x'], 'g', [
      'markup' => 'simple',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);

    $result = $this->callProtectedParserMethod(
      $this->parser,
      'renderFirstComponent',
      [['', 'John'], 'given'],
    );
    $this->assertSame('<span class="given">John</span>', $result);
  }

  /**
   * @covers ::format
   */
  public function testFormatTriggersDeprecation(): void {
    $result = $this->callProtectedParserMethod($this->parser, 'format', [
      ['given' => 'John'],
      '',
    ]);
    $this->assertSame('', $result);
  }

  /**
   * @covers ::format
   */
  public function testFormatDelegatesToUtilityWithTokens(): void {
    $this->parser->parse(['given' => 'x'], 'g', [
      'markup' => 'none',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);
    $result = $this->callProtectedParserMethod($this->parser, 'format', [
      ['given' => 'John', 'family' => 'Doe'],
      'gif',
    ]);
    $this->assertSame('John Doe', $result);
  }

  /**
   * @covers ::generateTokens
   */
  public function testGenerateTokensTriggersDeprecation(): void {
    $this->parser->parse(['given' => 'x'], 'g', [
      'markup' => 'none',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);
    $tokens = $this->callProtectedParserMethod($this->parser, 'generateTokens', [
      ['given' => 'John'],
    ]);
    $this->assertArrayHasKey('g', $tokens);
    $this->assertSame('John', $tokens['g']);
  }

  /**
   * @covers ::generateTokens
   */
  public function testGenerateTokensMatchesUtility(): void {
    $this->parser->parse(['given' => 'x'], 'g', [
      'markup' => 'simple',
      'sep1'   => ' ',
      'sep2'   => ', ',
      'sep3'   => '',
    ]);
    $components = ['given' => 'Jane', 'family' => 'Smith'];
    $tokens = $this->callProtectedParserMethod($this->parser, 'generateTokens', [$components]);
    $this->assertSame(
      NameFormatTokens::build($components, ' ', ', ', '', 'simple'),
      $tokens,
    );
  }

  /**
   * Helper function to provide data for testParser.
   *
   * @return array
   *   The data to test.
   */
  protected static function names() {
    return [
      'given' => [
        'components' => ['given' => 'John'],
        'tests' => [
          // Test that only the given name creates an entry.
          // Title.
          't' => '',
          // Given name.
          'g' => 'John',
          // Escaped letter.
          '\g' => 'g',
          // Middle name(s).
          'm' => '',
          // Family name.
          'f' => '',
          // Credentials.
          'c' => '',
          // Generational suffix.
          's' => '',
          // First letter given.
          'x' => 'J',
          // First letter middle.
          'y' => '',
          // First letter family.
          'z' => '',
          // Either the given or family name. Given name is given preference.
          'e' => 'John',
          // Either the given or family name. Family name is given preference.
          'E' => 'John',
          // Combination tests.
          // Using a single space.
          'g f' => 'John ',
          // Separator 1.
          'gif' => 'John ',
          // Separator 2.
          'gjf' => 'John, ',
          // Separator 3.
          'gkf' => 'John',
          'f g' => ' John',
          'fig' => ' John',
          'fjg' => ', John',
          'fkg' => 'John',
          't g t' => ' John ',
          'tigit' => ' John ',
          'tjgjt' => ', John, ',
          'tkgkt' => 'John',
          // Modifier entries.
          // To lowercase.
          'Lg' => 'john',
          // To uppercase.
          'Ug' => 'JOHN',
          // First letter to uppercase.
          'Fg' => 'John',
          // First letter of all words to uppercase.
          'Gg' => 'John',
          // Lowercase, first letter to uppercase.
          'LF(g)' => 'John',
          // Lowercase, first letter of all words to uppercase.
          'LG(g)' => 'John',
          // Lowercase, first letter to uppercase.
          'LFg' => 'John',
          // Lowercase, first letter of all words to uppercase.
          'LGg' => 'John',
          // Trims whitespace around the next token.
          'Tg' => 'John',
          // @todo assess the old check_plain run on code test / token.
          'Sg' => 'John',
          // Conditional entries.
          // Brackets.
          '(((g)))' => 'John',
          // Brackets - mismatched.
          '(g))()(' => 'John)(',
          // Insert the token if both the surrounding tokens are not empty.
          'g+ f' => 'John',
          // Insert the token, iff the next token after it is not empty.
          'g= f' => 'John',
          // Skip the token, iff the next token after it is not empty.
          'g^ f' => 'John ',
          // Uses only the first one.
          's|c|g|m|f|t' => 'John',
          // Uses the previous token unless empty, otherwise it uses this token.
          'g|f' => 'John',
          // Real world examples.
          // Full name with a comma-space before credentials.
          'L(t= g= m= f= s=,(= c))' => ' john',
          // Full name with a comma-space before credentials. ucfirst does not
          // work on a whitespace.
          'TS(LF(t= g= m= f= s)=,(= c))' => 'john',
          // Full name with a comma-space before credentials.
          'L(t+ g+ m+ f+ s+,(= c))' => 'john',
          // Full name with a comma-space before credentials.
          'TS(LF(t+ g+ m+ f+ s)+,(= c))' => 'John',
        ],
      ],
      'full' => [
        'components' => [
          'title' => 'MR.',
          'given' => 'JoHn',
          'middle' => 'pEter',
          'family' => 'dOE',
          'generational' => 'sR',
          'credentials' => 'b.Sc, pHd',
          'preferred' => 'peTe',
          'alternative' => 'aLt',
        ],
        // Tests "MR. JoHn pEter dOE sR b.Sc, pHd".
        'tests' => [
          // Test that only the given name creates a entry.
          // Title.
          't' => 'MR.',
          // Given name.
          'g' => 'JoHn',
          // Preferred name.
          'p' => 'peTe',
          // Preferred name without fallback.
          'q' => 'peTe',
          // Middle name(s).
          'm' => 'pEter',
          // Family name.
          'f' => 'dOE',
          // Credentials.
          'c' => 'b.Sc, pHd',
          // Generational suffix.
          's' => 'sR',
          // Alternative name.
          'a' => 'aLt',
          // First letter of the preferred or given.
          'w' => 'p',
          // First letter of the preferred without fallback.
          'v' => 'p',
          // First letter given.
          'x' => 'J',
          // First letter middle.
          'y' => 'p',
          // First letter family.
          'z' => 'd',
          // First letter of alternative name.
          'A' => 'a',
          // Initials (all) from given and family.
          'I' => 'JD',
          // Initials (all) from given, middle and family.
          'J' => 'JPD',
          // Initials (all) from given.
          'K' => 'J',
          // Initials (all) from given and middle.
          'M' => 'JP',
          // Either the preferred or family name. Preferred is given preference.
          'd' => 'peTe',
          // Either the preferred or family name. Family is given preference.
          'D' => 'dOE',
          // Either the given or family name. Given name is given preference.
          'e' => 'JoHn',
          // Either the given or family name. Family name is given preference.
          'E' => 'dOE',
          // Combination tests.
          // Using a single space.
          'g f' => 'JoHn dOE',
          // Using a single space with preferred.
          'p f' => 'peTe dOE',
          // Separator 1.
          'gif' => 'JoHn dOE',
          // Separator 2.
          'gjf' => 'JoHn, dOE',
          // Separator 3.
          'gkf' => 'JoHndOE',
          'f g' => 'dOE JoHn',
          'fig' => 'dOE JoHn',
          'fjg' => 'dOE, JoHn',
          'fkg' => 'dOEJoHn',
          't g t' => 'MR. JoHn MR.',
          'tigit' => 'MR. JoHn MR.',
          'tjgjt' => 'MR., JoHn, MR.',
          'tkgkt' => 'MR.JoHnMR.',
          // Modifier entries.
          // Lowercase.
          'L(t g m f s c)' => 'mr. john peter doe sr b.sc, phd',
          // Uppercase.
          'U(t g m f s c)' => 'MR. JOHN PETER DOE SR B.SC, PHD',
          // First letter to uppercase.
          'F(t g m f s c)' => 'MR. JoHn pEter dOE sR b.Sc, pHd',
          // First letter of all words to uppercase.
          'G(t g m f s c)' => 'MR. JoHn PEter DOE SR B.Sc, PHd',
          // First letter to uppercase.
          'LF(t g m f s c)' => 'Mr. john peter doe sr b.sc, phd',
          // First letter of all words to uppercase.
          'LG(t g m f s c)' => 'Mr. John Peter Doe Sr B.Sc, Phd',
          // Trims whitespace around the next token.
          'T(t g m f s c)' => 'MR. JoHn pEter dOE sR b.Sc, pHd',
          // @todo Assess the old check_plain run on code test / token.
          'S(t g m f s c)' => 'MR. JoHn pEter dOE sR b.Sc, pHd',
          // Use the first word of the next token.
          'Bc' => 'b.Sc',
          // Use the last word of the next token.
          'bc' => 'pHd',
          // Use the first word of the next token, nested tokens.
          'B((LG(g= m= f= s)|a)=,LG(= c))' => 'John',
          // Use the last word of the next token, nested tokens.
          'b((LG(g= m= f= s)|a)=,LG(= c))' => 'Phd',
          // Conditional entries
          // Brackets.
          '(((t g m f s c)))' => 'MR. JoHn pEter dOE sR b.Sc, pHd',
          // Brackets - mismatched.
          '(t g m f s c))()(' => 'MR. JoHn pEter dOE sR b.Sc, pHd)(',
          // Insert the token, iff the next token after it is not empty.
          't= g= m= f= s= c' => 'MR. JoHn pEter dOE sR b.Sc, pHd',
          // Uses the previous token unless empty, otherwise it uses this token.
          'g|m|f' => 'JoHn',
          // Uses the previous token unless empty, otherwise it uses this token.
          'g|a' => 'JoHn',
          // Uses the previous token unless empty, otherwise it uses this token.
          'a|g' => 'aLt',
          // Uses the previous token unless empty, otherwise it uses this token.
          'm|f|g' => 'pEter',
          // Uses only the first one.
          's|c|g|m|f|t' => 'sR',
          // Real world examples.
          // Full name with a comma-space before credentials.
          'L(t= g= m= f= s=,(= c))' => 'mr. john peter doe sr, b.sc, phd',
          // Full name with a comma-space before credentials.
          'TS(LG(t= g= m= f= s)=,LG(= c))' => 'Mr. John Peter Doe Sr, B.Sc, Phd',
          // Alt or full name followed by a comma-space before credentials.
          'TS(a|LG(t= g= m= f= s)=,LG(= c))' => 'aLt, B.Sc, Phd',
          // Full name or alt followed by a comma-space before credentials.
          'TS((LG(t= g= m= f= s)|a)=,LG(= c))' => 'Mr. John Peter Doe Sr, B.Sc, Phd',
          // Full name including preferred name (nickname).
          'TS(LG(((t+ig+i(=\(q-\)))+im)+if)+iLG(s))' => 'Mr. John (Pete) Peter Doe Sr',
        ],
      ],
      'initials' => [
        'components' => [
          'given' => 'JoHn william',
          'middle' => 'pEter smith jOnes',
          'family' => 'dOE waLker',
        ],
        // Tests "JoHn william pEter smith dOE waLker".
        'tests' => [
          // Initials (all) from given and family.
          'I' => 'JWDW',
          // Initials (all) from given, middle and family.
          'J' => 'JWPSJDW',
          // Initials (all) from given.
          'K' => 'JW',
          // Initials (all) from given and middle.
          'M' => 'JWPSJ',
          // Family name with custom conditional separator before initials.
          'LG(f)+(; )K' => 'Doe Walker; JW',
        ],
      ],
    ];
  }

}

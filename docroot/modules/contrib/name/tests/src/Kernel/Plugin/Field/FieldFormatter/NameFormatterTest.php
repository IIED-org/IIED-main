<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Entity\NameFormat;
use Drupal\name\Entity\NameListFormat;
use Drupal\name\NameFormatter;
use Drupal\name\Render\NameListFormattableMarkup;
use Drupal\name\Service\NameFormatParserService;

/**
 * Tests the NameFormatter service.
 *
 * @group name
 * @group legacy
 * @coversDefaultClass \Drupal\name\NameFormatter
 * @covers \Drupal\name\Service\NameFormatterService::formatList
 */
class NameFormatterTest extends KernelTestBase {

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
   * The name formatter service.
   *
   * @var \Drupal\name\NameFormatter
   */
  protected NameFormatter $nameFormatter;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The name format parser.
   *
   * @var \Drupal\name\Service\NameFormatParserService
   */
  protected NameFormatParserService $nameFormatParser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected TranslationInterface $stringTranslation;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->nameFormatParser = $this->container->get('name.format_parser');
    $this->languageManager = $this->container->get('language_manager');
    $this->stringTranslation = $this->container->get('string_translation');
    $this->configFactory = $this->container->get('config.factory');

    // Create the name formatter service.
    $this->nameFormatter = new NameFormatter(
      $this->entityTypeManager,
      $this->nameFormatParser,
      $this->languageManager,
      $this->stringTranslation,
      $this->configFactory
    );
  }

  /**
   * Tests the constructor and initial settings.
   */
  public function testConstructor(): void {
    $this->assertEquals(' ', $this->nameFormatter->getSetting('sep1'));
    $this->assertEquals(', ', $this->nameFormatter->getSetting('sep2'));
    $this->assertEquals('', $this->nameFormatter->getSetting('sep3'));
    $this->assertEquals('none', $this->nameFormatter->getSetting('markup'));
  }

  /**
   * Tests setting and getting settings.
   */
  public function testSetAndGetSettings(): void {
    $this->nameFormatter->setSetting('sep1', ' - ');
    $this->nameFormatter->setSetting('markup', 'simple');
    $this->nameFormatter->setSetting('custom_setting', 'custom_value');

    $this->assertEquals(' - ', $this->nameFormatter->getSetting('sep1'));
    $this->assertEquals('simple', $this->nameFormatter->getSetting('markup'));
    $this->assertEquals('custom_value', $this->nameFormatter->getSetting('custom_setting'));
    $this->assertNull($this->nameFormatter->getSetting('nonexistent_setting'));
  }

  /**
   * Tests formatting a single name with default format.
   */
  public function testFormatDefault(): void {
    $components = [
      'title' => 'Dr.',
      'given' => 'John',
      'family' => 'Smith',
    ];

    $result = $this->nameFormatter->format($components);
    // Default markup returns a string.
    $this->assertIsString($result);
    $this->assertStringContainsString('Dr.', $result);
    $this->assertStringContainsString('John', $result);
    $this->assertStringContainsString('Smith', $result);
  }

  /**
   * Tests formatting a name with custom format.
   */
  public function testFormatCustom(): void {
    // Create a custom name format.
    $customFormat = NameFormat::create([
      'id' => 'custom_test',
      'label' => 'Custom Test Format',
      'pattern' => 't+ig+if',
    ]);
    $customFormat->save();

    $components = [
      'title' => 'Mr.',
      'given' => 'Bob',
      'family' => 'Johnson',
    ];

    $result = $this->nameFormatter->format($components, 'custom_test');
    // Custom format returns a string.
    $this->assertIsString($result);
    $this->assertStringContainsString('Mr.', $result);
    $this->assertStringContainsString('Bob', $result);
    $this->assertStringContainsString('Johnson', $result);
  }

  /**
   * Tests formatting a name with URL component.
   */
  public function testFormatWithUrl(): void {
    $components = [
      'title' => 'Prof.',
      'given' => 'Jane',
      'family' => 'Doe',
      'url' => Url::fromUri('https://example.com'),
    ];

    $result = $this->nameFormatter->format($components);
    $this->assertInstanceOf(FormattableMarkup::class, $result);
    $this->assertStringContainsString('<a href="', $result->jsonSerialize());
    $this->assertStringContainsString('https://example.com', $result->jsonSerialize());
  }

  /**
   * Tests formatting a name list with single item.
   */
  public function testFormatListSingle(): void {
    $items = [
      [
        'title' => 'Dr.',
        'given' => 'Alice',
        'family' => 'Brown',
      ],
    ];

    $result = $this->nameFormatter->formatList($items);
    // Single items can be strings or FormattableMarkup objects.
    $this->assertTrue(is_string($result) || $result instanceof FormattableMarkup);

    $resultString = (string) $result;
    $this->assertStringContainsString('Dr.', $resultString);
    $this->assertStringContainsString('Alice', $resultString);
    $this->assertStringContainsString('Brown', $resultString);
  }

  /**
   * Tests formatting a name list with multiple items.
   */
  public function testFormatListMultiple(): void {
    $items = [
      [
        'title' => 'Mr.',
        'given' => 'John',
        'family' => 'Smith',
      ],
      [
        'title' => 'Ms.',
        'given' => 'Jane',
        'family' => 'Doe',
      ],
    ];

    $result = $this->nameFormatter->formatList($items);
    $this->assertInstanceOf(TranslatableMarkup::class, $result);

    // Convert to string for content assertions.
    $resultString = (string) $result;
    $this->assertStringContainsString('John', $resultString);
    $this->assertStringContainsString('Jane', $resultString);
    $this->assertStringContainsString('and', $resultString);
  }

  /**
   * Tests formatting a name list with et al functionality.
   */
  public function testFormatListWithEtAl(): void {
    // Create a custom list format with et al settings.
    $customListFormat = NameListFormat::create([
      'id' => 'et_al_test',
      'label' => 'Et Al Test Format',
      'delimiter' => '; ',
      'and' => 'text',
      'delimiter_precedes_last' => 'always',
      'el_al_min' => 2,
      'el_al_first' => 1,
    ]);
    $customListFormat->save();

    $items = [
      [
        'title' => 'Dr.',
        'given' => 'Alice',
        'family' => 'Brown',
      ],
      [
        'title' => 'Prof.',
        'given' => 'Bob',
        'family' => 'Johnson',
      ],
      [
        'title' => 'Mr.',
        'given' => 'Charlie',
        'family' => 'Wilson',
      ],
    ];

    $result = $this->nameFormatter->formatList($items, 'default', 'et_al_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);

    $resultString = (string) $result;
    $this->assertStringContainsString('et al', $resultString);
    $this->assertStringContainsString('Alice', $resultString);
    $this->assertStringNotContainsString('Charlie', $resultString);
  }

  /**
   * Tests et al. is wrapped in emphasis when component markup is enabled.
   *
   * Covers FormattableMarkup for et al. when markup is not 'none'.
   */
  public function testFormatListEtAlUsesEmWhenMarkupEnabled(): void {
    $listFormat = NameListFormat::create([
      'id' => 'et_al_markup_test',
      'label' => 'Et Al Markup Test',
      'delimiter' => '; ',
      'and' => 'text',
      'delimiter_precedes_last' => 'always',
      'el_al_min' => 2,
      'el_al_first' => 1,
    ]);
    $listFormat->save();

    $items = [
      ['given' => 'Alice', 'family' => 'Brown'],
      ['given' => 'Bob', 'family' => 'Johnson'],
      ['given' => 'Charlie', 'family' => 'Wilson'],
    ];

    $this->nameFormatter->setSetting('markup', 'simple');
    $result = $this->nameFormatter->formatList($items, 'default', 'et_al_markup_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);

    $resultString = (string) $result;
    $this->assertStringContainsString('<em>', $resultString);
    $this->assertStringContainsString('et al', $resultString);
    $this->assertStringContainsString('</em>', $resultString);

    $this->nameFormatter->setSetting('markup', 'none');
    $resultPlain = $this->nameFormatter->formatList($items, 'default', 'et_al_markup_test');
    $plainString = (string) $resultPlain;
    $this->assertStringContainsString('et al', $plainString);
    $this->assertStringNotContainsString('<em>', $plainString);
  }

  /**
   * Tests NameListFormattableMarkup for multi-name et al. lists.
   *
   * Covers the @names@delimiter @etal branch when count($names) > 1.
   */
  public function testFormatListEtAlMultipleLeadingNames(): void {
    $listFormat = NameListFormat::create([
      'id' => 'et_al_multi_test',
      'label' => 'Et Al Multi Test',
      'delimiter' => ' | ',
      'and' => 'text',
      'delimiter_precedes_last' => 'always',
      'el_al_min' => 2,
      'el_al_first' => 2,
    ]);
    $listFormat->save();

    $items = [
      ['given' => 'Alice', 'family' => 'Adams'],
      ['given' => 'Bob', 'family' => 'Baker'],
      ['given' => 'Carol', 'family' => 'Cook'],
      ['given' => 'Dan', 'family' => 'Dale'],
    ];

    $result = $this->nameFormatter->formatList($items, 'default', 'et_al_multi_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);

    $resultString = (string) $result;
    $this->assertStringContainsString('Alice', $resultString);
    $this->assertStringContainsString('Adams', $resultString);
    $this->assertStringContainsString('Bob', $resultString);
    $this->assertStringContainsString('Baker', $resultString);
    $this->assertStringContainsString(' | ', $resultString);
    $this->assertStringContainsString('et al', $resultString);
    $this->assertStringNotContainsString('Carol', $resultString);
    $this->assertStringNotContainsString('Dan', $resultString);
  }

  /**
   * Tests formatting a name list with different delimiter behaviors.
   */
  public function testFormatListDelimiterBehaviors(): void {
    // Test 'never' behavior.
    $neverFormat = NameListFormat::create([
      'id' => 'never_test',
      'label' => 'Never Test Format',
      'delimiter' => ', ',
      'and' => 'text',
      'delimiter_precedes_last' => 'never',
    ]);
    $neverFormat->save();

    $items = [
      ['given' => 'John', 'family' => 'Smith'],
      ['given' => 'Jane', 'family' => 'Doe'],
    ];

    $result = $this->nameFormatter->formatList($items, 'default', 'never_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertStringContainsString('John Smith and Jane Doe', (string) $result);

    // Test 'always' behavior.
    $alwaysFormat = NameListFormat::create([
      'id' => 'always_test',
      'label' => 'Always Test Format',
      'delimiter' => ', ',
      'and' => 'text',
      'delimiter_precedes_last' => 'always',
    ]);
    $alwaysFormat->save();

    $result = $this->nameFormatter->formatList($items, 'default', 'always_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertStringContainsString('John Smith, and Jane Doe', (string) $result);
  }

  /**
   * Tests formatting a name list with symbol delimiter.
   */
  public function testFormatListWithSymbolDelimiter(): void {
    $symbolFormat = NameListFormat::create([
      'id' => 'symbol_test',
      'label' => 'Symbol Test Format',
      'delimiter' => '; ',
      'and' => 'symbol',
      'delimiter_precedes_last' => 'contextual',
    ]);
    $symbolFormat->save();

    $items = [
      ['given' => 'John', 'family' => 'Smith'],
      ['given' => 'Jane', 'family' => 'Doe'],
    ];

    $result = $this->nameFormatter->formatList($items, 'default', 'symbol_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertStringContainsString('John Smith & Jane Doe', (string) $result);
  }

  /**
   * Tests formatting a name list with contextual delimiter behavior.
   */
  public function testFormatListContextualDelimiter(): void {
    $contextualFormat = NameListFormat::create([
      'id' => 'contextual_test',
      'label' => 'Contextual Test Format',
      'delimiter' => ', ',
      'and' => 'text',
      'delimiter_precedes_last' => 'contextual',
    ]);
    $contextualFormat->save();

    // Test with 2 names (should not have comma before 'and').
    $items = [
      ['given' => 'John', 'family' => 'Smith'],
      ['given' => 'Jane', 'family' => 'Doe'],
    ];

    $result = $this->nameFormatter->formatList($items, 'default', 'contextual_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertStringContainsString('John Smith and Jane Doe', (string) $result);

    // Test with 3 names (should have comma before 'and').
    $items = [
      ['given' => 'John', 'family' => 'Smith'],
      ['given' => 'Jane', 'family' => 'Doe'],
      ['given' => 'Bob', 'family' => 'Johnson'],
    ];

    $result = $this->nameFormatter->formatList($items, 'default', 'contextual_test');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $this->assertStringContainsString('John Smith, Jane Doe, and Bob Johnson', (string) $result);
  }

  /**
   * Tests formatting a name list with inherit delimiter.
   */
  public function testFormatListInheritDelimiter(): void {
    $inheritFormat = NameListFormat::create([
      'id' => 'inherit_test',
      'label' => 'Inherit Test Format',
      'delimiter' => ' | ',
      'and' => 'inherit',
      'delimiter_precedes_last' => 'never',
    ]);
    $inheritFormat->save();

    $items = [
      ['given' => 'John', 'family' => 'Smith'],
      ['given' => 'Jane', 'family' => 'Doe'],
    ];

    $result = $this->nameFormatter->formatList($items, 'default', 'inherit_test');
    $this->assertInstanceOf(NameListFormattableMarkup::class, $result);
    $this->assertStringContainsString('John Smith | Jane Doe', (string) $result);
  }

  /**
   * Tests the getLastDelimiterTypes method.
   */
  public function testGetLastDelimiterTypes(): void {
    $types = $this->nameFormatter->getLastDelimiterTypes();
    $this->assertArrayHasKey('text', $types);
    $this->assertArrayHasKey('symbol', $types);
    $this->assertArrayHasKey('inherit', $types);

    $typesWithoutExamples = $this->nameFormatter->getLastDelimiterTypes(FALSE);
    $this->assertArrayHasKey('text', $typesWithoutExamples);
    $this->assertArrayHasKey('symbol', $typesWithoutExamples);
    $this->assertArrayHasKey('inherit', $typesWithoutExamples);
  }

  /**
   * Tests the getLastDelimiterBehaviors method.
   */
  public function testGetLastDelimiterBehaviors(): void {
    $behaviors = $this->nameFormatter->getLastDelimiterBehaviors();
    $this->assertArrayHasKey('never', $behaviors);
    $this->assertArrayHasKey('always', $behaviors);
    $this->assertArrayHasKey('contextual', $behaviors);

    $behaviorsWithoutExamples = $this->nameFormatter->getLastDelimiterBehaviors(FALSE);
    $this->assertArrayHasKey('never', $behaviorsWithoutExamples);
    $this->assertArrayHasKey('always', $behaviorsWithoutExamples);
    $this->assertArrayHasKey('contextual', $behaviorsWithoutExamples);
  }

  /**
   * Tests fallback to default format when custom format doesn't exist.
   */
  public function testFallbackToDefaultFormat(): void {
    $components = [
      'given' => 'John',
      'family' => 'Smith',
    ];

    // Fall back to the default format since 'nonexistent' doesn't exist.
    $result = $this->nameFormatter->format($components, 'nonexistent');
    // Fallback format returns a string.
    $this->assertIsString($result);
    $this->assertStringContainsString('John', $result);
    $this->assertStringContainsString('Smith', $result);
  }

  /**
   * Tests fallback to default list format when custom format doesn't exist.
   */
  public function testFallbackToDefaultListFormat(): void {
    $items = [
      ['given' => 'John', 'family' => 'Smith'],
      ['given' => 'Jane', 'family' => 'Doe'],
    ];

    // Fall back to the default list format since 'nonexistent' doesn't exist.
    $result = $this->nameFormatter->formatList($items, 'default', 'nonexistent');
    $this->assertInstanceOf(TranslatableMarkup::class, $result);
    $resultString = (string) $result;
    $this->assertStringContainsString('John', $resultString);
    $this->assertStringContainsString('Jane', $resultString);
  }

  /**
   * Tests empty name list handling.
   */
  public function testEmptyNameList(): void {
    $result = $this->nameFormatter->formatList([]);
    $this->assertEquals('', $result);
  }

  /**
   * Tests name formatting with different markup settings.
   */
  public function testFormatWithDifferentMarkup(): void {
    $components = [
      'title' => 'Dr.',
      'given' => 'John',
      'family' => 'Smith',
    ];

    // Test with simple markup.
    $this->nameFormatter->setSetting('markup', 'simple');
    $result = $this->nameFormatter->format($components);
    $this->assertIsString($result);

    // Test with raw markup.
    $this->nameFormatter->setSetting('markup', 'raw');
    $result = $this->nameFormatter->format($components);
    $this->assertInstanceOf(MarkupInterface::class, $result);

    // Reset to none.
    $this->nameFormatter->setSetting('markup', 'none');
    $result = $this->nameFormatter->format($components);
    $this->assertIsString($result);
  }

  /**
   * Tests raw markup mode strips script tags from formatted output.
   */
  public function testFormatWithRawMarkupSanitizesScript(): void {
    $components = [
      'given' => '<script>alert(1)</script><em>Alice</em>',
      'family' => 'Doe',
    ];

    $this->nameFormatter->setSetting('markup', 'raw');
    $result = $this->nameFormatter->format($components);

    $this->assertInstanceOf(MarkupInterface::class, $result);
    $this->assertStringNotContainsString('<script', (string) $result);
    $this->assertStringContainsString('<em>Alice</em>', (string) $result);
    $this->assertStringContainsString('Doe', (string) $result);
  }

  /**
   * Tests the deprecated getLastDelimitorTypes method.
   */
  public function testDeprecatedGetLastDelimitorTypes(): void {
    // cspell:ignore delimitor
    $this->expectDeprecation('getLastDelimitorTypes() is deprecated in name:8.x-1.1 and is removed from name:2.0.0. use getLastDelimiterTypes(). See https://www.drupal.org/project/name/issues/3518599');

    $types = $this->nameFormatter->getLastDelimitorTypes();
    $this->assertArrayHasKey('text', $types);
    $this->assertArrayHasKey('symbol', $types);
    $this->assertArrayHasKey('inherit', $types);
  }

  /**
   * Tests the deprecated getLastDelimitorBehaviors method.
   */
  public function testDeprecatedGetLastDelimitorBehaviors(): void {
    // cspell:ignore delimitor
    $this->expectDeprecation('getLastDelimitorBehaviors() is deprecated in name:8.x-1.1 and is removed from name:2.0.0. use getLastDelimiterBehaviors(). See https://www.drupal.org/project/name/issues/3518599');

    $behaviors = $this->nameFormatter->getLastDelimitorBehaviors();
    $this->assertArrayHasKey('never', $behaviors);
    $this->assertArrayHasKey('always', $behaviors);
    $this->assertArrayHasKey('contextual', $behaviors);
  }

}

<?php

namespace Drupal\Tests\name\Kernel\Plugin\migrate\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\name\Plugin\migrate\process\NameField;

/**
 * Tests the name_field migrate process plugin.
 *
 * @coversDefaultClass \Drupal\name\Plugin\migrate\process\NameField
 * @group name
 */
class NameFieldTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'migrate',
    'name',
    'system',
    'user',
  ];

  /**
   * The process plugin under test.
   *
   * @var \Drupal\name\Plugin\migrate\process\NameField
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');

    FieldStorageConfig::create([
      'field_name' => 'field_name_test',
      'entity_type' => 'user',
      'type' => 'name',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_name_test',
      'entity_type' => 'user',
      'type' => 'name',
      'bundle' => 'user',
    ])->save();

    $this->plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_name_test',
      ]);
  }

  /**
   * Helper to call transform on the plugin.
   */
  protected function transform(string $value): array {
    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = new Row();
    return $this->plugin->transform($value, $executable, $row, 'field_name_test');
  }

  /**
   * Invokes a protected method on the plugin for focused unit assertions.
   *
   * @param \Drupal\name\Plugin\migrate\process\NameField $plugin
   *   The migrate process plugin instance.
   * @param string $methodName
   *   The protected method name.
   * @param array $arguments
   *   Positional arguments for the method.
   *
   * @return mixed
   *   The invoked method return value.
   */
  protected function callProtectedNameFieldMethod(NameField $plugin, string $methodName, array $arguments = []): mixed {
    $method = new \ReflectionMethod(NameField::class, $methodName);
    $method->setAccessible(TRUE);
    return $method->invoke($plugin, ...$arguments);
  }

  /**
   * Tests that a single word is treated as a family name.
   *
   * @covers ::transform
   * @covers ::familyOnlyComponentsIfSingleWord
   */
  public function testSingleWord(): void {
    $this->assertSame(['family' => 'Smith'], $this->transform('Smith'));
  }

  /**
   * Tests a simple two-word name.
   *
   * @covers ::transform
   * @covers ::mergeStructuredNameOntoParsed
   */
  public function testSimpleName(): void {
    $this->assertSame(
      ['given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith'),
    );
  }

  /**
   * Tests a three-word name with a middle name.
   *
   * @covers ::transform
   */
  public function testThreeWordName(): void {
    $this->assertSame(
      ['given' => 'John', 'middle' => 'Michael', 'family' => 'Smith'],
      $this->transform('John Michael Smith'),
    );
  }

  /**
   * Tests that title is recognized from field configuration.
   *
   * @covers ::transform
   * @covers ::getComponentOptions
   */
  public function testTitleFromFieldConfig(): void {
    $this->assertSame(
      ['title' => 'Dr.', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('Dr. John Smith'),
    );
  }

  /**
   * Tests that Prof. title is recognized from field config, not hardcoded.
   *
   * @covers ::transform
   * @covers ::getComponentOptions
   */
  public function testTitleFromFieldConfigNotHardcoded(): void {
    $this->assertSame(
      ['title' => 'Prof.', 'given' => 'Jane', 'family' => 'Doe'],
      $this->transform('Prof. Jane Doe'),
    );
  }

  /**
   * Tests a generational suffix without a delimiter.
   *
   * @covers ::transform
   * @covers ::getComponentOptions
   */
  public function testGenerationalSuffix(): void {
    $this->assertSame(
      ['generational' => 'Jr.', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith Jr.'),
    );
  }

  /**
   * Tests that Sr. generational is recognized from field config, not hardcoded.
   *
   * @covers ::transform
   * @covers ::getComponentOptions
   */
  public function testGenerationalFromFieldConfigNotHardcoded(): void {
    $this->assertSame(
      ['generational' => 'Sr.', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith Sr.'),
    );
  }

  /**
   * Tests credentials delimited by a comma.
   *
   * @covers ::transform
   * @covers ::tryCommaGenerationalOrCredentialsSuffix
   */
  public function testCommaDelimitedCredentials(): void {
    $this->assertSame(
      ['credentials' => 'MD', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith, MD'),
    );
  }

  /**
   * Tests that comma-delimited generational is not treated as credentials.
   *
   * @covers ::transform
   * @covers ::getComponentOptions
   */
  public function testCommaDelimitedGenerational(): void {
    $this->assertSame(
      ['generational' => 'Jr.', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith, Jr.'),
    );
  }

  /**
   * Tests credentials in parentheses.
   *
   * @covers ::transform
   * @covers ::tryParentheticalCredentials
   */
  public function testParenthesizedCredentials(): void {
    $this->assertSame(
      ['credentials' => 'PhD', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith (PhD)'),
    );
  }

  /**
   * Tests a full name with title, middle, generational, and credentials.
   *
   * @covers ::transform
   */
  public function testFullNameAllComponents(): void {
    $this->assertSame(
      [
        'credentials' => 'MD',
        'title' => 'Dr.',
        'generational' => 'Jr.',
        'given' => 'John',
        'middle' => 'Michael',
        'family' => 'Smith',
      ],
      $this->transform('Dr. John Michael Smith Jr., MD'),
    );
  }

  /**
   * Tests that extra whitespace is normalized.
   *
   * @covers ::transform
   * @covers ::normalizeWhitespace
   */
  public function testExtraWhitespace(): void {
    $this->assertSame(
      ['given' => 'John', 'family' => 'Smith'],
      $this->transform('  John   Smith  '),
    );
  }

  /**
   * Tests that custom title options override field configuration.
   *
   * @covers ::getComponentOptions
   * @covers ::configuredComponentList
   */
  public function testCustomTitleOverridesFieldConfig(): void {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_name_test',
        'title' => ['Rev.'],
      ]);
    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = new Row();

    // Rev. is recognized because it was passed directly.
    $result = $plugin->transform('Rev. John Smith', $executable, $row, 'field_name_test');
    $this->assertSame('Rev.', $result['title']);

    // Dr. is no longer recognized because the override replaced field config.
    $result = $plugin->transform('Dr. John Smith', $executable, $row, 'field_name_test');
    $this->assertArrayNotHasKey('title', $result);
    $this->assertSame('Dr.', $result['given']);
  }

  /**
   * Tests the plugin without field configuration uses delimiter detection only.
   *
   * @covers ::transform
   */
  public function testWithoutFieldConfig(): void {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', []);
    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = new Row();

    // Comma-delimited credentials still work without field config.
    $result = $plugin->transform('John Smith, MD', $executable, $row, 'field_name_test');
    $this->assertSame('MD', $result['credentials']);
    $this->assertSame('John', $result['given']);
    $this->assertSame('Smith', $result['family']);

    // But titles are not recognized without field config.
    $result = $plugin->transform('Dr. John Smith', $executable, $row, 'field_name_test');
    $this->assertArrayNotHasKey('title', $result);
    $this->assertSame('Dr.', $result['given']);
  }

  /**
   * @covers ::normalizeWhitespace
   */
  public function testNormalizeWhitespaceViaReflection(): void {
    $this->assertSame(
      'John Smith',
      $this->callProtectedNameFieldMethod($this->plugin, 'normalizeWhitespace', ['  John   Smith  ']),
    );
  }

  /**
   * @covers ::familyOnlyComponentsIfSingleWord
   */
  public function testFamilyOnlyComponentsIfSingleWordViaReflection(): void {
    $this->assertSame(
      ['family' => 'Smith'],
      $this->callProtectedNameFieldMethod($this->plugin, 'familyOnlyComponentsIfSingleWord', ['Smith']),
    );
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'familyOnlyComponentsIfSingleWord', ['John Smith']),
    );
  }

  /**
   * @covers ::tryParentheticalCredentials
   */
  public function testTryParentheticalCredentialsViaReflection(): void {
    $expected = [
      'parsed' => ['credentials' => 'PhD'],
      'remainder' => 'John Smith ',
    ];
    $this->assertSame(
      $expected,
      $this->callProtectedNameFieldMethod($this->plugin, 'tryParentheticalCredentials', ['John Smith (PhD)']),
    );
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryParentheticalCredentials', ['John Smith']),
    );
  }

  /**
   * @covers ::tryCommaThenSlashTrailingCredentials
   */
  public function testTryCommaThenSlashTrailingCredentialsViaReflection(): void {
    $expected = [
      'parsed' => ['credentials' => 'RN / BSN'],
      'remainder' => 'Jane Doe',
    ];
    $this->assertSame(
      $expected,
      $this->callProtectedNameFieldMethod($this->plugin, 'tryCommaThenSlashTrailingCredentials', ['Jane Doe, RN / BSN']),
    );
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryCommaThenSlashTrailingCredentials', ['Jane/Roe']),
    );
  }

  /**
   * @covers ::transform
   * @covers ::tryLeadingCredentialWord
   */
  public function testLeadingConfiguredCredentialWord(): void {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_name_test',
        'credentials' => ['MD', 'PhD'],
      ]);
    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = new Row();
    $result = $plugin->transform('MD John Smith', $executable, $row, 'field_name_test');
    $this->assertSame(
      ['credentials' => 'MD', 'given' => 'John', 'family' => 'Smith'],
      $result,
    );
  }

  /**
   * @covers ::tryLeadingCredentialWord
   */
  public function testTryLeadingCredentialWordViaReflection(): void {
    $expected = [
      'parsed' => ['credentials' => 'MD'],
      'remainder' => ' John Smith',
    ];
    $this->assertSame(
      $expected,
      $this->callProtectedNameFieldMethod($this->plugin, 'tryLeadingCredentialWord', ['MD John Smith', ['MD']]),
    );
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryLeadingCredentialWord', ['John Smith', ['MD']]),
    );
  }

  /**
   * @covers ::trySlashTrailingCredentials
   */
  public function testSlashDelimitedCredentials(): void {
    $this->assertSame(
      ['credentials' => 'MD', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith/MD'),
    );
  }

  /**
   * @covers ::trySlashTrailingCredentials
   */
  public function testTrySlashTrailingCredentialsViaReflection(): void {
    $expected = [
      'parsed' => ['credentials' => 'MD'],
      'remainder' => 'John Smith',
    ];
    $this->assertSame(
      $expected,
      $this->callProtectedNameFieldMethod($this->plugin, 'trySlashTrailingCredentials', ['John Smith/MD']),
    );
  }

  /**
   * @covers ::trySpacedDashTrailingCredentials
   */
  public function testSpacedDashDelimitedCredentials(): void {
    $this->assertSame(
      ['credentials' => 'MD', 'given' => 'John', 'family' => 'Smith'],
      $this->transform('John Smith - MD'),
    );
  }

  /**
   * @covers ::trySpacedDashTrailingCredentials
   */
  public function testTrySpacedDashTrailingCredentialsViaReflection(): void {
    $expected = [
      'parsed' => ['credentials' => 'MD'],
      'remainder' => 'John Smith',
    ];
    $this->assertSame(
      $expected,
      $this->callProtectedNameFieldMethod($this->plugin, 'trySpacedDashTrailingCredentials', ['John Smith - MD']),
    );
  }

  /**
   * @covers ::trySpacedDashTrailingCredentials
   */
  public function testTrySpacedDashTrailingCredentialsWithoutDashDelimiter(): void {
    $this->assertNull(
      $this->callProtectedNameFieldMethod(
        $this->plugin,
        'trySpacedDashTrailingCredentials',
        ['John Smith MD'],
      ),
    );
  }

  /**
   * @covers ::tryCommaGenerationalOrCredentialsSuffix
   */
  public function testTryCommaGenerationalOrCredentialsSuffixViaReflection(): void {
    $generational = ['Jr.', 'Sr.'];
    $this->assertSame(
      [
        'parsed' => ['generational' => 'Jr.'],
        'remainder' => 'John Smith',
      ],
      $this->callProtectedNameFieldMethod(
        $this->plugin,
        'tryCommaGenerationalOrCredentialsSuffix',
        ['John Smith, Jr.', $generational],
      ),
    );
    $this->assertSame(
      [
        'parsed' => ['credentials' => 'MD'],
        'remainder' => 'John Smith',
      ],
      $this->callProtectedNameFieldMethod(
        $this->plugin,
        'tryCommaGenerationalOrCredentialsSuffix',
        ['John Smith, MD', $generational],
      ),
    );
  }

  /**
   * @covers ::tryTrailingCredentialWord
   */
  public function testTrailingConfiguredCredentialWord(): void {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_name_test',
        'credentials' => ['MD', 'PhD'],
      ]);
    $executable = $this->createMock(MigrateExecutableInterface::class);
    $row = new Row();
    $result = $plugin->transform('John Smith MD', $executable, $row, 'field_name_test');
    $this->assertSame(
      ['credentials' => 'MD', 'given' => 'John', 'family' => 'Smith'],
      $result,
    );
  }

  /**
   * @covers ::tryTrailingCredentialWord
   */
  public function testTryTrailingCredentialWordViaReflection(): void {
    $expected = [
      'parsed' => ['credentials' => 'MD'],
      'remainder' => 'John Smith',
    ];
    $this->assertSame(
      $expected,
      $this->callProtectedNameFieldMethod($this->plugin, 'tryTrailingCredentialWord', ['John Smith MD', ['MD']]),
    );
  }

  /**
   * @covers ::extractCredentialOrCommaSuffix
   */
  public function testExtractCredentialOrCommaSuffixReturnsEmptyWhenNoDelimiterMatches(): void {
    $this->assertSame(
      [[], 'John Smith'],
      $this->callProtectedNameFieldMethod($this->plugin, 'extractCredentialOrCommaSuffix', [
        'John Smith',
        [],
        [],
      ]),
    );
  }

  /**
   * @covers ::mergeStructuredNameOntoParsed
   */
  public function testMergeStructuredNameOntoParsedViaReflection(): void {
    $merged = $this->callProtectedNameFieldMethod($this->plugin, 'mergeStructuredNameOntoParsed', [
      ['credentials' => 'MD'],
      'Dr. John Michael Smith Jr.',
      ['Dr.'],
      ['Jr.'],
    ]);
    $this->assertSame(
      [
        'credentials' => 'MD',
        'title' => 'Dr.',
        'generational' => 'Jr.',
        'given' => 'John',
        'middle' => 'Michael',
        'family' => 'Smith',
      ],
      $merged,
    );
  }

  /**
   * @covers ::getComponentOptions
   * @covers ::fieldSettingsComponentList
   */
  public function testFieldSettingsComponentListReturnsTitleOptions(): void {
    $titles = $this->callProtectedNameFieldMethod($this->plugin, 'fieldSettingsComponentList', ['title']);
    $this->assertContains('Dr.', $titles);
  }

  /**
   * @covers ::optionListWithoutEmptyPlaceholders
   */
  public function testOptionListWithoutEmptyPlaceholdersViaReflection(): void {
    $filtered = $this->callProtectedNameFieldMethod($this->plugin, 'optionListWithoutEmptyPlaceholders', [
      ['--', 'Dr.', 'Prof.', '-- --'],
    ]);
    $this->assertSame(['Dr.', 'Prof.'], $filtered);
  }

  /**
   * @covers ::configuredComponentList
   */
  public function testConfiguredComponentListViaReflection(): void {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_name_test',
        'credentials' => ['PE'],
      ]);
    $this->assertSame(
      ['PE'],
      $this->callProtectedNameFieldMethod($plugin, 'configuredComponentList', ['credentials']),
    );
    $this->assertNull(
      $this->callProtectedNameFieldMethod($plugin, 'configuredComponentList', ['generational']),
    );
  }

  /**
   * @covers ::__construct
   * @covers ::create
   */
  public function testPluginCreateInjectsEntityFieldManager(): void {
    $plugin_manager = \Drupal::service('plugin.manager.migrate.process');
    $plugin_definition = $plugin_manager->getDefinition('name_field');
    $plugin = NameField::create($this->container, [], 'name_field', $plugin_definition);
    $this->assertInstanceOf(NameField::class, $plugin);
  }

  /**
   * @covers ::extractCredentialOrCommaSuffix
   * @covers ::tryParentheticalCredentials
   */
  public function testExtractCredentialOrCommaSuffixReturnsParsedWhenParentheticalMatches(): void {
    $expected_parsed = ['credentials' => 'PhD'];
    $expected_remainder = 'John Smith ';
    $credential_lexicon = ['MD'];
    $generational_lexicon = ['Jr.'];
    $parts = $this->callProtectedNameFieldMethod($this->plugin, 'extractCredentialOrCommaSuffix', [
      'John Smith (PhD)',
      $credential_lexicon,
      $generational_lexicon,
    ]);
    $this->assertSame([$expected_parsed, $expected_remainder], $parts);
  }

  /**
   * @covers ::tryLeadingCredentialWord
   */
  public function testTryLeadingCredentialWordEmptyLexicon(): void {
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryLeadingCredentialWord', [
        'MD John Smith',
        [],
      ]),
    );
  }

  /**
   * @covers ::tryLeadingCredentialWord
   */
  public function testTryLeadingCredentialWordSingleTokenNoSpace(): void {
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryLeadingCredentialWord', [
        'MD',
        ['MD'],
      ]),
    );
  }

  /**
   * @covers ::trySlashTrailingCredentials
   */
  public function testTrySlashTrailingCredentialsNoSlash(): void {
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'trySlashTrailingCredentials', [
        'John Smith',
      ]),
    );
  }

  /**
   * @covers ::tryCommaGenerationalOrCredentialsSuffix
   */
  public function testTryCommaGenerationalOrCredentialsSuffixNoComma(): void {
    $generational_lexicon = ['Jr.'];
    $this->assertNull(
      $this->callProtectedNameFieldMethod(
        $this->plugin,
        'tryCommaGenerationalOrCredentialsSuffix',
        ['John Smith', $generational_lexicon],
      ),
    );
  }

  /**
   * @covers ::tryTrailingCredentialWord
   */
  public function testTryTrailingCredentialWordEmptyLexicon(): void {
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryTrailingCredentialWord', [
        'John Smith MD',
        [],
      ]),
    );
  }

  /**
   * @covers ::tryTrailingCredentialWord
   */
  public function testTryTrailingCredentialWordLastTokenNotInLexicon(): void {
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryTrailingCredentialWord', [
        'John Smith MD',
        ['PhD'],
      ]),
    );
  }

  /**
   * @covers ::tryTrailingCredentialWord
   */
  public function testTryTrailingCredentialWordSingleTokenNoSpace(): void {
    $this->assertNull(
      $this->callProtectedNameFieldMethod($this->plugin, 'tryTrailingCredentialWord', [
        'MD',
        ['MD'],
      ]),
    );
  }

  /**
   * @covers ::fieldSettingsComponentList
   */
  public function testFieldSettingsComponentListMissingDestinationKeys(): void {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', []);
    $titles = $this->callProtectedNameFieldMethod($plugin, 'fieldSettingsComponentList', ['title']);
    $this->assertSame([], $titles);
  }

  /**
   * @covers ::fieldSettingsComponentList
   */
  public function testFieldSettingsComponentListUnknownFieldName(): void {
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('name_field', [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_does_not_exist',
      ]);
    $titles = $this->callProtectedNameFieldMethod($plugin, 'fieldSettingsComponentList', ['title']);
    $this->assertSame([], $titles);
  }

  /**
   * @covers ::fieldSettingsComponentList
   */
  public function testFieldSettingsComponentListEmptyTitleOptions(): void {
    $field_config = FieldConfig::load('user.user.field_name_test');
    $this->assertNotNull($field_config);
    $original_settings = $field_config->getSettings();
    try {
      $updated_settings = $original_settings;
      $updated_settings['title_options'] = [];
      $field_config->setSettings($updated_settings);
      $field_config->save();
      $titles = $this->callProtectedNameFieldMethod(
        $this->plugin,
        'fieldSettingsComponentList',
        ['title'],
      );
      $this->assertSame([], $titles);
    }
    finally {
      $field_config->setSettings($original_settings);
      $field_config->save();
    }
  }

}

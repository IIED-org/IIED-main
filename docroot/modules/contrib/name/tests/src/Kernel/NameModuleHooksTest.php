<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\name\Hook\TokenHooks;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Direct coverage for procedural hooks in name.module.
 *
 * @group name
 */
class NameModuleHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'entity_test',
    'node',
    'system',
    'text',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'name', 'node']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    $this->container->get('entity_type.listener')
      ->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('entity_test'));

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    drupal_static_reset();
  }

  /**
   * @covers \name_help
   */
  public function testHelpReturnsPageHelpAndDefaultEmptyString(): void {
    $route_match = $this->createMock(RouteMatchInterface::class);

    $output = name_help('help.page.name', $route_match);
    $this->assertStringContainsString('stores a person\'s name in parts', $output);
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.formats">',
      $output,
    );

    $output = name_help('name.name_format_list', $route_match);
    $this->assertStringContainsString('Name formats control', $output);
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.formats">',
      $output,
    );

    $output = name_help('name.name_list_format_list', $route_match);
    $this->assertStringContainsString('Name list formats control', $output);
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.list_formats">',
      $output,
    );

    $output = name_help('name.settings', $route_match);
    $this->assertStringContainsString('separator replacement tokens', $output);

    $field_config = $this->createMock(FieldConfigInterface::class);
    $field_config->method('getType')->willReturn('name');
    $field_config->method('getTargetEntityTypeId')->willReturn('user');
    $field_route_match = $this->createMock(RouteMatchInterface::class);
    $field_route_match->method('getParameter')
      ->with('field_config')
      ->willReturn($field_config);

    $output = name_help('entity.field_config.user_field_edit_form', $field_route_match);
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.field_settings">',
      $output,
    );
    $this->assertStringContainsString(
      '<a href="/admin/help/topic/name.user_display_name">',
      $output,
    );

    $this->assertSame('', name_help('name.noop_route', $route_match));
  }

  /**
   * @covers \name_name_widget_layouts
   */
  public function testNameWidgetLayoutsReturnsExpectedLayouts(): void {
    $layouts = name_name_widget_layouts();

    $this->assertArrayHasKey('stacked', $layouts);
    $this->assertArrayHasKey('inline', $layouts);
    $this->assertSame('name/widget.inline', $layouts['inline']['library'][0]);
    $this->assertSame(
      ['form--inline', 'clearfix'],
      $layouts['inline']['wrapper_attributes']['class'],
    );
  }

  /**
   * @covers \name_theme
   */
  public function testThemeDefinitionsDoNotReferenceIncFiles(): void {
    $theme = name_theme();

    $this->assertArrayHasKey('name_item', $theme);
    $this->assertArrayHasKey('name_item_list', $theme);
    $this->assertArrayHasKey('name', $theme);
    $this->assertArrayNotHasKey('file', $theme['name_item']);
    $this->assertArrayNotHasKey('file', $theme['name_item_list']);
    $this->assertArrayNotHasKey('file', $theme['name']);
    $this->assertSame('element', $theme['name']['render element']);
  }

  /**
   * @covers \template_preprocess_name_item
   */
  public function testTemplatePreprocessNameItemFormatsName(): void {
    $format_options = $this->container->get('name.format_options');
    $expected = $this->container->get('name.format_parser')->parse(
      ['given' => 'Pat', 'family' => 'Smith'],
      $format_options->getFormatPatternByMachineName('default'),
      ['markup' => 0],
    );

    $variables = [
      'item' => [
        'given' => 'Pat',
        'family' => 'Smith',
      ],
      'format' => 'default',
      'settings' => [],
    ];

    template_preprocess_name_item($variables);

    $this->assertSame(0, $variables['settings']['markup']);
    $this->assertSame($expected, $variables['formatted_name']);
  }

  /**
   * @covers \template_preprocess_name_item_list
   */
  public function testTemplatePreprocessNameItemFallsBackToDefaultFormatWhenFormatIdIsNull(): void {
    $format_options = $this->container->get('name.format_options');
    $expected = $this->container->get('name.format_parser')->parse(
      ['given' => 'Pat', 'family' => 'Fallback'],
      $format_options->getFormatPatternByMachineName('default'),
      ['markup' => 0],
    );

    $variables = [
      'item' => [
        'given' => 'Pat',
        'family' => 'Fallback',
      ],
      'format' => NULL,
      'settings' => [],
    ];

    template_preprocess_name_item($variables);

    $this->assertSame($expected, $variables['formatted_name']);
  }

  /**
   * @covers \template_preprocess_name_item_list
   */
  public function testTemplatePreprocessNameItemListHandlesSingleAndEtAl(): void {
    $single = [
      'items' => ['Pat Smith'],
      'settings' => [],
    ];
    template_preprocess_name_item_list($single);
    $this->assertSame(1, $single['original_count']);
    $this->assertSame('Pat Smith', $single['item']);

    $multiple = [
      'items' => ['Pat Smith', 'Jo Smith', 'Lee Smith', 'Sam Smith'],
      'settings' => [],
    ];
    template_preprocess_name_item_list($multiple);

    $this->assertSame(4, $multiple['original_count']);
    $this->assertSame(', ', $multiple['delimiter']);
    $this->assertSame(1, $multiple['items_count']);
    $this->assertSame('Pat Smith', $multiple['name']);
    $this->assertSame('<em>et al</em>', (string) $multiple['etal']);
  }

  /**
   * @covers \name_user_format_name_alter
   */
  public function testTemplatePreprocessNameItemListPlainOutputStripsTagsFromDelimiter(): void {
    $variables = [
      'items' => ['Pat Smith', 'Jo Smith'],
      'settings' => [
        'output' => 'plain',
        'multiple_delimiter' => '<b>, </b>',
      ],
    ];

    template_preprocess_name_item_list($variables);

    $this->assertSame(', ', $variables['delimiter']);
    $this->assertSame('et al', (string) $variables['etal']);
  }

  /**
   * @covers \template_preprocess_name_item_list
   */
  public function testTemplatePreprocessNameItemListRawOutputLeavesDelimiterUnchanged(): void {
    $variables = [
      'items' => ['Pat Smith', 'Jo Smith'],
      'settings' => [
        'output' => 'raw',
        'multiple_delimiter' => '<b>, </b>',
      ],
    ];

    template_preprocess_name_item_list($variables);

    $this->assertSame('<b>, </b>', $variables['delimiter']);
    $this->assertSame('et al', (string) $variables['etal']);
  }

  /**
   * @covers \template_preprocess_name_item_list
   */
  public function testTemplatePreprocessNameItemListEtAlWithMultipleFirstNamesImplodes(): void {
    $variables = [
      'items' => [
        'Pat Smith',
        'Jo Smith',
        'Lee Smith',
        'Sam Smith',
        'Kim Smith',
        'Ari Smith',
      ],
      'settings' => [
        'multiple_el_al_min' => 3,
        'multiple_el_al_first' => 3,
      ],
    ];

    template_preprocess_name_item_list($variables);

    $this->assertSame(3, $variables['items_count']);
    $this->assertSame('Pat Smith,  Jo Smith,  Lee Smith', $variables['names']);
  }

  /**
   * @covers \template_preprocess_name_item_list
   */
  public function testTemplatePreprocessNameItemListAndSymbolIsAmpHtmlEntityForDefaultOutput(): void {
    $variables = [
      'items' => ['Pat Smith', 'Jo Smith'],
      'settings' => [
        'output' => 'default',
        'multiple_and' => 'symbol',
      ],
    ];

    template_preprocess_name_item_list($variables);

    $this->assertSame('&amp', $variables['and_']);
  }

  /**
   * @covers \template_preprocess_name_item_list
   */
  public function testTemplatePreprocessNameItemListAndSymbolIsRawAmpForNonDefaultOutput(): void {
    $variables = [
      'items' => ['Pat Smith', 'Jo Smith'],
      'settings' => [
        'output' => 'plain',
        'multiple_and' => 'symbol',
      ],
    ];

    template_preprocess_name_item_list($variables);

    $this->assertSame('&', $variables['and_']);
  }

  /**
   * @covers \name_user_format_name_alter
   */
  public function testUserFormatNameAlterUsesStaticCache(): void {
    drupal_static_reset('name_user_realname_cache');
    $account = User::create([
      'name' => 'cached-user',
      'mail' => 'cached@example.com',
    ]);
    $account->save();

    $cache = &drupal_static('name_user_realname_cache', []);
    $cache[$account->id()] = 'Cached Realname';

    $name = 'Original';
    name_user_format_name_alter($name, $account);

    $this->assertSame('Cached Realname', $name);
    $this->assertSame('Cached Realname', $account->realname);
  }

  /**
   * @covers \name_user_format_name_alter
   */
  public function testUserFormatNameAlterLeavesAnonymousUserUnchanged(): void {
    $name = 'Anonymous';
    name_user_format_name_alter($name, new AnonymousUserSession());

    $this->assertSame('Anonymous', $name);
  }

  /**
   * @covers \name_user_load
   */
  public function testUserLoadFormatsAndCachesConfiguredPreferredField(): void {
    drupal_static_reset();
    $field = $this->installNameField(
      'field_name_user',
      'user',
      'user',
      [],
      [
        'preferred_field_reference' => '_self_property_name',
        'preferred_field_reference_separator' => ', ',
      ],
    );

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', $field->getName())
      ->save();

    $account = User::create([
      'name' => 'kernel-user',
      'mail' => 'kernel-user@example.com',
      'field_name_user' => [
        'given' => 'Pat',
        'family' => 'Smith',
      ],
    ]);
    $account->save();

    $loaded = User::load($account->id());
    $users = [$loaded->id() => $loaded];
    name_user_load($users);

    $this->assertSame('Pat Smith', $loaded->realname);

    $reloaded = User::load($account->id());
    $cache = &drupal_static('name_user_realname_cache', []);
    $cache[$reloaded->id()] = 'Cached Once';
    $users = [$reloaded->id() => $reloaded];
    name_user_load($users);

    $this->assertSame('Cached Once', $reloaded->realname);
  }

  /**
   * @covers \name_user_save
   */
  public function testUserSaveClearsCachedRealname(): void {
    $account = User::create([
      'name' => 'saved-user',
      'mail' => 'saved-user@example.com',
    ]);
    $account->save();

    $cache = &drupal_static('name_user_realname_cache', []);
    $cache[$account->id()] = 'To clear';

    name_user_save($account);

    $this->assertArrayNotHasKey($account->id(), $cache);
  }

  /**
   * @covers \name_field_config_create
   */
  public function testFieldConfigCreateSetsInitialUserPreferredField(): void {
    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', '')
      ->save();

    $field = $this->buildUnsavedUserNameField('field_name_created');
    name_field_config_create($field);

    $this->assertSame(
      'field_name_created',
      \Drupal::config('name.settings')->get('user_preferred'),
    );
  }

  /**
   * @covers \name_field_config_delete
   */
  public function testFieldConfigDeleteClearsPreferredUserField(): void {
    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('user_preferred', 'field_name_deleted')
      ->save();

    $field = $this->buildUnsavedUserNameField('field_name_deleted');
    name_field_config_delete($field);

    $this->assertSame('', \Drupal::config('name.settings')->get('user_preferred'));
  }

  /**
   * @covers \name_module_implements_alter
   */
  public function testModuleImplementsAlterMovesNameToEndForTokenInfo(): void {
    $implementations = [
      'alpha' => FALSE,
      'name' => TRUE,
      'zeta' => FALSE,
    ];

    name_module_implements_alter($implementations, 'token_info_alter');
    $this->assertSame(['alpha', 'zeta', 'name'], array_keys($implementations));

    $unchanged = $implementations;
    name_module_implements_alter($unchanged, 'tokens');
    $this->assertSame($implementations, $unchanged);
  }

  /**
   * @covers \name_token_info_alter
   */
  public function testTokenInfoAlterRegistersFormattedNameTokens(): void {
    $this->installNameField('field_name_test', 'entity_test', 'entity_test');

    $info = [
      'types' => [],
      'tokens' => [
        'entity_test' => [],
      ],
    ];

    name_token_info_alter($info);

    $chain = 'name_formatted|entity_test|field_name_test';
    $this->assertArrayHasKey($chain, $info['types']);
    $this->assertArrayHasKey(
      'formatted_field_name_test',
      $info['tokens']['entity_test'],
    );
  }

  /**
   * @covers \name_token_info_alter
   */
  public function testTokenInfoAlterReturnsEarlyWhenTokenServiceIsNull(): void {
    $original_token = $this->container->get(
      TokenHooks::class,
      ContainerInterface::NULL_ON_INVALID_REFERENCE,
    );
    $this->container->set(TokenHooks::class, NULL);
    \Drupal::setContainer($this->container);

    $info = [
      'types' => [],
      'tokens' => ['entity_test' => []],
    ];
    name_token_info_alter($info);

    $this->assertSame([], $info['types']);
    $this->assertSame([], $info['tokens']['entity_test']);

    $this->container->set(TokenHooks::class, $original_token);
    \Drupal::setContainer($this->container);
  }

  /**
   * @covers \name_tokens
   */
  public function testTokensProxyDelegatesToTokenHookService(): void {
    $this->installNameField('field_name_test', 'entity_test', 'entity_test');
    $entity = $this->createEntityTestWithName('field_name_test');
    $bubbleable_metadata = new BubbleableMetadata();

    $expected = $this->container->get(TokenHooks::class)->getChainReplacements(
      'name_formatted|entity_test|field_name_test',
      ['given' => '[placeholder]'],
      ['entity_test' => $entity],
      ['langcode' => 'en'],
      $bubbleable_metadata,
    );

    $actual = name_tokens(
      'name_formatted|entity_test|field_name_test',
      ['given' => '[placeholder]'],
      ['entity_test' => $entity],
      ['langcode' => 'en'],
      new BubbleableMetadata(),
    );

    $this->assertSame($expected, $actual);
  }

  /**
   * @covers \name_tokens
   */
  public function testTokensReturnsEmptyArrayWhenTokenServiceIsNull(): void {
    $original_token = $this->container->get(
      TokenHooks::class,
      ContainerInterface::NULL_ON_INVALID_REFERENCE,
    );
    $this->container->set(TokenHooks::class, NULL);
    \Drupal::setContainer($this->container);

    $actual = name_tokens(
      'entity_test',
      ['given' => '[placeholder]'],
      [],
      [],
      new BubbleableMetadata(),
    );
    $this->assertSame([], $actual);

    $this->container->set(TokenHooks::class, $original_token);
    \Drupal::setContainer($this->container);
  }

  /**
   * @covers \name_tokens_alter
   */
  public function testTokensAlterProxyDelegatesToTokenHookService(): void {
    $this->installNameField('field_name_test', 'entity_test', 'entity_test');
    $entity = $this->createEntityTestWithName('field_name_test');
    $context = [
      'type' => 'entity_test',
      'tokens' => [
        'field_name_test:formatted:given' => '[legacy]',
      ],
      'data' => ['entity_test' => $entity],
      'options' => ['langcode' => 'en'],
    ];

    $expected = [];
    $this->container->get(TokenHooks::class)->alterReplacements(
      $expected,
      $context,
      new BubbleableMetadata(),
    );

    $actual = [];
    name_tokens_alter($actual, $context, new BubbleableMetadata());

    $this->assertSame($expected, $actual);
  }

  /**
   * @covers \name_tokens_alter
   */
  public function testTokensAlterReturnsEarlyWhenTokenServiceIsNull(): void {
    $original_token = $this->container->get(
      TokenHooks::class,
      ContainerInterface::NULL_ON_INVALID_REFERENCE,
    );
    $this->container->set(TokenHooks::class, NULL);
    \Drupal::setContainer($this->container);

    $replacements = ['existing' => 'value'];
    $context = [
      'type' => 'entity_test',
      'tokens' => [],
      'data' => [],
      'options' => [],
    ];
    name_tokens_alter($replacements, $context, new BubbleableMetadata());
    $this->assertSame(['existing' => 'value'], $replacements);

    $this->container->set(TokenHooks::class, $original_token);
    \Drupal::setContainer($this->container);
  }

  /**
   * @covers \name_field_views_data
   */
  public function testFieldViewsDataAddsFulltextFilterAndSubfields(): void {
    $storage = $this->installNameField('field_name_views', 'node', 'page')
      ->getFieldStorageDefinition();

    $data = name_field_views_data($storage);

    $this->assertNotEmpty($data);
    $table_name = array_key_first($data);
    $this->assertSame(
      'name_fulltext',
      $data[$table_name]['field_name_views']['filter']['id'],
    );
    $this->assertSame(
      'given',
      $data[$table_name]['field_name_views_given']['field']['property'],
    );
    $this->assertSame(
      'family',
      $data[$table_name]['field_name_views_family']['field']['property'],
    );
  }

  /**
   * @covers \name_field_type_category_info_alter
   */
  public function testFieldTypeCategoryInfoAlterAddsFieldUiLibrary(): void {
    $definitions = [
      FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY => [
        'libraries' => [],
      ],
    ];

    name_field_type_category_info_alter($definitions);

    $this->assertContains(
      'name/field_ui',
      $definitions[FieldTypeCategoryManagerInterface::FALLBACK_CATEGORY]['libraries'],
    );
  }

  /**
   * Installs a configurable name field.
   */
  private function installNameField(
    string $field_name,
    string $entity_type,
    string $bundle,
    array $storage_settings = [],
    array $field_settings = [],
    int $cardinality = 1,
  ): FieldConfigInterface {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'name',
      'cardinality' => $cardinality,
      'settings' => $storage_settings,
    ]);
    $storage->save();

    $field = FieldConfig::create([
      'field_storage' => $storage,
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'settings' => $field_settings,
    ]);
    $field->save();

    return $field;
  }

  /**
   * Builds an unsaved user name field config.
   */
  private function buildUnsavedUserNameField(string $field_name): FieldConfigInterface {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'user',
      'type' => 'name',
    ]);
    $storage->save();

    return FieldConfig::create([
      'field_storage' => $storage,
      'field_name' => $field_name,
      'entity_type' => 'user',
      'bundle' => 'user',
    ]);
  }

  /**
   * Creates a saved entity_test entity with a name field value.
   */
  private function createEntityTestWithName(string $field_name): EntityTest {
    $entity = EntityTest::create([
      'name' => 'Entity test record',
      $field_name => [
        'given' => 'Kernel',
        'family' => 'Token',
      ],
    ]);
    $entity->save();

    return $entity;
  }

}

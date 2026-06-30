<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Element\Name;
use Drupal\name\NameUnicodeExtras;
use Drupal\name\Utility\NameComponents;
use Drupal\name\Utility\UnicodeExtras;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Parity tests for deprecated procedural helpers in name.module (288–617).
 *
 * @group name
 * @group legacy
 */
class NameModuleLegacyProceduralTest extends KernelTestBase {

  private const DEPRECATION_NAME_TRANSLATIONS = '_name_translations() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.component_metadata\')->getTranslations() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_NAME_COMPONENT_KEYS = '_name_component_keys() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Utility\NameComponents::coreKeys() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_FORMATTER_OUTPUT_TYPES = '_name_formatter_output_types() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.component_metadata\')->getFormatterOutputTypes() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_FORMATTER_OUTPUT_OPTIONS = '_name_formatter_output_options() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.component_metadata\')->getFormatterOutputOptions() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_COMPONENT_LAYOUT = '_name_component_layout() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Utility\NameComponents::applyLayout() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_WIDGET_LAYOUTS = 'name_widget_layouts() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.widget_layouts\')->getLayouts() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_USER_REALNAME_PRELOAD = 'name_user_format_name_alter_preload() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.user_realname_preload\')->preload() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_ADDITIONAL_COMPONENT = 'name_get_additional_component() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.additional_component\')->getAdditionalComponent() instead. The entity type manager and renderer arguments are ignored. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_CUSTOM_FORMAT_OPTIONS = 'name_get_custom_format_options() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.format_options\')->getCustomFormatOptions() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_CUSTOM_LIST_FORMAT_OPTIONS = 'name_get_custom_list_format_options() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.format_options\')->getCustomListFormatOptions() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_FORMAT_BY_MACHINE_NAME = 'name_get_format_by_machine_name() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal::service(\'name.format_options\')->getFormatPatternByMachineName() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_ELEMENT_EXPAND = 'name_element_expand() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Element\Name::process() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_ELEMENT_RENDER_COMPONENT = 'name_element_render_component() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Element\Name::renderComponent() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_COMPONENT_DESCRIPTION_ALTER = 'name_component_description_after_build_label_alter() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Element\Name::componentDescriptionAfterBuildLabelAlter() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_ELEMENT_VALIDATE = 'name_element_validate() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Element\Name::validateElement() or the name.element_validator service instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_ELEMENT_PRE_RENDER = 'name_element_pre_render() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Element\Name::preRender() instead. See https://www.drupal.org/project/name/issues/3128409';

  private const DEPRECATION_ELEMENT_VALIDATE_IS_EMPTY = 'name_element_validate_is_empty() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Element\Name::validateIsEmpty() instead. See https://www.drupal.org/project/name/issues/3555260';

  private const DEPRECATION_VALUE_SANITIZE = '_name_value_sanitize() is deprecated in name:8.x-1.3 and is removed from name:2.0.0. Use \Drupal\name\Utility\NameComponents::sanitizeValue() instead. See https://www.drupal.org/project/name/issues/3555260';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
    'node',
    'text',
  ];

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected ElementInfoManagerInterface $elementInfoManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    $fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_name_acs',
      'entity_type' => 'node',
      'type' => 'name',
      'settings' => [
        'components' => [
          'title' => TRUE,
          'given' => TRUE,
          'middle' => TRUE,
          'family' => TRUE,
          'generational' => FALSE,
          'credentials' => FALSE,
        ],
      ],
    ]);
    $fieldStorage->save();

    $fieldConfig = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => 'page',
      'label' => 'Name',
      'settings' => [
        'components' => [
          'title' => TRUE,
          'given' => TRUE,
          'middle' => TRUE,
          'family' => TRUE,
          'generational' => FALSE,
          'credentials' => FALSE,
        ],
        'labels' => [
          'title' => 'Title',
          'given' => 'Given',
          'middle' => 'Middle',
          'family' => 'Family',
          'generational' => 'Generational',
          'credentials' => 'Credentials',
        ],
      ],
    ]);
    $fieldConfig->save();

    $this->elementInfoManager = $this->container->get('plugin.manager.element_info');
  }

  /**
   * @covers \_name_translations
   */
  public function testLegacyTranslationsMatchService(): void {
    $metadata = $this->container->get('name.component_metadata');
    // DeprecationHandler dedupes identical messages; one expect covers both
    // _name_translations() calls in this test.
    $this->expectDeprecation(self::DEPRECATION_NAME_TRANSLATIONS);
    $this->assertSame($metadata->getTranslations(), _name_translations());
    $subsetKeys = ['given' => '', 'middle' => ''];
    $this->assertSame(
      $metadata->getTranslations($subsetKeys),
      _name_translations($subsetKeys),
    );
  }

  /**
   * @covers \_name_component_keys
   */
  public function testLegacyComponentKeysMatchUtility(): void {
    $this->expectDeprecation(self::DEPRECATION_NAME_COMPONENT_KEYS);
    $this->assertSame(NameComponents::coreKeys(), _name_component_keys());
  }

  /**
   * @covers \_name_formatter_output_types
   * @covers \_name_formatter_output_options
   */
  public function testLegacyFormatterMetadataMatchesService(): void {
    $metadata = $this->container->get('name.component_metadata');
    $this->expectDeprecation(self::DEPRECATION_FORMATTER_OUTPUT_TYPES);
    $this->assertSame(
      $metadata->getFormatterOutputTypes(),
      _name_formatter_output_types(),
    );
    $this->expectDeprecation(self::DEPRECATION_FORMATTER_OUTPUT_OPTIONS);
    $this->assertSame(
      $metadata->getFormatterOutputOptions(),
      _name_formatter_output_options(),
    );
  }

  /**
   * @covers \_name_component_layout
   */
  public function testLegacyComponentLayoutMatchesUtility(): void {
    $a = [
      'family' => [],
      'generational' => [
        '#default_value' => 'Jr',
        '#access' => TRUE,
      ],
    ];
    $b = $a;
    $this->expectDeprecation(self::DEPRECATION_COMPONENT_LAYOUT);
    _name_component_layout($a, 'asian');
    NameComponents::applyLayout($b, 'asian');
    $this->assertSame($a, $b);
  }

  /**
   * @covers \name_widget_layouts
   */
  public function testLegacyWidgetLayoutsMatchService(): void {
    $service = $this->container->get('name.widget_layouts');
    $this->expectDeprecation(self::DEPRECATION_WIDGET_LAYOUTS);
    $this->assertSame($service->getLayouts(), name_widget_layouts());
  }

  /**
   * @covers \name_user_format_name_alter_preload
   */
  public function testLegacyUserRealnamePreloadDoesNotFatal(): void {
    $user = User::create([
      'name' => $this->randomMachineName(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $this->expectDeprecation(self::DEPRECATION_USER_REALNAME_PRELOAD);
    name_user_format_name_alter_preload($user);
    $this->assertFalse($user->isAuthenticated());
  }

  /**
   * @covers \name_get_additional_component
   */
  public function testLegacyAdditionalComponentMatchesService(): void {
    $service = $this->container->get('name.additional_component');
    $node = Node::create([
      'type' => 'page',
      'title' => 'Kernel page title',
      'field_name_acs' => [
        'given' => 'Test',
        'family' => 'User',
      ],
    ]);
    $node->save();
    $items = $node->get('field_name_acs');
    $etm = $this->createMock(EntityTypeManagerInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $this->expectDeprecation(self::DEPRECATION_ADDITIONAL_COMPONENT);
    $this->assertSame(
      $service->getAdditionalComponent($items, '_self', ''),
      name_get_additional_component($etm, $renderer, $items, '_self', ''),
    );
  }

  /**
   * @covers \name_get_custom_format_options
   * @covers \name_get_custom_list_format_options
   */
  public function testLegacyFormatOptionHelpersMatchService(): void {
    $formatOptions = $this->container->get('name.format_options');
    $this->expectDeprecation(self::DEPRECATION_CUSTOM_FORMAT_OPTIONS);
    $this->assertSame(
      $formatOptions->getCustomFormatOptions(),
      name_get_custom_format_options(),
    );
    $this->expectDeprecation(self::DEPRECATION_CUSTOM_LIST_FORMAT_OPTIONS);
    $this->assertSame(
      $formatOptions->getCustomListFormatOptions(),
      name_get_custom_list_format_options(),
    );
  }

  /**
   * @covers \name_get_format_by_machine_name
   */
  public function testLegacyGetFormatByMachineName(): void {
    $formatOptions = $this->container->get('name.format_options');
    // Same deprecation string for every call; handler records it once.
    $this->expectDeprecation(self::DEPRECATION_FORMAT_BY_MACHINE_NAME);
    $this->assertNull(name_get_format_by_machine_name(''));
    $this->assertNull(name_get_format_by_machine_name([]));
    $expected = $formatOptions->getFormatPatternByMachineName('default');
    $this->assertNotNull($expected);
    $this->assertSame($expected, name_get_format_by_machine_name('default'));
  }

  /**
   * @covers \name_element_expand
   */
  public function testLegacyElementExpandMatchesNameProcess(): void {
    $definition = $this->elementInfoManager->getInfo('name');
    $definition['#parents'] = ['name_legacy'];
    $definition['#required'] = FALSE;
    if (!isset($definition['#value'])) {
      $definition['#value'] = [];
    }
    $formStateA = new FormState();
    $formStateB = new FormState();
    $defA = unserialize(serialize($definition));
    $defB = unserialize(serialize($definition));
    $processedA = Name::process($defA, $formStateA, []);
    $this->expectDeprecation(self::DEPRECATION_ELEMENT_EXPAND);
    $processedB = name_element_expand($defB, $formStateB, []);
    $this->assertEquals($processedA, $processedB);
  }

  /**
   * @covers \name_element_render_component
   */
  public function testLegacyRenderComponentMatches(): void {
    $definition = $this->elementInfoManager->getInfo('name');
    $components = $definition['#components'];
    $base = [
      '#parents' => ['name_legacy'],
      '#tree' => TRUE,
      '#required' => FALSE,
    ];
    $core = TRUE;
    $this->expectDeprecation(self::DEPRECATION_ELEMENT_RENDER_COMPONENT);
    $this->assertEquals(
      Name::renderComponent($components, 'given', $base, $core),
      name_element_render_component($components, 'given', $base, $core),
    );
  }

  /**
   * @covers \name_component_description_after_build_label_alter
   */
  public function testLegacyComponentDescriptionAfterBuild(): void {
    $element = [
      '#id' => 'edit-name-given',
      '#description' => [
        '#type' => 'processed_text',
        '#text' => 'Help text',
      ],
    ];
    $formStateA = new FormState();
    $formStateB = new FormState();
    $elA = unserialize(serialize($element));
    $elB = unserialize(serialize($element));
    $this->expectDeprecation(self::DEPRECATION_COMPONENT_DESCRIPTION_ALTER);
    $this->assertEquals(
      Name::componentDescriptionAfterBuildLabelAlter($elA, $formStateA),
      name_component_description_after_build_label_alter($elB, $formStateB),
    );
  }

  /**
   * @covers \name_element_validate
   */
  public function testLegacyElementValidateMatches(): void {
    $template = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#minimum_components' => [
        'given' => 'given',
        'family' => 'family',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given name'],
        'family' => ['type' => 'textfield', 'title' => 'Family name'],
      ],
      '#value' => [
        'given' => 'Pat',
        'family' => 'Smith',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
    ];
    $elementA = unserialize(serialize($template));
    $elementB = unserialize(serialize($template));
    $formStateA = new FormState();
    $formStateB = new FormState();
    $outA = Name::validateElement($elementA, $formStateA);
    $this->expectDeprecation(self::DEPRECATION_ELEMENT_VALIDATE);
    $outB = name_element_validate($elementB, $formStateB);
    $this->assertEquals($formStateA->getErrors(), $formStateB->getErrors());
    $this->assertEquals($outA, $outB);
  }

  /**
   * @covers \name_element_pre_render
   */
  public function testLegacyPreRenderMatches(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
        '#options' => ['Dr.' => 'Dr.', 'Mr.' => 'Mr.'],
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      'family' => [
        '#type' => 'textfield',
        '#title' => 'Family Name',
      ],
      '#widget_layout' => 'stacked',
    ];
    $elA = unserialize(serialize($element));
    $elB = unserialize(serialize($element));
    $this->expectDeprecation(self::DEPRECATION_ELEMENT_PRE_RENDER);
    $this->assertEquals(
      Name::preRender($elA),
      name_element_pre_render($elB),
    );
  }

  /**
   * @covers \name_element_validate_is_empty
   */
  public function testLegacyValidateIsEmptyMatches(): void {
    $empty = [
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ];
    $this->expectDeprecation(self::DEPRECATION_ELEMENT_VALIDATE_IS_EMPTY);
    $this->assertSame(
      Name::validateIsEmpty($empty),
      name_element_validate_is_empty($empty),
    );
    $nonEmpty = $empty;
    $nonEmpty['given'] = 'Pat';
    $this->assertSame(
      Name::validateIsEmpty($nonEmpty),
      name_element_validate_is_empty($nonEmpty),
    );
  }

  /**
   * @covers \_name_value_sanitize
   */
  public function testLegacyValueSanitizeMatches(): void {
    $this->expectDeprecation(self::DEPRECATION_VALUE_SANITIZE);
    $this->assertSame(
      NameComponents::sanitizeValue('<script>x</script>', NULL, 'default'),
      _name_value_sanitize('<script>x</script>', NULL, 'default'),
    );
    $this->assertSame(
      NameComponents::sanitizeValue('<b>x</b>', NULL, 'plain'),
      _name_value_sanitize('<b>x</b>', NULL, 'plain'),
    );
    $this->assertSame(
      NameComponents::sanitizeValue('<b>x</b>', NULL, 'raw'),
      _name_value_sanitize('<b>x</b>', NULL, 'raw'),
    );
    $item = ['given' => '<i>a</i>'];
    $this->assertSame(
      NameComponents::sanitizeValue($item, 'given', 'default'),
      _name_value_sanitize($item, 'given', 'default'),
    );
  }

  /**
   * Ensures the 8.x-1.2 FQCN for Unicode helpers remains a class alias.
   */
  public function testLegacyNameUnicodeExtrasClassAlias(): void {
    $this->assertTrue(class_exists(NameUnicodeExtras::class));
    $text = 'Hello World';
    $this->assertSame(
      UnicodeExtras::explode($text),
      NameUnicodeExtras::explode($text),
    );
  }

}

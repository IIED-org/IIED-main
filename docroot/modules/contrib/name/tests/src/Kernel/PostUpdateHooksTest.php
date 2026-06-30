<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

require_once __DIR__ . '/../../../name.post_update.php';

/**
 * Direct coverage for procedural post-update hooks.
 *
 * @group name
 */
class PostUpdateHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'name']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');

    $this->container->get('entity_type.listener')
      ->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('entity_test'));
  }

  /**
   * @covers \name_post_update_create_name_list_format
   */
  public function testPostUpdateCreateNameListFormatAddsDefaultEntity(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('name_list_format');
    $existing = $storage->load('default');
    $this->assertNotNull($existing);
    $existing->delete();

    $message = name_post_update_create_name_list_format();
    $created = $storage->load('default');

    $this->assertSame('Default name list format was added.', (string) $message);
    $this->assertNotNull($created);
    $this->assertTrue((bool) $created->locked);
    $this->assertSame(', ', $created->delimiter);
  }

  /**
   * @covers \name_post_update_create_name_list_format
   */
  public function testPostUpdateCreateNameListFormatLocksExistingEntity(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('name_list_format');
    $existing = $storage->load('default');
    $this->assertNotNull($existing);
    $existing->locked = FALSE;
    $existing->save();

    $message = name_post_update_create_name_list_format();

    $this->assertSame(
      'Default name list format was set to locked.',
      (string) $message,
    );
    $this->assertTrue((bool) $storage->load('default')->locked);
  }

  /**
   * @covers \name_post_update_create_name_list_format
   */
  public function testPostUpdateCreateNameListFormatNoopWhenAlreadyLocked(): void {
    $message = name_post_update_create_name_list_format();

    $this->assertSame('Nothing required to action.', (string) $message);
  }

  /**
   * @covers \name_post_update_formatter_settings
   */
  public function testPostUpdateFormatterSettingsMigratesLegacyListSettings(): void {
    $this->installNameField('field_name_formatter', 'entity_test', 'entity_test');
    $display = $this->createViewDisplay('field_name_formatter', [
      'format' => 'given',
      'markup' => TRUE,
      'output' => 'default',
      'multiple' => 'default',
      'custom_legacy' => 'remove-me',
    ]);

    $this->assertNotNull($display->getComponent('field_name_formatter'));

    $message = name_post_update_formatter_settings();
    $display = EntityViewDisplay::load($display->id());
    $settings = $display->getComponent('field_name_formatter')['settings'];

    $this->assertStringContainsString(
      'New name list formatter settings are implemented.',
      (string) $message,
    );
    $this->assertSame('given', $settings['format']);
    $this->assertSame('1', (string) $settings['markup']);
    $this->assertArrayNotHasKey('output', $settings);
    $this->assertSame('default', $settings['list_format']);
    $this->assertArrayNotHasKey('multiple', $settings);
    $this->assertArrayNotHasKey('custom_legacy', $settings);
  }

  /**
   * @covers \name_post_update_formatter_settings_link_and_external_sources
   */
  public function testPostUpdateFormatterSettingsAddsLinkAndSourceDefaults(): void {
    $this->installNameField('field_name_link', 'entity_test', 'entity_test');
    $display = $this->createViewDisplay('field_name_link', [
      'format' => 'default',
      'markup' => '1',
      'output' => 'default',
      'list_format' => '',
    ]);

    name_post_update_formatter_settings_link_and_external_sources();

    $display = EntityViewDisplay::load($display->id());
    $settings = $display->getComponent('field_name_link')['settings'];

    $this->assertSame('simple', $settings['markup']);
    $this->assertSame('', $settings['link_target']);
    $this->assertSame('', $settings['preferred_field_reference']);
    $this->assertSame(', ', $settings['preferred_field_reference_separator']);
    $this->assertSame('', $settings['alternative_field_reference']);
    $this->assertSame(', ', $settings['alternative_field_reference_separator']);
    $this->assertArrayNotHasKey('output', $settings);
  }

  /**
   * @covers \name_post_update_formatter_settings_link_and_external_sources
   */
  public function testPostUpdateFormatterSettingsLinkHandlesSettingsWithoutOutputKey(): void {
    $this->installNameField('field_name_link_no_output', 'entity_test', 'entity_test');
    $display = $this->createViewDisplay('field_name_link_no_output', [
      'format' => 'default',
      'markup' => 'none',
      'list_format' => '',
    ]);

    name_post_update_formatter_settings_link_and_external_sources();

    $display = EntityViewDisplay::load($display->id());
    $settings = $display->getComponent('field_name_link_no_output')['settings'];

    $this->assertSame('none', $settings['markup']);
    $this->assertSame('', $settings['link_target']);
    $this->assertSame('', $settings['preferred_field_reference']);
    $this->assertSame(', ', $settings['preferred_field_reference_separator']);
    $this->assertSame('', $settings['alternative_field_reference']);
    $this->assertSame(', ', $settings['alternative_field_reference_separator']);
    $this->assertArrayNotHasKey('output', $settings);
  }

  /**
   * @covers \name_post_update_field_settings_merge
   */
  public function testPostUpdateFieldSettingsMergeMovesSettingsToFieldConfig(): void {
    $field = $this->installNameField(
      'field_name_merge',
      'entity_test',
      'entity_test',
      [
        'components' => ['given' => TRUE, 'family' => TRUE],
        'minimum_components' => ['given' => 'given'],
        'max_length' => 255,
        'labels' => ['given' => 'Given'],
        'allow_family_or_given' => TRUE,
        'autocomplete_source' => ['given' => ['source_a']],
        'autocomplete_separator' => ['given' => ', '],
        'title_options' => ['Dr.'],
        'generational_options' => ['Jr.'],
        'sort_options' => ['family'],
      ],
      [
        'override_format' => 'default',
      ],
    );

    name_post_update_field_settings_merge();

    $reloaded_field = FieldConfig::loadByName(
      'entity_test',
      'entity_test',
      'field_name_merge',
    );
    $reloaded_storage = FieldStorageConfig::loadByName(
      'entity_test',
      'field_name_merge',
    );

    $field_settings = $reloaded_field->getSettings();
    $storage_settings = $reloaded_storage->getSettings();

    $this->assertTrue($field_settings['components']['given']);
    $this->assertSame('Given', $field_settings['labels']['given']);
    $this->assertFalse((bool) $field_settings['allow_family_or_given']);
    $this->assertSame('default', $field_settings['override_format']);
    $this->assertArrayNotHasKey('components', $storage_settings);
    $this->assertArrayNotHasKey('labels', $storage_settings);
    $this->assertArrayNotHasKey('sort_options', $storage_settings);
  }

  /**
   * @covers \name_post_update_field_settings_remove_inline_css
   */
  public function testPostUpdateFieldSettingsRemoveInlineCssCleansSettings(): void {
    $this->installNameField(
      'field_name_css',
      'entity_test',
      'entity_test',
      [],
      [
        'inline_css' => 'display:inline-block;',
        'component_css' => ['given' => 'color:red;'],
        'widget_layout' => '',
      ],
    );

    \Drupal::configFactory()
      ->getEditable('name.settings')
      ->set('element_wrapper', 'div')
      ->set('inline_styles', 'ltr')
      ->set('inline_styles_rtl', 'rtl')
      ->save();

    name_post_update_field_settings_remove_inline_css();

    $field = FieldConfig::loadByName('entity_test', 'entity_test', 'field_name_css');
    $settings = $field->getSettings();

    $this->assertArrayNotHasKey('inline_css', $settings);
    $this->assertArrayNotHasKey('component_css', $settings);
    $this->assertSame('stacked', $settings['widget_layout']);
    $this->assertNull(\Drupal::config('name.settings')->get('element_wrapper'));
    $this->assertNull(\Drupal::config('name.settings')->get('inline_styles'));
    $this->assertNull(\Drupal::config('name.settings')->get('inline_styles_rtl'));
  }

  /**
   * @covers \name_post_update_add_wrapper_type_to_name_widget
   */
  public function testPostUpdateAddsNameWidgetWrapperType(): void {
    \Drupal::configFactory()
      ->getEditable('core.entity_form_display.entity_test.entity_test.default')
      ->setData([
        'targetEntityType' => 'entity_test',
        'bundle' => 'entity_test',
        'mode' => 'default',
        'status' => TRUE,
        'content' => [
          'field_name_wrapper' => [
            'type' => 'name_default',
            'label' => 'above',
            'settings' => [
              'override_field_settings' => TRUE,
            ],
          ],
          'field_name_preserved' => [
            'type' => 'name_default',
            'label' => 'above',
            'settings' => [
              'override_field_settings' => TRUE,
              'wrapper_type' => 'details',
            ],
          ],
        ],
      ])
      ->save();

    $message = name_post_update_add_wrapper_type_to_name_widget();
    $content = \Drupal::configFactory()
      ->get('core.entity_form_display.entity_test.entity_test.default')
      ->get('content');
    $updated = $content['field_name_wrapper']['settings'];
    $preserved = $content['field_name_preserved']['settings'];

    $this->assertSame('container', $updated['wrapper_type']);
    $this->assertSame('details', $preserved['wrapper_type']);
    $this->assertSame(
      'Updated 1 form displays with name wrapper settings.',
      (string) $message,
    );
  }

  /**
   * @covers \name_post_update_add_autocomplete_match_settings
   */
  public function testPostUpdateAddsMissingAutocompleteMatchSettings(): void {
    $field = $this->installNameField(
      'field_name_autocomplete_defaults',
      'entity_test',
      'entity_test',
    );
    $settings = $field->getSettings();
    unset($settings['autocomplete_match'], $settings['autocomplete_match_overrides']);
    $field->setSettings($settings)->save();

    $message = name_post_update_add_autocomplete_match_settings();

    $reloaded_field = FieldConfig::loadByName(
      'entity_test',
      'entity_test',
      'field_name_autocomplete_defaults',
    );
    $updated_settings = $reloaded_field->getSettings();

    $this->assertSame('starts_with', $updated_settings['autocomplete_match']);
    $this->assertCount(6, $updated_settings['autocomplete_match_overrides']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['title']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['given']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['middle']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['family']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['generational']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['credentials']);
    $this->assertStringContainsString(
      'name fields with autocomplete match settings.',
      (string) $message,
    );
  }

  /**
   * @covers \name_post_update_add_autocomplete_match_settings
   */
  public function testPostUpdatePreservesExistingAutocompleteMatchSettings(): void {
    $field = $this->installNameField(
      'field_name_autocomplete_existing',
      'entity_test',
      'entity_test',
    );
    $settings = $field->getSettings();
    $settings['autocomplete_match'] = 'contains';
    $settings['autocomplete_match_overrides'] = [
      'given' => 'starts_with',
      'family' => 'contains',
    ];
    $field->setSettings($settings)->save();

    name_post_update_add_autocomplete_match_settings();

    $reloaded_field = FieldConfig::loadByName(
      'entity_test',
      'entity_test',
      'field_name_autocomplete_existing',
    );
    $updated_settings = $reloaded_field->getSettings();

    $this->assertSame('contains', $updated_settings['autocomplete_match']);
    $this->assertSame('starts_with', $updated_settings['autocomplete_match_overrides']['given']);
    $this->assertSame('contains', $updated_settings['autocomplete_match_overrides']['family']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['title']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['middle']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['generational']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['credentials']);
  }

  /**
   * @covers \name_post_update_add_autocomplete_match_settings
   */
  public function testPostUpdateNormalizesMalformedAutocompleteOverrides(): void {
    $field = $this->installNameField(
      'field_name_auto_malformed',
      'entity_test',
      'entity_test',
    );
    $config_storage = \Drupal::service('config.storage');
    $config_name = $field->getConfigDependencyName();
    $field_config = $config_storage->read($config_name);
    unset($field_config['settings']['autocomplete_match']);
    $field_config['settings']['autocomplete_match_overrides'] = 'legacy-string';
    $config_storage->write($config_name, $field_config);
    \Drupal::entityTypeManager()->getStorage('field_config')->resetCache();

    name_post_update_add_autocomplete_match_settings();

    $reloaded_field = FieldConfig::loadByName(
      'entity_test',
      'entity_test',
      'field_name_auto_malformed',
    );
    $updated_settings = $reloaded_field->getSettings();

    $this->assertSame('starts_with', $updated_settings['autocomplete_match']);
    $this->assertCount(6, $updated_settings['autocomplete_match_overrides']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['title']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['given']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['middle']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['family']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['generational']);
    $this->assertSame('', $updated_settings['autocomplete_match_overrides']['credentials']);
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
  ): FieldConfig {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'name',
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
   * Creates a display with a configured name formatter component.
   */
  private function createViewDisplay(string $field_name, array $settings): EntityViewDisplay {
    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'id' => 'entity_test.entity_test.default',
    ]);
    $display->setComponent($field_name, [
      'type' => 'name_default',
      'label' => 'above',
      'settings' => $settings,
    ]);
    $display->save();

    return $display;
  }

}

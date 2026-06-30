<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Plugin\Field\FieldWidget\NameWidget;

/**
 * Tests the Name field widget plugin.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Plugin\Field\FieldWidget\NameWidget
 */
final class NameWidgetTest extends KernelTestBase {

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
   * Name field machine name.
   */
  private string $fieldName = 'field_name_widget';

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

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'type' => 'name',
    ])->save();

    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
      'label' => 'Name widget',
    ])->save();
  }

  /**
   * Builds the NameWidget plugin instance.
   */
  private function buildWidget(array $settings = []): NameWidget {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    return NameWidget::create($this->container, [
      'field_definition' => $field_config,
      'settings' => $settings,
      'third_party_settings' => [],
    ], 'name_default', []);
  }

  /**
   * Resolves settings through the protected widget helper.
   */
  private function resolveWidgetSettings(NameWidget $widget, FormState $form_state): array {
    return (function (FormState $form_state): array {
      return $this->resolveSettings($form_state);
    })->call($widget, $form_state);
  }

  /**
   * @covers ::defaultSettings
   */
  public function testDefaultSettingsIncludesOverrideFieldSettings(): void {
    $defaults = NameWidget::defaultSettings();

    $this->assertArrayHasKey('override_field_settings', $defaults);
    $this->assertFalse((bool) $defaults['override_field_settings']);
    $this->assertSame('fieldset', $defaults['wrapper_type']);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementBuildsNameElementWithComponents(): void {
    $widget = $this->buildWidget();
    $entity = EntityTest::create([
      'name' => 'Widget entity',
      $this->fieldName => [
        'given' => 'Pat',
        'family' => 'Lee',
      ],
    ]);

    $form = [];
    $element = $widget->formElement(
      $entity->get($this->fieldName),
      0,
      ['#title_display' => 'before'],
      $form,
      new FormState(),
    );

    $this->assertSame('name', $element['#type']);
    $this->assertSame('fieldset', $element['#wrapper_type']);
    $this->assertArrayHasKey('given', $element['#components']);
    $this->assertArrayHasKey('family', $element['#components']);
    $this->assertArrayHasKey('title', $element['#components']);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementUsesWidgetSettingsUnlessDefaultValueWidget(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_config->setSetting('widget_layout', 'stacked');
    $field_config->save();

    $widget = $this->buildWidget([
      'override_field_settings' => TRUE,
      'widget_layout' => 'inline',
      'wrapper_type' => 'details',
    ]);
    $entity = EntityTest::create([
      'name' => 'Widget override',
      $this->fieldName => [
        'given' => 'Pat',
        'family' => 'Lee',
      ],
    ]);

    $form = [];
    $normal_element = $widget->formElement(
      $entity->get($this->fieldName),
      0,
      ['#title_display' => 'before'],
      $form,
      new FormState(),
    );
    $this->assertSame('inline', $normal_element['#widget_layout']);
    $this->assertSame('details', $normal_element['#wrapper_type']);

    $default_value_form_state = new FormState();
    $default_value_form_state->set('default_value_widget', TRUE);
    $default_value_element = $widget->formElement(
      $entity->get($this->fieldName),
      0,
      ['#title_display' => 'before'],
      $form,
      $default_value_form_state,
    );
    $this->assertSame('stacked', $default_value_element['#widget_layout']);
    $this->assertSame('details', $default_value_element['#wrapper_type']);
  }

  /**
   * @covers ::resolveSettings
   */
  public function testResolveSettingsReturnsFieldSettingsWhenNoOverride(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_config->setSetting('widget_layout', 'inline');
    $field_config->save();

    $widget = $this->buildWidget([
      'override_field_settings' => FALSE,
      'widget_layout' => 'stacked',
    ]);

    $settings = $this->resolveWidgetSettings($widget, new FormState());

    $this->assertSame('inline', $settings['widget_layout']);
    $this->assertArrayNotHasKey('override_field_settings', $settings);
  }

  /**
   * @covers ::resolveSettings
   */
  public function testResolveSettingsReturnsWidgetSettingsWhenOverrideActiveAndNotDefaultValueWidget(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_config->setSetting('widget_layout', 'stacked');
    $field_config->save();

    $widget = $this->buildWidget([
      'override_field_settings' => TRUE,
      'widget_layout' => 'inline',
    ]);

    $settings = $this->resolveWidgetSettings($widget, new FormState());

    // Widget value must win over field value for shared keys.
    $this->assertSame('inline', $settings['widget_layout']);
    $this->assertArrayHasKey('override_field_settings', $settings);
  }

  /**
   * @covers ::formElement
   * @covers ::buildComponentProperties
   */
  public function testFormElementBuildsSelectComponent(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_type = $field_config->getSetting('field_type');
    $field_type['title'] = 'select';
    $field_config->setSetting('field_type', $field_type);
    $field_config->save();

    $widget = $this->buildWidget();
    $entity = EntityTest::create([
      'name' => 'Select widget',
      $this->fieldName => [
        'title' => 'Dr.',
        'given' => 'Pat',
      ],
    ]);

    $form = [];
    $element = $widget->formElement(
      $entity->get($this->fieldName),
      0,
      ['#title_display' => 'before'],
      $form,
      new FormState(),
    );

    $this->assertSame('select', $element['#components']['title']['type']);
    $this->assertSame(1, $element['#components']['title']['size']);
    $this->assertArrayHasKey('options', $element['#components']['title']);
  }

  /**
   * @covers ::formElement
   * @covers ::buildComponentProperties
   */
  public function testFormElementAddsAutocompleteConfigurationWhenSourcesExist(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_type = $field_config->getSetting('field_type');
    $field_type['given'] = 'autocomplete';
    $field_config->setSetting('field_type', $field_type);

    $autocomplete_source = $field_config->getSetting('autocomplete_source');
    $autocomplete_source['given'] = ['directory', ''];
    $field_config->setSetting('autocomplete_source', $autocomplete_source);
    $field_config->save();

    $widget = $this->buildWidget();
    $entity = EntityTest::create([
      'name' => 'Autocomplete widget',
      $this->fieldName => [
        'given' => 'Pat',
      ],
    ]);

    $form = [];
    $element = $widget->formElement(
      $entity->get($this->fieldName),
      0,
      ['#title_display' => 'before'],
      $form,
      new FormState(),
    );

    $this->assertSame('name.autocomplete', $element['#components']['given']['autocomplete']['#autocomplete_route_name']);
    $this->assertSame($this->fieldName, $element['#components']['given']['autocomplete']['#autocomplete_route_parameters']['field_name']);
    $this->assertSame('entity_test', $element['#components']['given']['autocomplete']['#autocomplete_route_parameters']['entity_type']);
    $this->assertSame('entity_test', $element['#components']['given']['autocomplete']['#autocomplete_route_parameters']['bundle']);
    $this->assertSame('given', $element['#components']['given']['autocomplete']['#autocomplete_route_parameters']['component']);
  }

  /**
   * @covers ::formElement
   * @covers ::buildComponentProperties
   */
  public function testFormElementSkipsAutocompleteWhenSourcesAreEmpty(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_type = $field_config->getSetting('field_type');
    $field_type['given'] = 'autocomplete';
    $field_config->setSetting('field_type', $field_type);

    $autocomplete_source = $field_config->getSetting('autocomplete_source');
    $autocomplete_source['given'] = ['', ''];
    $field_config->setSetting('autocomplete_source', $autocomplete_source);
    $field_config->save();

    $widget = $this->buildWidget();
    $entity = EntityTest::create([
      'name' => 'Empty autocomplete sources widget',
      $this->fieldName => [
        'given' => 'Pat',
      ],
    ]);

    $form = [];
    $element = $widget->formElement(
      $entity->get($this->fieldName),
      0,
      ['#title_display' => 'before'],
      $form,
      new FormState(),
    );

    $this->assertArrayNotHasKey('autocomplete', $element['#components']['given']);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElementMarksDisabledComponentsAsExcluded(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_config->setSetting('components', [
      'title' => TRUE,
      'given' => TRUE,
      'middle' => TRUE,
      'family' => TRUE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);
    $field_config->save();

    $widget = $this->buildWidget();
    $entity = EntityTest::create([
      'name' => 'Excluded components widget',
      $this->fieldName => [
        'given' => 'Pat',
        'family' => 'Lee',
      ],
    ]);

    $form = [];
    $element = $widget->formElement(
      $entity->get($this->fieldName),
      0,
      ['#title_display' => 'before'],
      $form,
      new FormState(),
    );

    $this->assertTrue((bool) ($element['#components']['generational']['exclude'] ?? FALSE));
    $this->assertTrue((bool) ($element['#components']['credentials']['exclude'] ?? FALSE));
  }

  /**
   * @covers ::massageFormValues
   */
  public function testMassageFormValuesStripsNoneAndFiltersEmpty(): void {
    $widget = $this->buildWidget();

    $values = [
      [
        'title' => '_none',
        'given' => '',
        'middle' => '',
        'family' => '',
        'generational' => '',
        'credentials' => '',
      ],
      [
        'title' => '_none',
        'given' => 'Pat',
        'middle' => '',
        'family' => 'Lee',
        'generational' => '',
        'credentials' => '',
      ],
    ];

    $processed = $widget->massageFormValues($values, [], new FormState());

    $this->assertCount(1, $processed);
    $this->assertSame('', $processed[0]['title']);
    $this->assertSame('Pat', $processed[0]['given']);
    $this->assertSame('Lee', $processed[0]['family']);
  }

  /**
   * @covers ::settingsSummary
   */
  public function testSettingsSummaryShowsUsingSharedSettingsWhenNotOverridden(): void {
    $widget = $this->buildWidget([
      'override_field_settings' => FALSE,
      'wrapper_type' => 'details',
    ]);
    $summary = $widget->settingsSummary();
    $summary = array_map(static fn($item): string => (string) $item, $summary);

    $this->assertContains('Using shared settings', $summary);
    $this->assertContains('Wrapper type: Details (collapsible)', $summary);
  }

  /**
   * @covers ::settingsSummary
   */
  public function testSettingsSummaryShowsOverriddenWhenOverrideEnabled(): void {
    $widget = $this->buildWidget([
      'override_field_settings' => TRUE,
      'wrapper_type' => 'container',
    ]);
    $summary = $widget->settingsSummary();
    $summary = array_map(static fn($item): string => (string) $item, $summary);

    $this->assertContains('Overridden settings', $summary);
    $this->assertContains('Wrapper type: Container (invisible)', $summary);
  }

  /**
   * @covers ::settingsForm
   */
  public function testSettingsFormAddsOverrideStatesAndExcludedComponents(): void {
    $field_config = FieldConfig::loadByName('entity_test', 'entity_test', $this->fieldName);
    $field_config->setSetting('components', [
      'title' => TRUE,
      'given' => TRUE,
      'middle' => TRUE,
      'family' => TRUE,
      'generational' => FALSE,
      'credentials' => FALSE,
    ]);
    $field_config->save();

    $widget = $this->buildWidget(['override_field_settings' => TRUE]);
    $settings_form = $widget->settingsForm([], new FormState());

    $expected_states = [
      'visible' => [
        ':input[name$="[override_field_settings]"]' => [
          'checked' => TRUE,
        ],
      ],
    ];

    $this->assertSame('checkbox', $settings_form['override_field_settings']['#type']);
    $this->assertSame('radios', $settings_form['wrapper_type']['#type']);
    $this->assertTrue((bool) $settings_form['override_field_settings']['#default_value']);
    $this->assertSame('above', $settings_form['wrapper_type']['#table_group']);
    $this->assertSame($expected_states, $settings_form['widget_layout']['#states']);
    $this->assertArrayNotHasKey('#states', $settings_form['wrapper_type']);
    $this->assertSame($expected_states, $settings_form['field_title_display']['#states']);
    $this->assertSame($expected_states, $settings_form['name_settings']['#states']);
    $this->assertArrayHasKey('generational', $settings_form['#excluded_components']);
    $this->assertArrayHasKey('credentials', $settings_form['#excluded_components']);
    $this->assertContainsEquals([$widget, 'fieldSettingsFormPreRender'], $settings_form['#pre_render']);
  }

}

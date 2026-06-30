<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Plugin\Field\FieldWidget\NameWidget;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the NameFormSettingsHelperTrait with actual form rendering.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameFormSettingsHelperTrait
 */
class NameFormSettingsHelperTest extends KernelTestBase {

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
   * The field storage.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected FieldStorageConfig $fieldStorage;

  /**
   * The field config.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected FieldConfig $fieldConfig;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);
    $this->installEntitySchema('node');

    // Create a content type.
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    // Create a field storage.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_name_test',
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
    $this->fieldStorage->save();

    // Create a field instance.
    $this->fieldConfig = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
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
    $this->fieldConfig->save();
  }

  /**
   * Tests NameWidget settings form has correct #states.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testWidgetSettingsFormStates(): void {
    $configuration = [
      'field_definition' => $this->fieldConfig,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $plugin_definition = [];
    $widget = NameWidget::create(
      $this->container,
      $configuration,
      'name_default',
      $plugin_definition
    );

    $form = [];
    $form_state = new FormState();
    $settings_form = $widget->settingsForm($form, $form_state);

    // Process the pre-render callback.
    if (!empty($settings_form['#pre_render'])) {
      foreach ($settings_form['#pre_render'] as $callback) {
        if (is_array($callback) && $callback[0] === $widget) {
          $settings_form = call_user_func($callback, $settings_form);
        }
      }
    }

    // Verify #states structure for labels.
    $this->assertArrayHasKey('name_settings', $settings_form);
    $this->assertArrayHasKey('table', $settings_form['name_settings']);
    $this->assertSame('table', $settings_form['name_settings']['table']['#type']);
    $this->assertArrayHasKey('labels', $settings_form['name_settings']['table']);

    // Check that labels have #states with OR condition.
    if (isset($settings_form['name_settings']['table']['labels']['given'])) {
      $states = $settings_form['name_settings']['table']['labels']['given']['#states'];
      $this->assertArrayHasKey('visible', $states);
      $this->assertCount(3, $states['visible']);
      $this->assertEquals('or', $states['visible'][1]);
      $this->assertArrayHasKey(':input[name$="[components][given]"]', $states['visible'][0]);
      $this->assertArrayHasKey(':input[name$="[labels][given]"]', $states['visible'][2]);
    }
  }

  /**
   * Tests NameItem field settings form has correct #states.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testFieldSettingsFormStates(): void {
    // Get the field type plugin manager.
    $plugin_manager = $this->container->get('plugin.manager.field.field_type');
    $plugin_definition = $plugin_manager->getDefinition('name');

    // Create field type instance using reflection or direct instantiation.
    // Field types don't use ContainerFactoryPluginInterface, so we need to
    // instantiate directly or get through the field definition.
    $field_type = $this->fieldConfig->getFieldStorageDefinition()->getType();
    $this->assertEquals('name', $field_type);

    // Test through the widget which also uses the trait and calls
    // fieldSettingsFormPreRender.
    // The widget's settings form will test the same pre-render
    // functionality.
    $configuration = [
      'field_definition' => $this->fieldConfig,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $widget = NameWidget::create(
      $this->container,
      $configuration,
      'name_default',
      []
    );

    $form = [];
    $form_state = new FormState();
    $settings_form = $widget->settingsForm($form, $form_state);

    // Process the pre-render callback.
    if (!empty($settings_form['#pre_render'])) {
      foreach ($settings_form['#pre_render'] as $callback) {
        if (is_array($callback) && $callback[0] === $widget) {
          $settings_form = call_user_func($callback, $settings_form);
        }
      }
    }

    // Verify #states structure exists.
    $this->assertArrayHasKey('name_settings', $settings_form);
    $this->assertArrayHasKey('table', $settings_form['name_settings']);

    // Check that form elements have #states with OR condition.
    $children_to_check = ['labels', 'size', 'title_display'];
    foreach ($children_to_check as $child) {
      if (isset($settings_form['name_settings']['table'][$child])) {
        foreach (['given', 'family'] as $key) {
          if (isset($settings_form['name_settings']['table'][$child][$key])) {
            $element = $settings_form['name_settings']['table'][$child][$key];
            if (isset($element['#states'])) {
              $states = $element['#states'];
              $this->assertArrayHasKey('visible', $states);
              $this->assertCount(3, $states['visible']);
              $this->assertEquals('or', $states['visible'][1]);
            }
          }
        }
      }
    }
  }

  /**
   * Tests column visibility with blank label.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testColumnVisibilityWithBlankLabel(): void {
    $configuration = [
      'field_definition' => $this->fieldConfig,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $plugin_definition = [];
    $widget = NameWidget::create(
      $this->container,
      $configuration,
      'name_default',
      $plugin_definition
    );

    // Create a form with blank labels.
    $form = [];
    $form_state = new FormState();
    $settings_form = $widget->settingsForm($form, $form_state);

    // Set blank label for given component.
    if (isset($settings_form['labels']['given'])) {
      $settings_form['labels']['given']['#default_value'] = '';
    }

    // Process the pre-render callback.
    if (!empty($settings_form['#pre_render'])) {
      foreach ($settings_form['#pre_render'] as $callback) {
        if (is_array($callback) && $callback[0] === $widget) {
          $settings_form = call_user_func($callback, $settings_form);
        }
      }
    }

    // Verify that the column has #states with OR condition including empty
    // check.
    if (isset($settings_form['name_settings']['table']['labels']['given'])) {
      $states = $settings_form['name_settings']['table']['labels']['given']['#states'];
      $this->assertArrayHasKey('visible', $states);
      $this->assertCount(3, $states['visible']);

      // Verify the empty label condition exists.
      $this->assertIsArray($states['visible'][2]);
      $this->assertArrayHasKey(':input[name$="[labels][given]"]', $states['visible'][2]);
      $this->assertEquals(['empty' => TRUE], $states['visible'][2][':input[name$="[labels][given]"]']);
    }
  }

  /**
   * Tests column visibility with checked component.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testColumnVisibilityWithCheckedComponent(): void {
    $configuration = [
      'field_definition' => $this->fieldConfig,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $plugin_definition = [];
    $widget = NameWidget::create(
      $this->container,
      $configuration,
      'name_default',
      $plugin_definition
    );

    $form = [];
    $form_state = new FormState();
    $settings_form = $widget->settingsForm($form, $form_state);

    // Process the pre-render callback.
    if (!empty($settings_form['#pre_render'])) {
      foreach ($settings_form['#pre_render'] as $callback) {
        if (is_array($callback) && $callback[0] === $widget) {
          $settings_form = call_user_func($callback, $settings_form);
        }
      }
    }

    // Verify that columns have #states with component checked condition.
    if (isset($settings_form['name_settings']['table']['labels']['given'])) {
      $states = $settings_form['name_settings']['table']['labels']['given']['#states'];
      $this->assertArrayHasKey('visible', $states);
      $this->assertCount(3, $states['visible']);

      // Verify the component checked condition exists.
      $this->assertIsArray($states['visible'][0]);
      $this->assertArrayHasKey(':input[name$="[components][given]"]', $states['visible'][0]);
      $this->assertEquals(['checked' => TRUE], $states['visible'][0][':input[name$="[components][given]"]']);
    }
  }

  /**
   * Tests that all form children get the OR condition.
   *
   * @covers ::fieldSettingsFormPreRender
   */
  public function testAllChildrenGetOrCondition(): void {
    $configuration = [
      'field_definition' => $this->fieldConfig,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $plugin_definition = [];
    $widget = NameWidget::create(
      $this->container,
      $configuration,
      'name_default',
      $plugin_definition
    );

    $form = [];
    $form_state = new FormState();
    $settings_form = $widget->settingsForm($form, $form_state);

    // Process the pre-render callback.
    if (!empty($settings_form['#pre_render'])) {
      foreach ($settings_form['#pre_render'] as $callback) {
        if (is_array($callback) && $callback[0] === $widget) {
          $settings_form = call_user_func($callback, $settings_form);
        }
      }
    }

    // Verify that labels, size, and title_display all get the OR condition.
    $children_to_check = ['labels', 'size', 'title_display'];
    foreach ($children_to_check as $child) {
      if (isset($settings_form['name_settings']['table'][$child])) {
        foreach (['given', 'family'] as $key) {
          if (isset($settings_form['name_settings']['table'][$child][$key])) {
            $element = $settings_form['name_settings']['table'][$child][$key];
            if (isset($element['#states'])) {
              $states = $element['#states'];
              $this->assertArrayHasKey('visible', $states);
              $this->assertCount(3, $states['visible'], "Child '{$child}' with key '{$key}' should have 3 elements in visible states");
              $this->assertEquals('or', $states['visible'][1], "Child '{$child}' with key '{$key}' should have 'or' as second element");
            }
          }
        }
      }
    }
  }

  /**
   * Tests field-item headers only include enabled component columns.
   *
   * @covers \Drupal\name\Plugin\Field\FieldType\NameItem::fieldSettingsForm
   * @covers \Drupal\name\Traits\NameFormSettingsHelperTrait::fieldSettingsFormPreRender
   */
  public function testFieldItemFormTableHeaderMatchesEnabledComponents(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Kernel header test',
    ]);
    $items = $node->get('field_name_test');
    if ($items->isEmpty()) {
      $items->appendItem();
    }
    $plugin = $items->get(0);

    $renderer = $this->container->get('renderer');
    $render_context = new RenderContext();
    [$form, $rendered] = $renderer->executeInRenderContext(
      $render_context,
      function () use ($plugin) {
        $built = $plugin->fieldSettingsForm([], new FormState());
        $after_pre_render = $built;
        if (!empty($after_pre_render['#pre_render'])) {
          foreach ($after_pre_render['#pre_render'] as $callback) {
            if (is_array($callback) && $callback[0] === $plugin) {
              $after_pre_render = call_user_func($callback, $after_pre_render);
            }
          }
        }
        return [$built, $after_pre_render];
      },
    );

    $metadata = $this->container->get('name.component_metadata');
    $components = $metadata->getTranslations();
    $excluded_components = $form['#excluded_components'] ?? [];
    $visible_components = array_diff_key($components, $excluded_components);
    $headers = $rendered['name_settings']['table']['#header'];

    $this->assertCount(count($visible_components) + 1, $headers);
    $this->assertSame('Field', (string) $headers[0]['data']);
  }

  /**
   * Tests footnotes are rendered when a row carries description text.
   *
   * @covers \Drupal\name\Plugin\Field\FieldType\NameItem::fieldSettingsForm
   * @covers \Drupal\name\Traits\NameFormSettingsHelperTrait::fieldSettingsFormPreRender
   */
  public function testFieldItemFormFootnotesRenderedForDescribedRows(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Kernel footnote test',
    ]);
    $items = $node->get('field_name_test');
    if ($items->isEmpty()) {
      $items->appendItem();
    }
    $plugin = $items->get(0);

    $renderer = $this->container->get('renderer');
    $render_context = new RenderContext();
    $rendered = $renderer->executeInRenderContext(
      $render_context,
      function () use ($plugin) {
        $built = $plugin->fieldSettingsForm([], new FormState());
        $built['labels']['#description'] = 'Description used for footnote coverage.';
        $after_pre_render = $built;
        if (!empty($after_pre_render['#pre_render'])) {
          foreach ($after_pre_render['#pre_render'] as $callback) {
            if (is_array($callback) && $callback[0] === $plugin) {
              $after_pre_render = call_user_func($callback, $after_pre_render);
            }
          }
        }
        return $after_pre_render;
      },
    );

    $this->assertArrayHasKey('footnotes', $rendered['name_settings']);
    $this->assertContains(
      'Description used for footnote coverage.',
      $rendered['name_settings']['footnotes']['help_items']['#items'],
    );
  }

  /**
   * Tests field-instance required-marker options are always available.
   *
   * @covers \Drupal\name\Plugin\Field\FieldType\NameItem::fieldSettingsForm
   */
  public function testNameItemFieldSettingsFormRequiredDependentVisibility(): void {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Kernel placeholder',
    ]);
    $items = $node->get('field_name_test');
    if ($items->isEmpty()) {
      $items->appendItem();
    }
    $plugin = $items->get(0);

    $renderer = $this->container->get('renderer');
    $render_context = new RenderContext();
    [$form, $rendered] = $renderer->executeInRenderContext(
      $render_context,
      function () use ($plugin) {
        $built = $plugin->fieldSettingsForm([], new FormState());
        $after_pre_render = $built;
        if (!empty($after_pre_render['#pre_render'])) {
          foreach ($after_pre_render['#pre_render'] as $callback) {
            if (is_array($callback) && $callback[0] === $plugin) {
              $after_pre_render = call_user_func($callback, $after_pre_render);
            }
          }
        }
        return [$built, $after_pre_render];
      }
    );

    $this->assertArrayHasKey('show_component_required_marker', $form);
    $this->assertArrayNotHasKey('#states', $form['show_component_required_marker']);
    $this->assertArrayHasKey('flag_required_input', $form);
    $this->assertArrayNotHasKey('#states', $form['flag_required_input']);

    $marker_path = $rendered['name_settings']['table']['components_extra']['elements']['show_component_required_marker'] ?? NULL;
    $this->assertIsArray($marker_path);
    $this->assertArrayNotHasKey('#states', $marker_path);

    $flag_path = $rendered['name_settings']['table']['components_extra']['elements']['flag_required_input'] ?? NULL;
    $this->assertIsArray($flag_path);
    $this->assertArrayNotHasKey('#states', $flag_path);
  }

}

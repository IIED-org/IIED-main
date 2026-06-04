<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Traits;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Plugin\Field\FieldWidget\NameWidget;
use Drupal\node\Entity\NodeType;

/**
 * Kernel coverage for NameFormDisplaySettingsTrait via NameWidget.
 *
 * Drives the trait through the real field-widget plugin with live container
 * services (name.component_metadata, name.widget_layouts, renderer) and
 * round-trips key settings through FieldConfig to confirm schema support.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameFormDisplaySettingsTrait
 */
class NameFormDisplaySettingsTraitTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'name',
    'node',
    'text',
  ];

  /**
   * Name field machine name.
   */
  protected string $fieldName = 'field_author';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['filter', 'node', 'name']);
    $this->installSchema('node', ['node_access']);

    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'name',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Author',
    ])->save();
  }

  /**
   * Builds a NameWidget for the node article bundle.
   */
  protected function buildWidget(): NameWidget {
    $field_config = FieldConfig::loadByName('node', 'article', $this->fieldName);
    return NameWidget::create($this->container, [
      'field_definition'     => $field_config,
      'settings'             => [],
      'third_party_settings' => [],
    ], 'name_default', []);
  }

  /**
   * The widget settings form exposes every form-display group.
   *
   * @covers ::getDefaultNameFormDisplaySettingsForm
   */
  public function testWidgetSettingsFormBuildsAllDisplayGroups(): void {
    $widget = $this->buildWidget();
    $context = new RenderContext();

    $form = $this->container->get('renderer')->executeInRenderContext(
      $context,
      fn () => $widget->settingsForm([], new FormState()),
    );

    $this->assertArrayHasKey('labels', $form);
    $this->assertArrayHasKey('size', $form);
    $this->assertArrayHasKey('title_display', $form);
    $this->assertArrayHasKey('widget_layout', $form);
    $this->assertArrayHasKey('field_title_display', $form);
    $this->assertArrayHasKey('show_component_required_marker', $form);
    $this->assertArrayHasKey('flag_required_input', $form);
    $this->assertArrayHasKey('credentials_inline', $form);

    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertArrayHasKey($component, $form['labels']);
      $this->assertArrayHasKey($component, $form['size']);
      $this->assertArrayHasKey($component, $form['title_display']);
      $this->assertSame('textfield', $form['labels'][$component]['#type']);
      $this->assertSame('number', $form['size'][$component]['#type']);
      $this->assertSame('radios', $form['title_display'][$component]['#type']);
    }

    $this->assertSame('stacked', $form['widget_layout']['#default_value']);
    $this->assertArrayHasKey('stacked', $form['widget_layout']['#options']);
  }

  /**
   * Form-display related settings round trip through FieldConfig.
   */
  public function testWidgetSettingsRoundTripThroughFieldConfig(): void {
    $config = FieldConfig::loadByName('node', 'article', $this->fieldName);
    $config->setSetting('widget_layout', 'stacked');
    $config->setSetting('credentials_inline', TRUE);
    $config->setSetting('flag_required_input', TRUE);
    $labels = $config->getSetting('labels');
    $labels['given'] = 'First name';
    $config->setSetting('labels', $labels);
    $config->save();

    $this->container->get('entity_type.manager')->getStorage('field_config')->resetCache();
    $reloaded = FieldConfig::loadByName('node', 'article', $this->fieldName);
    $this->assertSame('stacked', $reloaded->getSetting('widget_layout'));
    $this->assertTrue((bool) $reloaded->getSetting('credentials_inline'));
    $this->assertTrue((bool) $reloaded->getSetting('flag_required_input'));
    $this->assertSame('First name', $reloaded->getSetting('labels')['given']);
  }

}

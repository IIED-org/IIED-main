<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Traits;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\NameComponentMetadataService;
use Drupal\name\Service\WidgetLayoutInterface;
use Drupal\name\Traits\NameFormDisplaySettingsTrait;

/**
 * Tests for NameFormDisplaySettingsTrait.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameFormDisplaySettingsTrait
 */
class NameFormDisplaySettingsTraitTest extends UnitTestCase {

  /**
   * Default settings produced by the trait.
   *
   * @var array<string, mixed>
   */
  protected array $defaultSettings;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $renderer = $this->createMock(RendererInterface::class);
    $renderer->method('render')->willReturn('');

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('name.component_metadata', new NameComponentMetadataService(
      $container->get('string_translation'),
    ));
    $container->set('renderer', $renderer);
    \Drupal::setContainer($container);
  }

  /**
   * Builds a trait consumer with configurable settings access.
   *
   * @param array<string, mixed> $settings
   *   Settings exposed to the trait via getSetting().
   */
  protected function createConsumer(array $settings = []): object {
    $consumer = new class() {
      use NameFormDisplaySettingsTrait;
      use StringTranslationTrait;

      /**
       * Settings exposed through getSetting().
       *
       * @var array<string, mixed>
       */
      public array $settings = [];

      /**
       * Returns a setting by key.
       */
      public function getSetting(string $key): mixed {
        return $this->settings[$key] ?? NULL;
      }

      /**
       * Exposes the protected defaults.
       *
       * @return array<string, mixed>
       *   Default form-display settings.
       */
      public static function publicDefaults(): array {
        return static::getDefaultNameFormDisplaySettings();
      }

      /**
       * Exposes the protected form builder.
       *
       * @return array<string, mixed>
       *   Form element structure.
       */
      public function publicForm(array $settings, array &$form, FormStateInterface $form_state, bool $has_data = TRUE): array {
        return $this->getDefaultNameFormDisplaySettingsForm($settings, $form, $form_state, $has_data);
      }

    };
    $consumer->settings = $settings;
    return $consumer;
  }

  /**
   * Registers a widget-layouts stub that returns the given layouts.
   *
   * @param array<string, array<string, mixed>> $layouts
   *   Layout map keyed by layout id.
   */
  protected function registerWidgetLayouts(array $layouts): void {
    $stub = $this->createMock(WidgetLayoutInterface::class);
    $stub->method('getLayouts')->willReturn($layouts);
    \Drupal::getContainer()->set('name.widget_layouts', $stub);
  }

  /**
   * @covers ::getDefaultNameFormDisplaySettings
   */
  public function testDefaultsTopLevelKeys(): void {
    $defaults = $this->createConsumer()::publicDefaults();

    foreach (['labels', 'size', 'title_display'] as $group) {
      $this->assertArrayHasKey($group, $defaults);
      foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
        $this->assertArrayHasKey($component, $defaults[$group], "$group.$component missing");
      }
    }
    $this->assertSame('stacked', $defaults['widget_layout']);
    $this->assertSame('before', $defaults['field_title_display']);
    $this->assertFalse($defaults['show_component_required_marker']);
    $this->assertFalse($defaults['flag_required_input']);
    $this->assertFalse($defaults['credentials_inline']);
    $this->assertSame(6, $defaults['size']['title']);
    $this->assertSame(20, $defaults['size']['given']);
    $this->assertSame(5, $defaults['size']['generational']);
  }

  /**
   * @covers ::getDefaultNameFormDisplaySettingsForm
   */
  public function testFormProducesAllCoreElementsWithWidgetLayouts(): void {
    $this->registerWidgetLayouts([
      'stacked' => ['label' => 'Stacked'],
      'inline'  => ['label' => 'Inline'],
    ]);

    $consumer = $this->createConsumer([
      'show_component_required_marker' => FALSE,
      'flag_required_input'            => FALSE,
      'credentials_inline'             => TRUE,
      'field_title_display'            => 'invisible',
      'widget_layout'                  => 'inline',
    ]);

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $elements = $consumer->publicForm($consumer::publicDefaults(), $form, $form_state);

    $this->assertArrayHasKey('components_extra', $elements);
    $this->assertTrue($elements['components_extra']['#indent_row']);

    foreach (['show_component_required_marker', 'flag_required_input', 'credentials_inline'] as $key) {
      $this->assertSame('checkbox', $elements[$key]['#type'], "$key should be a checkbox");
      $this->assertSame('components_extra', $elements[$key]['#table_group']);
    }
    $this->assertTrue($elements['credentials_inline']['#default_value']);

    $components = ['title', 'given', 'middle', 'family', 'generational', 'credentials'];
    foreach ($components as $component) {
      $this->assertSame('textfield', $elements['labels'][$component]['#type']);
      $this->assertTrue($elements['labels'][$component]['#required']);

      $this->assertSame('number', $elements['size'][$component]['#type']);
      $this->assertSame(1, $elements['size'][$component]['#min']);
      $this->assertSame(255, $elements['size'][$component]['#max']);

      $this->assertSame('radios', $elements['title_display'][$component]['#type']);
      $this->assertSame(
        ['title', 'description', 'placeholder', 'attribute', 'none'],
        array_keys($elements['title_display'][$component]['#options']),
      );
    }

    $this->assertSame('select', $elements['field_title_display']['#type']);
    $this->assertSame(
      ['before', 'invisible', 'none'],
      array_keys($elements['field_title_display']['#options']),
    );
    $this->assertSame('above', $elements['field_title_display']['#table_group']);
    $this->assertSame('invisible', $elements['field_title_display']['#default_value']);

    $this->assertSame('radios', $elements['widget_layout']['#type']);
    $this->assertSame('above', $elements['widget_layout']['#table_group']);
    $this->assertTrue($elements['widget_layout']['#required']);
    $this->assertSame('inline', $elements['widget_layout']['#default_value']);
    $this->assertSame(['stacked', 'inline'], array_keys($elements['widget_layout']['#options']));
    $this->assertSame('Stacked', (string) $elements['widget_layout']['#options']['stacked']);
  }

  /**
   * @covers ::getDefaultNameFormDisplaySettingsForm
   */
  public function testFormWidgetLayoutOptionsAreEmptyWhenServiceAbsent(): void {
    $consumer = $this->createConsumer();

    $form = [];
    $elements = $consumer->publicForm(
      $consumer::publicDefaults(),
      $form,
      $this->createMock(FormStateInterface::class),
    );

    $this->assertSame([], $elements['widget_layout']['#options']);
  }

  /**
   * @covers ::getDefaultNameFormDisplaySettingsForm
   */
  public function testFormComponentLabelsEmptyWhenMetadataServiceMissing(): void {
    \Drupal::getContainer()->set('name.component_metadata', NULL);
    $this->registerWidgetLayouts(['stacked' => ['label' => 'Stacked']]);

    $consumer = $this->createConsumer();
    $form = [];
    $elements = $consumer->publicForm(
      $consumer::publicDefaults(),
      $form,
      $this->createMock(FormStateInterface::class),
    );

    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertArrayNotHasKey($component, $elements['labels']);
      $this->assertArrayNotHasKey($component, $elements['size']);
      $this->assertArrayNotHasKey($component, $elements['title_display']);
    }

    $this->assertArrayHasKey('widget_layout', $elements);
  }

}

<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Traits;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Kernel coverage for NameFieldSettingsTrait on a real name field.
 *
 * Mounts a real name field, exercises the settings form builder through the
 * NameItem plugin, and confirms:
 * - The autocomplete source options surface for every component.
 * - "Field data" appears per component, title/generational stay row-scoped.
 * - Configured settings survive a save/load round-trip and match schema.
 *
 * @group name
 */
class NameFieldSettingsTraitTest extends EntityKernelTestBase {

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
   * Builds the trait's settings form with all container services in place.
   *
   * @return array<string, mixed>
   *   The rendered settings form element.
   */
  protected function buildSettingsForm(): array {
    $definitions = $this->container->get('entity_field.manager')
      ->getFieldDefinitions('node', 'article');
    $item_class = $definitions[$this->fieldName]->getItemDefinition()->getClass();
    $field_item = new $item_class($definitions[$this->fieldName]->getItemDefinition());
    $form = [];
    $form_state = new FormState();

    $context = new RenderContext();
    return $this->container->get('renderer')->executeInRenderContext(
      $context,
      fn () => $field_item->fieldSettingsForm($form, $form_state),
    );
  }

  /**
   * The autocomplete_source form row has "Field data" per component.
   */
  public function testAutocompleteSourcesExposeFieldDataPerComponent(): void {
    $form = $this->buildSettingsForm();
    $this->assertArrayHasKey('autocomplete_source', $form);
    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertArrayHasKey($component, $form['autocomplete_source']);
      $options = $form['autocomplete_source'][$component]['#options'];
      $this->assertArrayHasKey('data', $options, "'data' missing from $component row");
    }
    $this->assertArrayHasKey('title', $form['autocomplete_source']['title']['#options']);
    $this->assertArrayHasKey('generational', $form['autocomplete_source']['generational']['#options']);
    foreach (['given', 'middle', 'family', 'credentials', 'generational'] as $component) {
      $this->assertArrayNotHasKey('title', $form['autocomplete_source'][$component]['#options']);
    }
    foreach (['title', 'given', 'middle', 'family', 'credentials'] as $component) {
      $this->assertArrayNotHasKey('generational', $form['autocomplete_source'][$component]['#options']);
    }
  }

  /**
   * Saving "data" as a source persists and round-trips cleanly.
   */
  public function testDataSourceSettingPersists(): void {
    $config = FieldConfig::loadByName('node', 'article', $this->fieldName);
    $sources = $config->getSetting('autocomplete_source');
    $sources['given'] = ['data'];
    $sources['family'] = ['data'];
    $config->setSetting('autocomplete_source', $sources);
    $field_types = $config->getSetting('field_type');
    $field_types['given'] = 'autocomplete';
    $field_types['family'] = 'autocomplete';
    $config->setSetting('field_type', $field_types);
    $config->save();

    $this->container->get('entity_type.manager')->getStorage('field_config')->resetCache();
    $reloaded = FieldConfig::loadByName('node', 'article', $this->fieldName);
    $this->assertContains('data', $reloaded->getSetting('autocomplete_source')['given']);
    $this->assertContains('data', $reloaded->getSetting('autocomplete_source')['family']);
    $this->assertSame('autocomplete', $reloaded->getSetting('field_type')['given']);
    $this->assertSame('autocomplete', $reloaded->getSetting('field_type')['family']);
  }

  /**
   * Match mode + per-component override survive a save/load round-trip.
   */
  public function testMatchModeAndOverridePersist(): void {
    $config = FieldConfig::loadByName('node', 'article', $this->fieldName);
    $config->setSetting('autocomplete_match', 'contains');
    $overrides = $config->getSetting('autocomplete_match_overrides') ?? [];
    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $overrides += [$component => ''];
    }
    $overrides['given'] = 'starts_with';
    $config->setSetting('autocomplete_match_overrides', $overrides);
    $config->save();

    $this->container->get('entity_type.manager')->getStorage('field_config')->resetCache();
    $reloaded = FieldConfig::loadByName('node', 'article', $this->fieldName);
    $this->assertSame('contains', $reloaded->getSetting('autocomplete_match'));
    $reloaded_overrides = $reloaded->getSetting('autocomplete_match_overrides');
    $this->assertSame('starts_with', $reloaded_overrides['given']);
    $this->assertSame('', $reloaded_overrides['family']);
    $this->assertSame('', $reloaded_overrides['title']);
  }

}

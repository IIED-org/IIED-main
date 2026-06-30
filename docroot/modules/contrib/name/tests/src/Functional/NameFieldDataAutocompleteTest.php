<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Functional;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the "Field data" autocomplete source end-to-end.
 *
 * Covers:
 * - The admin form exposes the new "Field data" checkbox for every component.
 * - The setting persists across save/load.
 * - The autocomplete JSON endpoint returns matches for given/family scoped to
 *   the correct component and enforces access.
 *
 * @group name
 */
class NameFieldDataAutocompleteTest extends NameTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'field_ui',
    'node',
    'name',
  ];

  /**
   * Name field machine name.
   */
  protected string $fieldName = 'field_author';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
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
      'bundle' => 'page',
      'label' => 'Author',
    ])->save();
  }

  /**
   * Verifies checkbox exposure, setting persistence, and scoped autocomplete.
   */
  public function testFieldDataSourceExposedPersistedAndScoped(): void {
    $this->drupalLogin($this->adminUser);
    $settings_path = 'admin/structure/types/manage/page/fields/node.page.' . $this->fieldName;

    $this->drupalGet($settings_path);
    // Every component row should expose the new "Field data" checkbox.
    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertSession()->fieldExists('settings[autocomplete_source][' . $component . '][data]');
    }
    // 'title' option only exists on the title row.
    $this->assertSession()->fieldExists('settings[autocomplete_source][title][title]');
    $this->assertSession()->fieldNotExists('settings[autocomplete_source][given][title]');
    // 'generational' option only on the generational row.
    $this->assertSession()->fieldExists('settings[autocomplete_source][generational][generational]');
    $this->assertSession()->fieldNotExists('settings[autocomplete_source][given][generational]');

    $edit = [
      'settings[autocomplete_source][given][data]' => TRUE,
      'settings[autocomplete_source][family][data]' => TRUE,
      'settings[field_type][given]' => 'autocomplete',
      'settings[field_type][family]' => 'autocomplete',
    ];
    $this->submitForm($edit, 'Save settings');

    // Reload the field and confirm the setting survived.
    $this->resetAll();
    $config = FieldConfig::loadByName('node', 'page', $this->fieldName);
    $sources = $config->getSetting('autocomplete_source');
    $this->assertContains('data', array_filter($sources['given']));
    $this->assertContains('data', array_filter($sources['family']));

    // Seed content with distinct given/family values.
    Node::create([
      'type' => 'page',
      'title' => 'Author 1',
      'status' => 1,
      $this->fieldName => ['given' => 'Alice', 'family' => 'Smith'],
    ])->save();
    Node::create([
      'type' => 'page',
      'title' => 'Author 2',
      'status' => 1,
      $this->fieldName => ['given' => 'Alfred', 'family' => 'Alison'],
    ])->save();

    $given_url = Url::fromRoute('name.autocomplete', [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'component' => 'given',
    ])->toString();
    $this->drupalGet($given_url, ['query' => ['q' => 'Al']]);
    $body = (string) $this->getSession()->getPage()->getContent();
    $given_matches = json_decode($body, TRUE);
    $this->assertIsArray($given_matches);
    // Response must be a list of {value, label} objects for jQuery UI.
    $given_values = array_column($given_matches, 'value');
    $this->assertContains('Alice', $given_values);
    $this->assertContains('Alfred', $given_values);
    foreach ($given_matches as $row) {
      $this->assertArrayHasKey('value', $row);
      $this->assertArrayHasKey('label', $row);
    }
    // 'Alison' is a family-column value and must not leak into given matches.
    $this->assertNotContains('Alison', $given_values);

    $family_url = Url::fromRoute('name.autocomplete', [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'component' => 'family',
    ])->toString();
    $this->drupalGet($family_url, ['query' => ['q' => 'Al']]);
    $body = (string) $this->getSession()->getPage()->getContent();
    $family_matches = json_decode($body, TRUE);
    $this->assertIsArray($family_matches);
    $family_values = array_column($family_matches, 'value');
    $this->assertContains('Alison', $family_values);
    // 'Alice'/'Alfred' are given-column values and must not leak here.
    $this->assertNotContains('Alice', $family_values);
    $this->assertNotContains('Alfred', $family_values);

    // The JSON response must declare itself private and non-cacheable so
    // access-filtered values are not cached by proxies or browsers.
    $response = $this->getSession()->getDriver()->getContent();
    $this->assertJson($response);
    $headers = $this->getSession()->getResponseHeaders();
    $cache_control = strtolower(implode(',', (array) ($headers['cache-control'] ?? $headers['Cache-Control'] ?? [])));
    $this->assertStringContainsString('no-store', $cache_control);
    $this->assertStringContainsString('private', $cache_control);
  }

  /**
   * A user without edit access to the field receives a 403 from the endpoint.
   */
  public function testAutocompleteRequiresFieldEditAccess(): void {
    $config = FieldConfig::loadByName('node', 'page', $this->fieldName);
    $config->setSetting('autocomplete_source', [
      'title' => ['title'],
      'given' => ['data'],
      'middle' => [],
      'family' => ['data'],
      'generational' => ['generation'],
      'credentials' => [],
    ]);
    $config->setSetting('field_type', [
      'title' => 'select',
      'given' => 'autocomplete',
      'middle' => 'text',
      'family' => 'autocomplete',
      'generational' => 'select',
      'credentials' => 'text',
    ]);
    $config->save();

    // webUser has no node edit permissions; field edit access should fail.
    $this->drupalLogin($this->webUser);
    $url = Url::fromRoute('name.autocomplete', [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'component' => 'given',
    ])->toString();
    $this->drupalGet($url, ['query' => ['q' => 'Al']]);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Admin UI exposes the global match radios and per-component overrides.
   */
  public function testMatchModeUiIsExposed(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.' . $this->fieldName);

    $this->assertSession()->fieldExists('settings[autocomplete_match]');
    $this->assertSession()->pageTextContains('Default autocomplete match mode');
    $this->assertSession()->pageTextContains('Per-component match mode overrides');
    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertSession()->fieldExists('settings[autocomplete_match_overrides][' . $component . ']');
    }
  }

  /**
   * End-to-end: global contains matches mid-string; override restores strict.
   */
  public function testMatchModeEndToEnd(): void {
    $config = FieldConfig::loadByName('node', 'page', $this->fieldName);
    $config->setSetting('autocomplete_source', [
      'title' => ['title'],
      'given' => ['data'],
      'middle' => [],
      'family' => ['data'],
      'generational' => ['generation'],
      'credentials' => [],
    ]);
    $config->setSetting('field_type', [
      'title' => 'select',
      'given' => 'autocomplete',
      'middle' => 'text',
      'family' => 'autocomplete',
      'generational' => 'select',
      'credentials' => 'text',
    ]);
    $config->setSetting('autocomplete_match', 'contains');
    $config->setSetting('autocomplete_match_overrides', [
      'title' => '',
      'given' => 'starts_with',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ]);
    $config->save();

    Node::create([
      'type' => 'page',
      'title' => 'Author 1',
      'status' => 1,
      $this->fieldName => ['given' => 'Alice', 'family' => 'Smith'],
    ])->save();
    Node::create([
      'type' => 'page',
      'title' => 'Author 2',
      'status' => 1,
      $this->fieldName => ['given' => 'Olivia', 'family' => 'Williams'],
    ])->save();

    $this->drupalLogin($this->adminUser);

    $family_url = Url::fromRoute('name.autocomplete', [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'component' => 'family',
    ])->toString();
    $this->drupalGet($family_url, ['query' => ['q' => 'li']]);
    $family_body = (string) $this->getSession()->getPage()->getContent();
    $family_matches = json_decode($family_body, TRUE);
    $family_values = array_column((array) $family_matches, 'value');
    $this->assertContains('Williams', $family_values, 'family under global contains mode should match mid-string');

    $given_url = Url::fromRoute('name.autocomplete', [
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'bundle' => 'page',
      'component' => 'given',
    ])->toString();
    $this->drupalGet($given_url, ['query' => ['q' => 'li']]);
    $given_body = (string) $this->getSession()->getPage()->getContent();
    $given_matches = json_decode($given_body, TRUE);
    $given_values = array_column((array) $given_matches, 'value');
    $this->assertNotContains('Alice', $given_values, 'given override to starts_with should reject mid-string');
    $this->assertNotContains('Olivia', $given_values, 'given override to starts_with should reject mid-string');
  }

}

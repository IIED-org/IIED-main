<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Traits;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Plugin\Field\FieldFormatter\NameFormatter;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Kernel coverage for NameAdditionalPreferredTrait via NameFormatter.
 *
 * Exercises the trait through real container services with a concrete name
 * field, a companion text field, a node bundle, and a user entity so the
 * _self_property_name branch is also observed.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameAdditionalPreferredTrait
 */
class NameAdditionalPreferredTraitTest extends EntityKernelTestBase {

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
   * The node-bundle name field machine name.
   */
  protected string $nodeNameField = 'field_author';

  /**
   * Companion text field used as an additional source candidate.
   */
  protected string $nodeNickField = 'field_nickname';

  /**
   * The user-bundle name field machine name.
   */
  protected string $userNameField = 'field_user_name';

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
      'field_name' => $this->nodeNameField,
      'type' => 'name',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->nodeNameField,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Author',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => $this->nodeNickField,
      'type' => 'text',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->nodeNickField,
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Nickname',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => $this->userNameField,
      'type' => 'name',
      'entity_type' => 'user',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => $this->userNameField,
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Display name',
    ])->save();
  }

  /**
   * Builds a NameFormatter plugin targeting a specific field.
   */
  protected function buildFormatter(string $entity_type, string $bundle, string $field_name, array $settings = []): NameFormatter {
    $definitions = $this->container->get('entity_field.manager')
      ->getFieldDefinitions($entity_type, $bundle);
    return NameFormatter::create($this->container, [
      'field_definition'      => $definitions[$field_name],
      'settings'              => $settings + NameFormatter::defaultSettings(),
      'label'                 => 'above',
      'view_mode'             => 'default',
      'third_party_settings'  => [],
    ], 'name_default', [
      'id'         => 'name_default',
      'class'      => NameFormatter::class,
      'field_types' => ['name'],
    ]);
  }

  /**
   * Source list includes companion fields but excludes the name field itself.
   *
   * @covers ::getAdditionalSources
   * @covers ::getNameAdditionalPreferredSettingsForm
   * @covers ::getEmptyOption
   * @covers ::getTraitUsageIsField
   */
  public function testFormatterSettingsFormListsCompanionSources(): void {
    $formatter = $this->buildFormatter('node', 'article', $this->nodeNameField);
    $form_state = new FormState();
    $elements = $formatter->settingsForm([], $form_state);

    $this->assertArrayHasKey('preferred_field_reference', $elements);
    $options = $elements['preferred_field_reference']['#options'];
    $this->assertArrayHasKey('_self', $options);
    $this->assertArrayHasKey($this->nodeNickField, $options);
    $this->assertArrayNotHasKey($this->nodeNameField, $options, 'Own field is excluded from source list.');
    $this->assertArrayNotHasKey('_self_property_name', $options, 'Login name option is only exposed on user entities.');
    $this->assertSame('-- field default --', (string) $elements['preferred_field_reference']['#empty_option']);
  }

  /**
   * Login-name source is exposed for user entities.
   *
   * @covers ::getAdditionalSources
   */
  public function testUserEntityExposesLoginNameSource(): void {
    $formatter = $this->buildFormatter('user', 'user', $this->userNameField);
    $form_state = new FormState();
    $elements = $formatter->settingsForm([], $form_state);

    $this->assertArrayHasKey('_self_property_name', $elements['preferred_field_reference']['#options']);
  }

  /**
   * The settings summary surfaces configured preferred/alternative sources.
   *
   * @covers ::settingsNameAdditionalPreferredSummary
   */
  public function testSettingsSummaryReportsConfiguredSources(): void {
    $formatter = $this->buildFormatter('node', 'article', $this->nodeNameField, [
      'preferred_field_reference'   => $this->nodeNickField,
      'alternative_field_reference' => 'missing_source',
    ]);
    $summary = $formatter->settingsSummary();
    $lines = array_map('strval', $summary);
    $joined = implode("\n", $lines);

    $this->assertStringContainsString('Preferred: Nickname', $joined);
    $this->assertStringContainsString('Alternative: -- invalid --', $joined);
  }

  /**
   * Preferred/alternative settings survive a FieldConfig save/load round trip.
   */
  public function testPreferredSettingsPersistThroughFieldConfig(): void {
    $config = FieldConfig::loadByName('node', 'article', $this->nodeNameField);
    $config->setSetting('preferred_field_reference', $this->nodeNickField);
    $config->setSetting('preferred_field_reference_separator', ' | ');
    $config->setSetting('alternative_field_reference', $this->nodeNickField);
    $config->setSetting('alternative_field_reference_separator', ' / ');
    $config->save();

    $this->container->get('entity_type.manager')->getStorage('field_config')->resetCache();
    $reloaded = FieldConfig::loadByName('node', 'article', $this->nodeNameField);
    $this->assertSame($this->nodeNickField, $reloaded->getSetting('preferred_field_reference'));
    $this->assertSame(' | ', $reloaded->getSetting('preferred_field_reference_separator'));
    $this->assertSame($this->nodeNickField, $reloaded->getSetting('alternative_field_reference'));
    $this->assertSame(' / ', $reloaded->getSetting('alternative_field_reference_separator'));
  }

  /**
   * Raw formatter output is sanitized when rendered via viewElements().
   */
  public function testViewElementsSanitizesRawMarkupOutput(): void {
    $formatter = $this->buildFormatter('node', 'article', $this->nodeNameField, [
      'markup' => 'raw',
    ]);

    $node = Node::create([
      'type' => 'article',
      $this->nodeNameField => [
        [
          'given' => '<script>alert(1)</script><em>Alice</em>',
          'family' => 'Doe',
        ],
      ],
    ]);

    $elements = $formatter->viewElements($node->get($this->nodeNameField), 'en');
    $output = (string) $elements[0]['#markup'];

    $this->assertArrayHasKey(0, $elements);
    $this->assertStringNotContainsString('<script', $output);
    $this->assertStringContainsString('<em>Alice</em>', $output);
    $this->assertStringContainsString('Doe', $output);
  }

  /**
   * Raw formatter output remains sanitized when wrapped in a link.
   */
  public function testViewElementsSanitizesRawMarkupOutputWithLink(): void {
    $formatter = $this->buildFormatter('node', 'article', $this->nodeNameField, [
      'markup' => 'raw',
      'link_target' => '_self',
    ]);

    $node = Node::create([
      'type' => 'article',
      $this->nodeNameField => [
        [
          'given' => '<script>alert(1)</script><em>Alice</em>',
          'family' => 'Doe',
        ],
      ],
    ]);

    $elements = $formatter->viewElements($node->get($this->nodeNameField), 'en');
    $output = (string) $elements[0]['#markup'];

    $this->assertArrayHasKey(0, $elements);
    $this->assertStringContainsString('<a href="', $output);
    $this->assertStringNotContainsString('<script', $output);
    $this->assertStringContainsString('<em>Alice</em>', $output);
    $this->assertStringContainsString('Doe', $output);
  }

}

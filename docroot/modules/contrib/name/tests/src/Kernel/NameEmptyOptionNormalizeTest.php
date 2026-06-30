<?php

namespace Drupal\Tests\name\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\name\Functional\NameTestTrait;
use Drupal\field\Entity\FieldConfig;

/**
 * Kernel coverage for normalizing empty option from field settings.
 *
 * @group name
 */
class NameEmptyOptionNormalizeTest extends KernelTestBase {

  use NameTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'entity_test',
    'name',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Config and schema needed by form displays and entity_test.
    $this->installConfig(['system', 'field', 'name']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');

    // Ensure the test entity type is fully registered.
    $this->container->get('entity_type.listener')
      ->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('entity_test'));

    // Create the Name field on entity_test.
    $this->createNameField('field_name_test', 'entity_test', 'entity_test');

    // Create a form display with the Name widget on the default form mode.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $display */
    $display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->setComponent('field_name_test', ['type' => 'name_default']);
    $display->save();
  }

  /**
   * Variants to test for the placeholder key: '_none', 0, and ''.
   */
  public static function emptyOptionKeyProvider(): array {
    return [
      'underscore none' => ['_none'],
      'zero' => [0],
      'empty string' => [''],
    ];
  }

  /**
   * Normalizes empty option from field settings for three key variants.
   *
   * @dataProvider emptyOptionKeyProvider
   */
  public function testWidgetNormalizesEmptyOptionFromSettings($emptyKey): void {
    // Update field settings: swap the existing 0 key in title_options for the
    // provided variant ('_none', 0, or '').
    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = FieldConfig::load('entity_test.entity_test.field_name_test');
    $settings = $field->getSettings();

    // Sanity: default title_options should exist and have key 0.
    $this->assertArrayHasKey('title_options', $settings);
    $this->assertArrayHasKey(0, $settings['title_options']);

    $opts = $settings['title_options'];
    $placeholder = $opts[0];
    // Removes the core-provided 0 keyed empty item.
    unset($opts[0]);
    // Rebuild so the placeholder is keyed by the test variant.
    // Keep other options with their numeric keys to avoid dots in config keys.
    $settings['title_options'] = [$emptyKey => $placeholder] + $opts;

    $field->set('settings', $settings)->save();

    // 2) Build the entity form via the collected render display (Kernel-safe).
    $entity = \Drupal::entityTypeManager()
      ->getStorage('entity_test')
      ->create(['bundle' => 'entity_test']);

    $form = [];
    $form_state = new FormState();
    $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');
    $display->buildForm($entity, $form, $form_state);

    // 3) Assert normalization on the Title select element.
    $title_options = $form['field_name_test']['widget'][0]['#components']['title']['options'] ?? NULL;

    // Expect normalized empties.
    // @todo Consider how to assert #empty_value changed by NameOptionService.
    // $this->assertEquals($placeholder, $title_options['_none']);
    $this->assertArrayHasKey('_none', $title_options);

    // And no other empty-key variants should remain.
    $this->assertArrayNotHasKey('', $title_options);
    $this->assertArrayNotHasKey(0, $title_options);

    // Sanity: a normal option value remains present.
    $this->assertContains('Mr.', $title_options);
  }

}

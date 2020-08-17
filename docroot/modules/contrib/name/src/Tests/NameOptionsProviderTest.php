<?php

namespace Drupal\name\Tests;

use Drupal\simpletest\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests NameOptionsProvider class.
 *
 * @group name
 */
class NameOptionsProviderTest extends KernelTestBase {

  use NameTestTrait;

  public static $modules = [
    'field',
    'name',
    'taxonomy',
    'entity_test',
    'text',
  ];

  /**
   * The entity listener.
   *
   * @var \Drupal\Core\Entity\EntityTypeListener
   */
  protected $entityListener;

  /**
   * The name options provider.
   *
   * @var \Drupal\name\NameOptionsProvider
   */
  protected $optionsProvider;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(self::$modules);
    $this->entityListener = \Drupal::service('entity_type.listener');
    $this->entityListener->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('taxonomy_term'));

    $this->optionsProvider = \Drupal::service('name.options_provider');
  }

  /**
   * Tests the field options.
   */
  public function testTitleOptionsFromField() {
    $field = $this->createNameField('field_name_test', 'entity_test', 'entity_test');
    $settings = $field->getSettings();
    $settings['title_options'] = [
      '-- --',
      'b',
      'a',
      'c',
    ];
    $field->getConfig('entity_test')->setSettings($settings)->save();

    $expected = [
      '' => '--',
      'b' => 'b',
      'a' => 'a',
      'c' => 'c',
    ];
    $this->assertEqual($expected, $this->optionsProvider->getOptions($field, 'title'));

    // Enable sorting.
    $settings['sort_options']['title'] = TRUE;
    $field->getConfig('entity_test')->setSettings($settings)->save();
    $expected = [
      '' => '--',
      'a' => 'a',
      'b' => 'b',
      'c' => 'c',
    ];
    $this->assertEqual($expected, $this->optionsProvider->getOptions($field, 'title'));
  }

  /**
   * Tests the taxonomy options source.
   */
  public function testTitleOptionsFromTaxonomy() {
    $field = $this->createNameField('field_name_test', 'entity_test', 'entity_test');

    $vocabulary = Vocabulary::create([
      'vid' => 'title_options',
      'name' => 'Title options',
    ]);
    $vocabulary->save();

    foreach (['foo', 'bar', 'baz'] as $name) {
      $term = Term::create([
        'name' => $name,
        'vid' => $vocabulary->id(),
      ]);
      $term->save();
    }

    $settings = $field->getSettings();
    $settings['title_options'] = [
      '-- --',
      '[vocabulary:title_options]',
    ];
    $settings['sort_options']['title'] = TRUE;
    $field->getConfig('entity_test')->setSettings($settings)->save();

    $expected = [
      '' => '--',
      'bar' => 'bar',
      'baz' => 'baz',
      'foo' => 'foo',
    ];
    $this->assertEqual($expected, $this->optionsProvider->getOptions($field, 'title'));
  }

}

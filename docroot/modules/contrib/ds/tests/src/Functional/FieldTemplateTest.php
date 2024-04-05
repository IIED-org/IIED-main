<?php

namespace Drupal\Tests\ds\Functional;

use Drupal\Core\Cache\Cache;

/**
 * Tests for display of nodes and fields.
 *
 * For some reason, ds_test_form_entity_view_display_edit_form_alter fails
 * on the pipeline, no idea why. So the group is ds_disabled which I use locally
 * to run the tests and methods are prefixed with _ in version control so the
 * bot doesn't run any tests.
 *
 * @group ds_disabled
 */
class FieldTemplateTest extends TestBase {

  /**
   * {@inheritdoc}
   */
  protected function setup(): void {
    parent::setup();

    // Enable field templates.
    \Drupal::configFactory()->getEditable('ds.settings')
      ->set('field_template', TRUE)
      ->save();
  }

  /**
   * Tests on field templates.
   */
  public function _testDsFieldTemplate() {

    // Get a node.
    $node = $this->entitiesTestSetup('hidden');
    $body_field = $node->get('body')->value;

    // Default theming function.
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="clearfix text-formatted field field--name-body field--type-text-with-summary field--label-hidden field__item"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('above', 'body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="clearfix text-formatted field field--name-body field--type-text-with-summary field--label-above"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'Body');
    $elements = $this->xpath('//div[@class="clearfix text-formatted field field--name-body field--type-text-with-summary field--label-above"]/div[@class="field__item"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('above', 'body', 'My body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="clearfix text-formatted field field--name-body field--type-text-with-summary field--label-above"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'My body');
    $elements = $this->xpath('//div[@class="clearfix text-formatted field field--name-body field--type-text-with-summary field--label-above"]/div[@class="field__item"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('hidden', 'body', '', 'test_field_class');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="test_field_class clearfix text-formatted field field--name-body field--type-text-with-summary field--label-hidden field__item"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
  }

  /**
   * Tests on field templates.
   */
  public function _testDsFieldTemplate2() {
    // Get a node.
    $node = $this->entitiesTestSetup('hidden');
    $body_field = $node->get('body')->value;

    // Reset theming function.
    $edit = [
      'fs1[ft_default]' => 'reset',
    ];
    $this->drupalGet('admin/structure/ds/settings');
    $this->submitForm($edit, 'Save configuration');

    // As long as we don't change anything in the UI, the default template will
    // be used.
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('above', 'body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'Body');

    $this->entitiesSetLabelClass('inline', 'body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'Body');

    $this->entitiesSetLabelClass('above', 'body', 'My body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'My body');

    $this->entitiesSetLabelClass('inline', 'body', 'My body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'My body');

    $edit = [
      'fs1[ft_show_colon]' => 'reset',
    ];
    $this->drupalGet('admin/structure/ds/settings');
    $this->submitForm($edit, 'Save configuration');
    // Clear node cache to get the colon.
    $tags = $node->getCacheTags();
    Cache::invalidateTags($tags);

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'My body:');

    $this->entitiesSetLabelClass('hidden', 'body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
  }

  /**
   * Tests on field templates.
   */
  public function _testDsFieldTemplate3() {
    // Get a node.
    $node = $this->entitiesTestSetup('hidden');
    $body_field = $node->get('body')->value;

    // Custom field function with outer wrapper.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][id]' => 'expert',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    // As long as we don't change anything in the UI, the default template will
    // be used.
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With outer div wrapper and class.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With outer span wrapper and class.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class-2',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/span[@class="ow-class-2"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
  }

  /**
   * Tests on field templates.
   */
  public function _testDsFieldTemplate4() {
    // Get a node.
    $node = $this->entitiesTestSetup('hidden');
    $body_field = $node->get('body')->value;

    // With outer wrapper and field items wrapper.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][id]' => 'expert',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'div',
    ];
    $this->dsEditFormatterSettings($edit);

    drupal_flush_all_caches();
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div/div/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With outer wrapper and field items div wrapper with class.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class',
    ];
    $this->dsEditFormatterSettings($edit);
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div/div[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With outer wrapper and field items span wrapper and class.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class',
    ];
    $this->dsEditFormatterSettings($edit);
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With outer wrapper class and field items span wrapper and class.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class',
    ];
    $this->dsEditFormatterSettings($edit);
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With outer wrapper span class and field items span wrapper and class.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class-2',
    ];
    $this->dsEditFormatterSettings($edit);
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/span[@class="ow-class"]/span[@class="fi-class-2"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);
  }

  /**
   * Tests on field templates.
   */
  public function _testDsFieldTemplate5() {
    // Get a node.
    $node = $this->entitiesTestSetup('hidden');
    $body_field = $node->get('body')->value;

    // With field item div wrapper.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][id]' => 'expert',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With field item span wrapper.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-el]' => 'span',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());

    $elements = $this->xpath('//div[@class="group-right"]/span/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With field item span wrapper and class.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-cl]' => 'fi-class',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With fis and fi.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class-2',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-cl]' => 'fi-class',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="fi-class-2"]/div[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With all wrappers.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class-2',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-cl]' => 'fi-class',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // With all wrappers and attributes.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-at]' => 'name="ow-att"',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class-2',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-at]' => 'name="fis-att"',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-cl]' => 'fi-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-at]' => 'name="fi-at"',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class" and @name="ow-att"]/div[@class="fi-class-2" and @name="fis-att"]/span[@class="fi-class" and @name="fi-at"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // Remove attributes.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-at]' => '',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'fi-class-2',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-at]' => '',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-el]' => 'span',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-cl]' => 'fi-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-at]' => '',
    ];
    $this->dsEditFormatterSettings($edit);

    // Label tests with custom function.
    $this->entitiesSetLabelClass('above', 'body');
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'Body');
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('inline', 'body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'Body');
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('above', 'body', 'My body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'My body');
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('inline', 'body', 'My body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'My body');
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('inline', 'body', 'My body', '', TRUE);
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="field__label"]');
    $this->assertTrimEqual($elements[0]->getText(), 'My body:');
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    $this->entitiesSetLabelClass('hidden', 'body');
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // Test default classes on outer wrapper.
    // @todo figure out a way to actually test this as the default cases don't
    // have classes anymore.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-def-cl]' => '1',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]/div[@class="fi-class-2"]/span[@class="fi-class"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // Test default attributes on field item.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => 'div',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => 'ow-class',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-def-at]' => '1',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="ow-class"]');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // Use the test field theming function to test that this function is
    // registered in the theme registry through ds_extras_theme().
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][id]' => 'ds_test_template',
    ];

    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]');
    $this->assertTrimEqual($elements[0]->getText(), 'Testing field output through custom function');
  }

  /**
   * Tests XSS on field templates.
   */
  public function _testDsFieldTemplateXss() {
    // Get a node.
    $node = $this->entitiesTestSetup('hidden');

    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][id]' => 'expert',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
    ];
    $this->dsEditFormatterSettings($edit);

    // Inject XSS everywhere and see if it brakes.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][prefix]' => '<div class="not-stripped"><script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][suffix]' => '</div><script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-el]' => '<script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-cl]' => '<script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][ow-at]' => "name=\"<script>alert('XSS')</script>\"",
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-el]' => '<script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => '<script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fis-at]' => "name=\"<script>alert('XSS')</script>\"",
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-el]' => '<script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-cl]' => '<script>alert("XSS")</script>',
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][fi-at]' => "name=\"<script>alert('XSS')</script>\"",
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseNotContains('<script>alert("XSS")</script>');

    // Verify the prefix/suffix is filtered but not escaped.
    $elements = $this->xpath('//div[@class="not-stripped"]');
    $this->assertEquals(count($elements), 1, 'Stripped but not escaped');
  }

  /**
   * Tests multiple field items.
   */
  public function _testDsMultipleFieldItems() {
    // Get a node.
    $node = $this->entitiesTestSetup('hidden');

    $edit = [
      'fields[field_tags][region]' => 'right',
      'fields[field_tags][type]' => 'entity_reference_label',
    ];
    $this->dsConfigureUi($edit, 'admin/structure/types/manage/article/display');

    // Set expert field on.
    $edit = [
      'fields[field_tags][settings_edit_form][third_party_settings][ds][ft][id]' => 'expert',
      'fields[field_tags][settings_edit_form][third_party_settings][ds][ft][settings][fis]' => '1',
      'fields[field_tags][settings_edit_form][third_party_settings][ds][ft][settings][fis-cl]' => 'tags',
      'fields[field_tags][settings_edit_form][third_party_settings][ds][ft][settings][fi]' => '1',
      'fields[field_tags][settings_edit_form][third_party_settings][ds][ft][settings][fi-cl]' => 'tag',
    ];
    $this->dsEditFormatterSettings($edit, 'field_tags');
    drupal_flush_all_caches();

    // Add multiple tags.
    $edit = [
      'field_tags[0][target_id]' => 'Tag 1',
      'field_tags[1][target_id]' => 'Tag 2',
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Count the found tags.
    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="tags"]/div[@class="tag"]');
    $this->assertEquals(count($elements), 2, '2 tags found');
  }

  /**
   * Tests minimal template functionality.
   */
  public function _testFieldTemplateMinimal() {
    // Get a node.
    $node = $this->entitiesTestSetup('hidden');
    $body_field = $node->get('body')->value;

    $edit = [
      'fields[body][region]' => 'right',
    ];
    $this->dsConfigureUi($edit);

    // Set minimal template on.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][id]' => 'minimal',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field field--name-body field--label-hidden"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // Choose field classes.
    $classes = [
      'test_field_class',
      '[node:nid]',
    ];
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][settings][classes][]' => $classes,
    ];
    $this->dsEditFormatterSettings($edit, 'body');
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $classes = 'test_field_class ' . $node->id() . ' field field--name-body field--label-hidden';
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="' . $classes . '"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // Switch theme.
    $this->container->get('theme_installer')->install(['ds_test_layout_theme']);
    $config = \Drupal::configFactory()->getEditable('system.theme');
    $config->set('default', 'ds_test_layout_theme')->save();
    drupal_flush_all_caches();

    // Go to the node.
    $this->drupalGet('node/' . $node->id());
    $this->assertSession()->responseContains('minimal overridden in test theme!');
    $classes = 'test_field_class ' . $node->id() . ' field field-name-body';
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="' . $classes . '"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

  }

  /**
   * Tests default selection of global template.
   */
  public function _testDefaultFieldTemplateMinimal() {

    // Set default to minimal.
    \Drupal::configFactory()->getEditable('ds.settings')
      ->set('ft_default', 'minimal')
      ->save();
    drupal_flush_all_caches();

    // Get a node.
    $node = $this->entitiesTestSetup('hidden');
    $body_field = $node->get('body')->value;

    $edit = [
      'fields[body][region]' => 'right',
    ];
    $this->dsConfigureUi($edit);

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field field--name-body field--label-hidden"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

    // Explicitly save minimal.
    $edit = [
      'fields[body][settings_edit_form][third_party_settings][ds][ft][id]' => 'minimal',
    ];
    $this->dsEditFormatterSettings($edit);
    drupal_flush_all_caches();

    $this->drupalGet('node/' . $node->id());
    $elements = $this->xpath('//div[@class="group-right"]/div[@class="field field--name-body field--label-hidden"]/p');
    $this->assertTrimEqual($elements[0]->getText(), $body_field);

  }

}

<?php

namespace Drupal\Tests\linkit_attributes\Functional;

use Drupal\linkit\Tests\ProfileCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests linkit attributes functionality.
 *
 * @group linkit_attributes
 */
class LinkitAttributesFieldTest extends BrowserTestBase {

  use FieldUiTestTrait;
  use ProfileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'linkit_attributes',
    'field_ui',
    'block',
    'link_attributes_test_alterinfo',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user that can edit content types.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * The linkit profile.
   *
   * @var \Drupal\linkit\ProfileInterface
   */
  protected $linkitProfile;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'administer content types',
      'administer node fields',
      'administer node display',
      'bypass node access',
    ]);
    $this->drupalLogin($this->adminUser);
    // Breadcrumb is required for FieldUiTestTrait::fieldUIAddNewField.
    $this->drupalPlaceBlock('system_breadcrumb_block');
    \Drupal::state()->set('link_attributes_test_alterinfo.hook_link_attributes_plugin_alter', TRUE);

    $this->linkitProfile = $this->createProfile();
  }

  /**
   * Tests the display of attributes in the widget.
   */
  public function testWidget() {
    // Add a content type.
    $type = $this->drupalCreateContentType();
    $type_path = 'admin/structure/types/manage/' . $type->id();
    $add_path = 'node/add/' . $type->id();

    // Add a link field to the newly-created type.
    $label = $this->randomMachineName();
    $field_name = mb_strtolower($label);
    $storage_settings = ['cardinality' => 'number', 'cardinality_number' => 2];
    $this->fieldUIAddNewField($type_path, $field_name, $label, 'link', $storage_settings);

    // Manually clear cache on the tester side.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    // Change the link widget and enable some attributes.
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $type->id() . '.default')
      ->setComponent('field_' . $field_name, [
        'type' => 'linkit_attributes',
        'settings' => [
          'profile' => $this->linkitProfile->id(),
          'enabled_attributes' => [
            'rel' => TRUE,
            'class' => TRUE,
            'target' => TRUE,
          ],
        ],
      ])
      ->save();

    // Check if the link field have the attributes displayed on node add page.
    $this->drupalGet($add_path);
    $web_assert = $this->assertSession();
    // Link attributes.
    $web_assert->elementExists('css', '.field--widget-linkit-attributes');

    // Rel attribute.
    $attribute_rel = 'field_' . $field_name . '[0][options][attributes][rel]';
    $web_assert->fieldExists($attribute_rel);

    // Class attribute.
    $attribute_class = 'field_' . $field_name . '[0][options][attributes][class]';
    $web_assert->fieldExists($attribute_class);

    // Target attribute.
    $attribute_target = 'field_' . $field_name . '[0][options][attributes][target]';
    $target = $web_assert->fieldExists($attribute_target);
    $web_assert->fieldValueEquals($attribute_target, '_blank');
    $this->assertNotEquals('target', $target->getAttribute('id'));

    \Drupal::state()->set('link_attributes_test_alterinfo.hook_link_attributes_plugin_alter', FALSE);
    \Drupal::service('plugin.manager.link_attributes')->clearCachedDefinitions();
    // Create a node.
    $edit = [
      'title[0][value]' => 'A multi field link test',
      'field_' . $field_name . '[0][title]' => 'Link One',
      'field_' . $field_name . '[0][uri]' => '<front>',
      'field_' . $field_name . '[0][options][attributes][class]' => 'class-one class-two',
      'field_' . $field_name . '[1][title]' => 'Link Two',
      'field_' . $field_name . '[1][uri]' => '<front>',
      'field_' . $field_name . '[1][options][attributes][class]' => 'class-three class-four',
    ];
    $this->drupalGet($add_path);
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // Load the field values.
    $field_values = $node->get('field_' . $field_name)->getValue();

    $expected_link_one = [
      'class-one',
      'class-two',
    ];
    $this->assertEquals($expected_link_one, $field_values[0]['options']['attributes']['class']);

    $expected_link_two = [
      'class-three',
      'class-four',
    ];
    $this->assertEquals($expected_link_two, $field_values[1]['options']['attributes']['class']);
  }

  /**
   * Tests saving a node without any attributes enabled in the widget settings.
   */
  public function testWidgetWithoutAttributes() {
    // Add a content type.
    $type = $this->drupalCreateContentType();
    $type_path = 'admin/structure/types/manage/' . $type->id();
    $add_path = 'node/add/' . $type->id();

    // Add a link field to the newly-created type.
    $label = $this->randomMachineName();
    $field_name = mb_strtolower($label);
    $storage_settings = ['cardinality' => 'number', 'cardinality_number' => 2];
    $this->fieldUIAddNewField($type_path, $field_name, $label, 'link', $storage_settings);

    // Manually clear cache on the tester side.
    \Drupal::service('entity_field.manager')->clearCachedFieldDefinitions();

    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $type->id() . '.default')
      ->setComponent('field_' . $field_name, [
        'type' => 'linkit_attributes',
        'settings' => [
          'enabled_attributes' => [],
        ],
      ])
      ->save();

    $this->drupalGet($add_path);
    $web_assert = $this->assertSession();
    // Link attributes.
    $web_assert->elementExists('css', '.field--widget-linkit-attributes');

    // The "Attributes" details form should not be present, since no attributes
    // are enabled:
    $web_assert->elementNotExists('css', 'edit-field-' . $field_name . '-0-options-attributes');
    // Create a node.
    $edit = [
      'title[0][value]' => 'A multi field link test',
      'field_' . $field_name . '[0][title]' => 'Link One',
      'field_' . $field_name . '[0][uri]' => '<front>',
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->drupalGet($node->toUrl()->toString());
    $web_assert->linkExists('Link One');
  }

}

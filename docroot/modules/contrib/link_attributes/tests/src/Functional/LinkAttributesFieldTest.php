<?php

namespace Drupal\Tests\link_attributes\Functional;

use Drupal\link_attributes\Plugin\Field\FieldWidget\LinkWithAttributesWidget;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests link attributes functionality.
 *
 * @group link_attributes
 */
class LinkAttributesFieldTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'link_attributes',
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
    \Drupal::state()->set('link_attributes_test_alterinfo.hook_link_attributes_plugin_alter', 'type_one');
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
        'type' => 'link_attributes',
        'settings' => [
          'enabled_attributes' => [
            'rel' => TRUE,
            'class' => TRUE,
            'target' => TRUE,
          ],
          'widget_default_open' => LinkWithAttributesWidget::WIDGET_OPEN_EXPANDED,
        ],
      ])
      ->save();

    // Check if the link field have the attributes displayed on node add page.
    $this->drupalGet($add_path);
    $web_assert = $this->assertSession();
    // Link attributes.
    $web_assert->elementExists('css', '.field--widget-link-attributes');

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
        'type' => 'link_attributes',
        'settings' => [
          'enabled_attributes' => [],
        ],
      ])
      ->save();

    $this->drupalGet($add_path);
    $web_assert = $this->assertSession();
    // Link attributes.
    $web_assert->elementExists('css', '.field--widget-link-attributes');

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

  /**
   * Tests the widget's "widget_default_open" option.
   */
  public function testWidgetDetailsBehavior() {
    $session = $this->assertSession();
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

    // Change the link widget and set the "widget_default_open" option
    // to "expanded":
    $defaultFormDisplay = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $type->id() . '.default');

    $defaultFormDisplay->setComponent('field_' . $field_name, [
      'type' => 'link_attributes',
      'settings' => [
        'widget_default_open' => LinkWithAttributesWidget::WIDGET_OPEN_EXPANDED,
        'enabled_attributes' => [
          'rel' => TRUE,
          'class' => TRUE,
          'target' => TRUE,
        ],
      ],
    ])->save();
    $this->drupalGet($add_path);
    // See if the details are open:
    $session->elementAttributeExists('css', '#edit-field-' . $field_name . '-0-options-attributes', 'open');

    // Change the link widget and the "widget_default_open" option to
    // "collapsed":
    $defaultFormDisplay->setComponent('field_' . $field_name, [
      'type' => 'link_attributes',
      'settings' => [
        'widget_default_open' => LinkWithAttributesWidget::WIDGET_OPEN_COLLAPSED,
        'enabled_attributes' => [
          'rel' => TRUE,
          'class' => TRUE,
          'target' => TRUE,
        ],
      ],
    ])
      ->save();
    // See if the details are closed:
    $this->drupalGet($add_path);
    $session->elementAttributeNotExists('css', '#edit-field-' . $field_name . '-0-options-attributes', 'open');

    // Change the link widget and the "widget_default_open" option to
    // "expandIfValuesSet" and have no attributes enabled:
    $defaultFormDisplay->setComponent('field_' . $field_name, [
      'type' => 'link_attributes',
      'settings' => [
        'widget_default_open' => LinkWithAttributesWidget::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
        'enabled_attributes' => [
          'rel' => TRUE,
          'class' => TRUE,
          'target' => TRUE,
        ],
      ],
    ])
      ->save();

    \Drupal::state()->set('link_attributes_test_alterinfo.hook_link_attributes_plugin_alter', FALSE);
    \Drupal::service('plugin.manager.link_attributes')->clearCachedDefinitions();

    // See if the details are closed, as no attributes are set:
    $this->drupalGet($add_path);
    $session->elementAttributeNotExists('css', '#edit-field-' . $field_name . '-0-options-attributes', 'open');

    // Create a node and set its attributes:
    $edit = [
      'title[0][value]' => 'A multi field link test',
      'field_' . $field_name . '[0][title]' => 'Link One',
      'field_' . $field_name . '[0][uri]' => '<front>',
      'field_' . $field_name . '[0][options][attributes][class]' => 'class-one class-two',
      'field_' . $field_name . '[1][title]' => 'Link Two',
      'field_' . $field_name . '[1][uri]' => '<front>',
      'field_' . $field_name . '[1][options][attributes][class]' => 'class-three class-four',
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);

    // See if the details are open now, as attributes are set:
    $this->drupalGet('node/' . $node->id() . '/edit');
    $session->elementAttributeExists('css', '#edit-field-' . $field_name . '-0-options-attributes', 'open');
  }

  /**
   * Tests that the details widget is opened any of the attributes is required.
   */
  public function testWidgetDetailsWithRequiredField() {
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
        'type' => 'link_attributes',
        'settings' => [
          'enabled_attributes' => [
            'rel' => TRUE,
            'class' => TRUE,
            'target' => TRUE,
          ],
          'widget_default_open' => LinkWithAttributesWidget::WIDGET_OPEN_EXPAND_IF_VALUES_SET,
        ],
      ])
      ->save();

    \Drupal::state()->set('link_attributes_test_alterinfo.hook_link_attributes_plugin_alter', 'type_two');
    \Drupal::service('plugin.manager.link_attributes')->clearCachedDefinitions();

    // Check if the link field have the attributes displayed on node add page.
    $this->drupalGet($add_path);
    $session = $this->assertSession();
    $session->elementAttributeExists('css', '#edit-field-' . $field_name . '-0-options-attributes', 'open');
  }

}

<?php

namespace Drupal\Tests\name\Functional;

use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\field\Entity\FieldConfig;
use Drupal\name\Utility\NameComponents;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Various tests on creating a name widget on a node.
 *
 * @group name
 */
class NameWidgetTest extends NameTestBase {

  use NameTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_ui',
    'node',
    'name',
    'taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create content-type: page.
    $page = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
    ]);
    $page->save();
  }

  /**
   * Minimum components show markers only when the Name field is required.
   */
  public function testNameFieldMarkers(): void {
    $this->drupalLogin($this->adminUser);

    // Create an optional Name field with minimum components and
    // "show_component_required_marker" enabled.
    $this->createNameField('field_name_test', 'node', 'page', [], [
      'settings' => [
        'components' => [
          'title' => TRUE,
          'given' => TRUE,
          'middle' => TRUE,
          'family' => TRUE,
          'generational' => TRUE,
          'credentials' => TRUE,
        ],
        'minimum_components' => [
          'title' => FALSE,
          'given' => TRUE,
          'middle' => FALSE,
          'family' => TRUE,
          'generational' => FALSE,
          'credentials' => FALSE,
        ],
        // Render labels in the description so we match the
        // #edit-field-name-test-0-*-description > label selectors.
        'title_display' => [
          'title' => 'description',
          'given' => 'description',
          'middle' => 'description',
          'family' => 'description',
          'generational' => 'description',
          'credentials' => 'description',
        ],
        'show_component_required_marker' => TRUE,
      ],
    ]);

    // Add the field to the form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page', 'default')
      ->setComponent('field_name_test', [
        'type' => 'name_default',
        'weight' => 1,
      ])
      ->save();

    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = FieldConfig::loadByName('node', 'page', 'field_name_test');
    $this->assertNotNull($field, 'Name field exists on Basic page bundle.');

    // Explicitly ensure the Name field is OPTIONAL.
    $this->assertFalse($field->isRequired(), 'Name field is not required.');
    $this->assertTrue(
      (bool) $field->getSetting('show_component_required_marker'),
      'show_component_required_marker is enabled on the field config.'
    );

    // Use an author user to inspect the widget.
    $author = $this->drupalCreateUser(['create page content']);
    $this->drupalLogin($author);

    // OPTIONAL FIELD: minimum components should NOT show required markers.
    $this->drupalGet('node/add/page');

    $given_label = $this->assertSession()
      ->elementExists('css', '#edit-field-name-test-0-given--description > label');
    $family_label = $this->assertSession()
      ->elementExists('css', '#edit-field-name-test-0-family--description > label');

    $given_classes = $given_label->getAttribute('class') ?? '';
    $family_classes = $family_label->getAttribute('class') ?? '';

    $this->assertStringNotContainsString(
      'js-form-required',
      $given_classes,
      'Given name label is not marked js-form-required when the Name field is optional.'
    );
    $this->assertStringNotContainsString(
      'form-required',
      $given_classes,
      'Given name label is not marked form-required when the Name field is optional.'
    );
    $this->assertStringNotContainsString(
      'js-form-required',
      $family_classes,
      'Family name label is not marked js-form-required when the Name field is optional.'
    );
    $this->assertStringNotContainsString(
      'form-required',
      $family_classes,
      'Family name label is not marked form-required when the Name field is optional.'
    );

    // REQUIRED FIELD: flip the field to required and assert markers appear.
    $this->drupalLogin($this->adminUser);
    $field->setRequired(TRUE)->save();

    $this->drupalLogin($author);
    $this->drupalGet('node/add/page');

    $given_label = $this->assertSession()
      ->elementExists('css', '#edit-field-name-test-0-given--description > label');
    $family_label = $this->assertSession()
      ->elementExists('css', '#edit-field-name-test-0-family--description > label');

    $given_classes = $given_label->getAttribute('class') ?? '';
    $family_classes = $family_label->getAttribute('class') ?? '';

    $this->assertStringContainsString(
      'js-form-required',
      $given_classes,
      'Given name label is marked js-form-required when the Name field is required.'
    );
    $this->assertStringContainsString(
      'form-required',
      $given_classes,
      'Given name label is marked form-required when the Name field is required.'
    );
    $this->assertStringContainsString(
      'js-form-required',
      $family_classes,
      'Family name label is marked js-form-required when the Name field is required.'
    );
    $this->assertStringContainsString(
      'form-required',
      $family_classes,
      'Family name label is marked form-required when the Name field is required.'
    );
  }

  /**
   * Placeholder and hidden labels "Flags inputs as required" on the widget.
   */
  public function testPlaceholderAndHiddenTitleRespectFlagRequiredInput(): void {
    $this->drupalLogin($this->adminUser);

    $this->createNameField('field_name_test', 'node', 'page', [], [
      'required' => TRUE,
      'settings' => [
        'components' => [
          'title' => TRUE,
          'given' => TRUE,
          'middle' => TRUE,
          'family' => TRUE,
          'generational' => TRUE,
          'credentials' => TRUE,
        ],
        'minimum_components' => [
          'title' => FALSE,
          'given' => TRUE,
          'middle' => FALSE,
          'family' => TRUE,
          'generational' => FALSE,
          'credentials' => FALSE,
        ],
        'title_display' => [
          'title' => 'description',
          'given' => 'placeholder',
          'middle' => 'description',
          'family' => 'none',
          'generational' => 'description',
          'credentials' => 'description',
        ],
        'field_type' => [
          'title' => 'text',
          'given' => 'text',
          'middle' => 'text',
          'family' => 'text',
          'generational' => 'text',
          'credentials' => 'text',
        ],
        'labels' => [
          'title' => 'Title',
          'given' => 'Given',
          'middle' => 'Middle name(s)',
          'family' => 'Family',
          'generational' => 'Generational',
          'credentials' => 'Credentials',
        ],
      ],
    ]);

    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page', 'default')
      ->setComponent('field_name_test', [
        'type' => 'name_default',
        'weight' => 1,
      ])
      ->save();

    $form_display = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page', 'default');
    $widget_component = $form_display->getComponent('field_name_test');
    $this->assertIsArray($widget_component);
    // Widget flag applies only when override shared field settings is enabled;
    // see NameWidget::formElement().
    $widget_component['settings']['override_field_settings'] = TRUE;
    $widget_component['settings']['flag_required_input'] = TRUE;
    $form_display->setComponent('field_name_test', $widget_component)->save();

    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = FieldConfig::loadByName('node', 'page', 'field_name_test');
    $this->assertNotNull($field);
    $this->assertSame(
      'placeholder',
      $field->getSetting('title_display')['given'],
      'Given name uses placeholder title display from field settings.'
    );
    $this->assertSame(
      'none',
      $field->getSetting('title_display')['family'],
      'Family name uses hidden title display from field settings.'
    );

    $this->drupalGet('node/add/page');
    $given = $this->inputFieldExists('field_name_test[0][given]');
    $family = $this->inputFieldExists('field_name_test[0][family]');
    $this->assertTrue(
      $given->hasAttribute('required'),
      'Given placeholder input is required when flag_required_input is enabled.'
    );
    $this->assertTrue(
      $family->hasAttribute('required'),
      'Family hidden-label input is required when flag_required_input is enabled.'
    );

    $widget_component['settings']['flag_required_input'] = FALSE;
    $form_display->setComponent('field_name_test', $widget_component)->save();
    $this->drupalGet('node/add/page');
    $given = $this->inputFieldExists('field_name_test[0][given]');
    $family = $this->inputFieldExists('field_name_test[0][family]');
    $this->assertFalse(
      $given->hasAttribute('required'),
      'Given input is not required when flag_required_input is disabled.'
    );
    $this->assertFalse(
      $family->hasAttribute('required'),
      'Family input is not required when flag_required_input is disabled.'
    );

    $widget_component['settings']['flag_required_input'] = TRUE;
    $form_display->setComponent('field_name_test', $widget_component)->save();
    $field->setRequired(FALSE)->save();
    $this->drupalGet('node/add/page');
    $given = $this->inputFieldExists('field_name_test[0][given]');
    $this->assertFalse(
      $given->hasAttribute('required'),
      'Given input is not required when the Name field is optional.'
    );

    $field->setRequired(TRUE)->save();
    $settings = $field->getSettings();
    $settings['minimum_components']['given'] = FALSE;
    $settings['minimum_components']['family'] = TRUE;
    $field->setSettings($settings);
    $field->save();

    $this->drupalGet('node/add/page');
    $given = $this->inputFieldExists('field_name_test[0][given]');
    $family = $this->inputFieldExists('field_name_test[0][family]');
    $this->assertFalse(
      $given->hasAttribute('required'),
      'Given input is not required when it is not a minimum component.'
    );
    $this->assertTrue(
      $family->hasAttribute('required'),
      'Family input stays required when it remains a minimum component.'
    );
  }

  /**
   * The most basic test.
   */
  public function testFieldEntry() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');

    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.2.2',
      fn() => $this->getSession()->getPage()->clickLink('Name'),
      fn() => !$this->getSession()->getPage()->fillField('new_storage_type', 'name') && $this->getSession()->getPage()->pressButton('Continue')
    );

    $new_name_field = [
      'label' => 'Test name',
      'field_name' => 'name_test',
    ];
    $this->submitForm($new_name_field, 'Continue');

    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.2.2',
      fn() => $this->submitForm([], 'Save'),
      fn() => $this->submitForm([], 'Save settings')
    );

    $this->resetAll();

    // Set up a field of each label display and test it shows.
    $field_settings = [
      'settings[components][title]' => TRUE,
      'settings[components][given]' => TRUE,
      'settings[components][middle]' => TRUE,
      'settings[components][family]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[components][credentials]' => TRUE,

      'settings[minimum_components][title]' => TRUE,
      'settings[minimum_components][given]' => TRUE,
      'settings[minimum_components][middle]' => TRUE,
      'settings[minimum_components][family]' => TRUE,
      'settings[minimum_components][generational]' => TRUE,
      'settings[minimum_components][credentials]' => TRUE,

      'settings[show_component_required_marker]' => TRUE,

      'settings[labels][title]' => 'Title',
      'settings[labels][given]' => 'Given',
      'settings[labels][middle]' => 'Middle name(s)',
      'settings[labels][family]' => 'Family',
      'settings[labels][generational]' => 'Generational',
      'settings[labels][credentials]' => 'Credentials',

      'settings[title_display][title]' => 'title',
      'settings[title_display][given]' => 'title',
      'settings[title_display][middle]' => 'description',
      'settings[title_display][family]' => 'placeholder',
      'settings[title_display][generational]' => 'none',
      'settings[title_display][credentials]' => 'placeholder',

      'settings[field_type][title]' => 'select',
      'settings[field_type][given]' => 'text',
      'settings[field_type][middle]' => 'text',
      'settings[field_type][family]' => 'text',
      'settings[field_type][generational]' => 'autocomplete',
      'settings[field_type][credentials]' => 'text',

      'settings[max_length][title]' => 31,
      'settings[max_length][given]' => 45,
      'settings[max_length][middle]' => 127,
      'settings[max_length][family]' => 63,
      'settings[max_length][generational]' => 15,
      'settings[max_length][credentials]' => 255,

      'settings[size][title]' => 6,
      'settings[size][given]' => 10,
      'settings[size][middle]' => 20,
      'settings[size][family]' => 25,
      'settings[size][generational]' => 5,
      'settings[size][credentials]' => 35,

      'settings[credentials_inline]' => TRUE,

      'settings[sort_options][title]' => TRUE,
      'settings[sort_options][generational]' => FALSE,

      'settings[title_options]' => "-- --\nMr.\nMrs.\nMiss\nMs.\nDr.\nProf.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nI\nII\nIII\nIV\nV\nVI\nVII\nVIII\nIX\nX",

      'settings[component_layout]' => 'default',
    ];
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');

    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.2.2',
      fn() => $this->submitForm($field_settings, 'Save'),
      fn() => $this->submitForm($field_settings, 'Save settings')
    );

    $this->drupalGet('node/add/page');

    $this->assertSession()->selectExists('field_name_test[0][title]');
    $this->inputFieldExists('field_name_test[0][given]');
    $this->inputFieldExists('field_name_test[0][middle]');
    $this->inputFieldExists('field_name_test[0][family]');
    $this->inputFieldExists('field_name_test[0][generational]');
    $this->inputFieldExists('field_name_test[0][credentials]');

    // Checks the existence and positioning of the components.
    foreach (NameComponents::coreKeys() as $component) {
      $this->assertComponentSettings($component, $field_settings);
    }

    $this->assertFieldSettings($field_settings);

    // Test the language layouts.
    $field_settings['settings[component_layout]'] = 'asian';
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');
    $this->submitForm($field_settings, 'Save settings');
    $this->drupalGet('node/add/page');
    $this->assertFieldSettings($field_settings);

    $field_settings['settings[component_layout]'] = 'eastern';
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');
    $this->submitForm($field_settings, 'Save settings');
    $this->drupalGet('node/add/page');
    $this->assertFieldSettings($field_settings);

    $field_settings['settings[component_layout]'] = 'german';
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');
    $this->submitForm($field_settings, 'Save settings');
    $this->drupalGet('node/add/page');
    $this->assertFieldSettings($field_settings);

    $field_settings = [
      'settings[show_component_required_marker]' => FALSE,
      'settings[component_layout]' => 'default',
      // 'settings[credentials_inline]' => TRUE,
      // 'settings[component_layout]' => 'default',
    ] + $field_settings;
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_name_test');
    $this->submitForm($field_settings, 'Save settings');
    $this->drupalGet('node/add/page');
    foreach (NameComponents::coreKeys() as $component) {
      $this->assertComponentSettings($component, $field_settings);
    }

  }

  /**
   * Asserts that the field settings appear in the correct order.
   *
   * @param array $settings
   *   The field settings, as form post array.
   */
  protected function assertFieldSettings(array $settings) {
    // Field API may wrap the widget in a div or fieldset; find component
    // wrappers by class so layout order assertions stay valid.
    $xpath = '//*[@id="edit-field-name-test-wrapper"]//div[contains(concat(" ", normalize-space(@class), " "), " name-component-wrapper ")]';
    $elements = $this->xpath($xpath);
    $this->assertNotEmpty($elements, 'No components found.');

    // Use each wrapper's class attribute for ordering. Mink::getHtml() is
    // inner HTML, so concatenating it omits the outer name-*-wrapper classes
    // and breaks regex-based order checks.
    $sequence = [];
    foreach ($elements as $element) {
      $normalized = ' ' . preg_replace('/\s+/', ' ', trim($element->getAttribute('class'))) . ' ';
      $found = NULL;
      foreach (array_keys(\Drupal::service('name.component_metadata')->getTranslations()) as $key) {
        $token = 'name-' . $key . '-wrapper';
        if (str_contains($normalized, ' ' . $token . ' ')) {
          $found = $token;
          break;
        }
      }
      $this->assertNotNull($found, 'Each name-component-wrapper div should declare one name-*-wrapper class.');
      $sequence[] = $found;
    }

    $layout = $settings['settings[component_layout]'] ?? 'default';
    switch ($layout) {
      case 'asian':
        $expected = [
          'name-family-wrapper',
          'name-middle-wrapper',
          'name-given-wrapper',
          'name-title-wrapper',
          'name-credentials-wrapper',
        ];
        $this->assertNotContains('name-generational-wrapper', $sequence, 'Generational field is not rendered with asian layout.');
        break;

      case 'eastern':
        $expected = [
          'name-title-wrapper',
          'name-family-wrapper',
          'name-given-wrapper',
          'name-middle-wrapper',
          'name-generational-wrapper',
          'name-credentials-wrapper',
        ];
        break;

      case 'german':
        $expected = [
          'name-title-wrapper',
          'name-credentials-wrapper',
          'name-given-wrapper',
          'name-middle-wrapper',
          'name-family-wrapper',
        ];
        $this->assertNotContains('name-generational-wrapper', $sequence, 'Generational field is not rendered with german layout.');
        break;

      case 'default':
      default:
        $expected = [
          'name-title-wrapper',
          'name-given-wrapper',
          'name-middle-wrapper',
          'name-family-wrapper',
          'name-generational-wrapper',
          'name-credentials-wrapper',
        ];
        break;
    }

    $this->assertSame($expected, $sequence, 'Name component wrappers should appear in the expected order for the layout.');

    // @todo Tests for settings[credentials_inline] setting.
  }

  /**
   * Asserts that the components exists and appear in the right order.
   *
   * @param string $key
   *   The name component key, for example 'given'.
   * @param array $settings
   *   The field settings, as form post array.
   */
  protected function assertComponentSettings($key, array $settings) {
    $xpath = '//div[contains(@class,:value)]';
    $elements = $this->xpath($this->assertSession()->buildXPathQuery($xpath, [':value' => "name-{$key}-wrapper"]));
    $this->assertNotEmpty($elements, "Component $key field found.");
    $object = reset($elements);

    $type = $settings["settings[field_type][{$key}]"] == 'select' ? 'select' : 'input';
    $show_required = $settings['settings[show_component_required_marker]'];
    $is_required = $settings["settings[minimum_components][{$key}]"];
    // The UI required marker should only appear when:
    // - the field itself is required, AND
    // - this component is configured as a minimum component, AND
    // - "show_component_required_marker" is enabled.
    $field = FieldConfig::loadByName('node', 'page', 'field_name_test');
    $field_required = $field ? $field->isRequired() : FALSE;
    $expect_required_marker = $show_required && $is_required && $field_required;
    $content = str_replace(["\n", "\r"], " ", $object->getHtml());

    switch ($settings["settings[title_display][$key]"]) {
      case 'title':
        $result = (bool) preg_match('/<label .*<' . $type . ' /i', $content);
        $this->assertTrue($result, "Testing label is before field of type $type for $key component.");
        // Regression check: no nested labels.
        $this->assertFalse(
          (bool) preg_match('@<label[^>]*>\s*<label@i', $content),
          "No nested <label> rendered for $key when title_display='title'."
        );
        if ($result) {
          $required_marker_preg = '@<label .*?class=".*?js-form-required.*form-required.*?".*>@';
          if ($expect_required_marker) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required class is added for $key component in label");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required class is not added for $key component in label");
          }
        }
        break;

      case 'description':
        $result = (bool) preg_match('/<' . $type . ' .*<label /i', $content);
        $this->assertTrue($result, "Testing label is after field of type $type for $key component.");
        if ($result) {
          $required_marker_preg = '@<label .*?class=".*?js-form-required.*form-required.*?">@';
          if ($expect_required_marker) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required class is added for $key component in label");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required class is not added for $key component in label");
          }
        }
        break;

      case 'placeholder':
        $result = (bool) preg_match('@<' . $type . ' [^>]*?placeholder=".*?' . $settings["settings[labels][$key]"] . '.*?"@', $content);
        $this->assertTrue($result, "Testing label is a placeholder on the field of type $type for $key component.");
        if ($result) {
          $required_marker_preg = '@<' . $type . ' [^>]*?placeholder=".*?Required.*?"@';
          if ($expect_required_marker) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in placeholder attribute");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in placeholder attribute");
          }
        }
        break;

      case 'attribute':
        $result = (bool) preg_match('@<' . $type . ' [^>]*?title=".*?' . $settings["settings[labels][$key]"] . '.*?"@', $content);
        $this->assertTrue($result, "Testing label is a title attribute on the field of type $type for $key component.");
        if ($result) {
          $required_marker_preg = '@<' . $type . ' [^>]*?title=".*?Required.*?"@';
          if ($expect_required_marker) {
            $this->assertTrue((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in $type title attribute");
          }
          else {
            $this->assertFalse((bool) preg_match($required_marker_preg, $content), "Required text is added for $key component in $type title attribute");
          }
        }
        break;

      case 'none':
        $result = (bool) preg_match('@<label [^>]*?class=".*?visually-hidden.*?"@', $content);
        $this->assertTrue($result, "Testing label is present but hidden on the field of type $type for $key component.");
        break;

    }

    if (isset($settings["settings[max_length][{$key}]"]) && $type != 'select') {
      $result = (bool) preg_match('@<' . $type . ' [^>]*?maxlength="' . $settings["settings[max_length][{$key}]"] . '"@', $content);
      $this->assertTrue($result, "Testing max_length is set on field of type $type for $key component.");
    }
    if (isset($settings["settings[size][{$key}]"]) && $type != 'select') {
      $result = (bool) preg_match('@<' . $type . ' [^>]*?size="' . $settings["settings[size][{$key}]"] . '"@', $content);
      $this->assertTrue($result, "Testing size is set on field of type $type for $key component.");
    }
  }

  /**
   * Checks that specific input field exists on the current page.
   *
   * @param string $name
   *   One of id|name|label|value for the input field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function inputFieldExists($name, ?TraversableElement $container = NULL) {
    $container = $container ?: $this->getSession()->getPage();
    $node = $container->find('named', [
      'field',
      $name,
    ]);

    if ($node === NULL || $node->getTagName() != 'input') {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'input', 'id|name|label|value', $name);
    }

    return $node;
  }

  /**
   * Tests that "_none" is a valid value for text fields.
   */
  public function testTextFieldNoneValue() {
    $this->drupalLogin($this->adminUser);

    // Create a name field, make all components text fields.
    $this->createNameField('field_name_test', 'node', 'page', [], [
      'settings' => [
        'field_type' => [
          'title' => 'text',
          'generational' => 'text',
        ],
      ],
    ]);
    // Add the field to the form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page', 'default')
      ->setComponent('field_name_test', [
        'type' => 'name_default',
        'weight' => 1,
      ])
      ->save();

    // Add a node and use the value "_none" for each text field.
    $this->drupalGet('node/add/page');
    $edit = [
      'title[0][value]' => 'Test node',
      'field_name_test[0][title]' => '_none',
      'field_name_test[0][given]' => '_none',
      'field_name_test[0][middle]' => '_none',
      'field_name_test[0][family]' => '_none',
      'field_name_test[0][generational]' => '_none',
      'field_name_test[0][credentials]' => '_none',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Basic page Test node has been created.');

    // Load the node and assert that the value _none was saved for each text
    // field.
    $node = Node::load(1);
    $this->assertEquals('_none', $node->field_name_test->title);
    $this->assertEquals('_none', $node->field_name_test->given);
    $this->assertEquals('_none', $node->field_name_test->middle);
    $this->assertEquals('_none', $node->field_name_test->family);
    $this->assertEquals('_none', $node->field_name_test->generational);
    $this->assertEquals('_none', $node->field_name_test->credentials);

    // Go the node edit form and assert that "_none" is used in each text field.
    $this->drupalGet('node/1/edit');
    $assert_session = $this->assertSession();
    $assert_session->fieldValueEquals('field_name_test[0][title]', '_none');
    $assert_session->fieldValueEquals('field_name_test[0][given]', '_none');
    $assert_session->fieldValueEquals('field_name_test[0][middle]', '_none');
    $assert_session->fieldValueEquals('field_name_test[0][family]', '_none');
    $assert_session->fieldValueEquals('field_name_test[0][generational]', '_none');
    $assert_session->fieldValueEquals('field_name_test[0][credentials]', '_none');
  }

  /**
   * Tests that "_none" can be used for text fields when name field is required.
   */
  public function testTextFieldNoneValueForRequiredNameField() {
    $this->drupalLogin($this->adminUser);

    // Create a name field and set it as required.
    $this->createNameField('field_name_test', 'node', 'page', [], [
      'required' => TRUE,
    ]);
    // Add the field to the form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page', 'default')
      ->setComponent('field_name_test', [
        'type' => 'name_default',
        'weight' => 1,
      ])
      ->save();

    // Add a node, first check if name field is indeed required.
    $this->drupalGet('node/add/page');
    $edit = [
      'title[0][value]' => 'Test node',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('field_name_test field is required.');

    // Now set the values.
    $edit = [
      'field_name_test[0][given]' => '_none',
      'field_name_test[0][family]' => '_none',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Basic page Test node has been created.');

    // Load the node and assert that the value "_none" was saved for fields
    // 'given' and 'family'.
    $node = Node::load(1);
    $this->assertEquals('_none', $node->field_name_test->given);
    $this->assertEquals('_none', $node->field_name_test->family);

    // Go the node edit form and assert that "_none" is used for 'given' and
    // 'family'.
    $this->drupalGet('node/1/edit');
    $assert_session = $this->assertSession();
    $assert_session->fieldValueEquals('field_name_test[0][given]', '_none');
    $assert_session->fieldValueEquals('field_name_test[0][family]', '_none');
  }

  /**
   * Tests that multiple unselected values are properly handled.
   */
  public function testMultipleNoneValues() {
    $this->drupalLogin($this->adminUser);

    // Create a name field.
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');

    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.2.2',
      fn() => $this->getSession()->getPage()->clickLink('Name'),
      fn() => !$this->getSession()->getPage()->fillField('new_storage_type', 'name') && $this->getSession()->getPage()->pressButton('Continue')
    );

    $new_name_field = [
      'label' => 'Test name',
      'field_name' => 'name_test',
    ];
    $this->submitForm($new_name_field, 'Continue');

    // Configure field settings with title and generational as select fields.
    $field_settings = [
      'settings[components][title]' => TRUE,
      'settings[components][generational]' => TRUE,
      'settings[field_type][title]' => 'select',
      'settings[field_type][generational]' => 'select',
      'settings[title_options]' => "-- --\nMr.\nMrs.\nDr.",
      'settings[generational_options]' => "-- --\nJr.\nSr.\nIII",
    ];

    DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '11.2.2',
      fn() => $this->submitForm($field_settings, 'Save'),
      fn() => $this->submitForm($field_settings, 'Save settings')
    );

    // Create a node with both title and generational unselected.
    $this->drupalGet('node/add/page');
    $edit = [
      'title[0][value]' => 'Test node',
      'field_name_test[0][title]' => '_none',
      'field_name_test[0][generational]' => '_none',
    ];
    $this->submitForm($edit, 'Save');

    // Load the node and verify both fields are empty strings.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => 'Test node']);
    $node = reset($nodes);

    $this->assertEquals('', $node->field_name_test->title, 'Title field is empty');
    $this->assertEquals('', $node->field_name_test->generational, 'Generational field is empty');
  }

  /**
   * Tests the configurable wrapper type for the Name widget.
   */
  public function testWidgetWrapperTypeOptions(): void {
    $this->drupalLogin($this->adminUser);

    $this->createNameField('field_name_wrapper', 'node', 'page');

    $display = $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page', 'default');
    $component = [
      'type' => 'name_default',
      'weight' => 1,
      'settings' => [
        'override_field_settings' => TRUE,
      ],
    ];

    $component['settings']['wrapper_type'] = 'fieldset';
    $display->setComponent('field_name_wrapper', $component)->save();
    $this->assertWidgetWrapperTag('fieldset');

    $component['settings']['wrapper_type'] = 'details';
    $display->setComponent('field_name_wrapper', $component)->save();
    $this->assertWidgetWrapperTag('details');

    $component['settings']['wrapper_type'] = 'container';
    $display->setComponent('field_name_wrapper', $component)->save();
    $this->assertWidgetWrapperTag('container');
  }

  /**
   * Asserts a Name widget renders with the expected wrapper tag.
   */
  private function assertWidgetWrapperTag(string $wrapper_type): void {
    $this->drupalGet('node/add/page');
    $html = $this->getSession()->getPage()->getContent();

    $this->assertStringContainsString(
      'name-widget-wrapper',
      $html,
      'The Name widget layout wrapper is rendered.',
    );

    if ($wrapper_type === 'container') {
      $fieldset_pattern = '@<fieldset\b[^>]*>.*?name-widget-wrapper@si';
      $details_pattern = '@<details\b[^>]*>.*?name-widget-wrapper@si';
      $this->assertSame(0, preg_match($fieldset_pattern, $html));
      $this->assertSame(0, preg_match($details_pattern, $html));
      return;
    }

    $pattern = '@<' . $wrapper_type . '\b[^>]*>.*?name-widget-wrapper@si';
    $this->assertSame(1, preg_match($pattern, $html));
  }

}

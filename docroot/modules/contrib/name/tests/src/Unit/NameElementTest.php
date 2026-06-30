<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Element\Name;
use Drupal\name\Service\WidgetLayoutInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Name element.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Element\Name
 */
class NameElementTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Tests element creation through the container factory.
   *
   * @covers ::__construct
   * @covers ::create
   * @covers ::getInfo
   */
  public function testCreateInjectsFieldTypeManager(): void {
    $field_type_manager = $this->createMock(FieldTypePluginManagerInterface::class);
    $field_type_manager->expects($this->once())
      ->method('getDefaultFieldSettings')
      ->with('name')
      ->willReturn([
        'minimum_components' => ['given' => 'given'],
        'allow_family_or_given' => TRUE,
        'field_type' => [
          'title' => 'select',
          'generational' => 'select',
        ],
        'size' => [
          'title' => 8,
          'given' => 32,
          'middle' => 32,
          'family' => 32,
          'generational' => 8,
          'credentials' => 32,
        ],
        'max_length' => [
          'title' => 16,
          'given' => 255,
          'middle' => 255,
          'family' => 255,
          'generational' => 16,
          'credentials' => 255,
        ],
        'title_options' => ['Dr.' => 'Dr.'],
        'generational_options' => ['Jr.' => 'Jr.'],
      ]);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.field.field_type', $field_type_manager);
    $container->set('name.component_metadata', new class {

      /**
       * Returns translated component labels for tests.
       */
      public function getTranslations(): array {
        return [
          'title' => 'Title',
          'given' => 'Given',
          'middle' => 'Middle',
          'family' => 'Family',
          'generational' => 'Generational',
          'credentials' => 'Credentials',
        ];
      }

    });

    $original_container = \Drupal::hasContainer() ? \Drupal::getContainer() : NULL;
    \Drupal::setContainer($container);

    try {
      $element = Name::create($container, [], 'name', []);
      $info = $element->getInfo();
    }
    finally {
      if ($original_container instanceof ContainerInterface) {
        \Drupal::setContainer($original_container);
      }
    }

    $this->assertInstanceOf(Name::class, $element);
    $this->assertSame(['given' => 'given'], $info['#minimum_components']);
    $this->assertTrue($info['#allow_family_or_given']);
    $this->assertSame('select', $info['#components']['title']['type']);
  }

  /**
   * Tests the trustedCallbacks method.
   *
   * @covers ::trustedCallbacks
   */
  public function testTrustedCallbacks(): void {
    $callbacks = Name::trustedCallbacks();
    $this->assertIsArray($callbacks);
    $this->assertContains('preRender', $callbacks);
    $this->assertCount(1, $callbacks);
  }

  /**
   * Tests the valueCallback method with FALSE input.
   *
   * @covers ::valueCallback
   */
  public function testValueCallbackWithFalseInput(): void {
    $element = [
      '#default_value' => [
        'title' => 'Dr.',
        'given' => 'John',
        'family' => 'Doe',
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::valueCallback($element, FALSE, $form_state);

    $expected = [
      'title' => 'Dr.',
      'given' => 'John',
      'family' => 'Doe',
      'middle' => '',
      'generational' => '',
      'credentials' => '',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the valueCallback method with valid input.
   *
   * @covers ::valueCallback
   */
  public function testValueCallbackWithValidInput(): void {
    $element = [];
    $input = [
      'title' => 'Dr.',
      'given' => 'John',
      'middle' => 'Michael',
      'family' => 'Doe',
      'generational' => 'Jr.',
      'credentials' => 'PhD',
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::valueCallback($element, $input, $form_state);

    $expected = [
      'title' => 'Dr.',
      'given' => 'John',
      'middle' => 'Michael',
      'family' => 'Doe',
      'generational' => 'Jr.',
      'credentials' => 'PhD',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the valueCallback method with scalar input conversion.
   *
   * @covers ::valueCallback
   */
  public function testValueCallbackWithScalarInputConversion(): void {
    $element = [];
    $input = [
      'title' => 123,
      'given' => TRUE,
      'family' => 456.78,
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::valueCallback($element, $input, $form_state);

    $expected = [
      'title' => '123',
      'given' => '1',
      'family' => '456.78',
      'middle' => '',
      'generational' => '',
      'credentials' => '',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the valueCallback method with invalid input keys.
   *
   * @covers ::valueCallback
   */
  public function testValueCallbackWithInvalidInputKeys(): void {
    $element = [];
    $input = [
      'title' => 'Dr.',
      'invalid_key' => 'should_be_ignored',
      'given' => 'John',
      'another_invalid' => 'also_ignored',
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::valueCallback($element, $input, $form_state);

    $expected = [
      'title' => 'Dr.',
      'given' => 'John',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the valueCallback method with nested array input.
   *
   * @covers ::valueCallback
   */
  public function testValueCallbackWithNestedArrayInput(): void {
    $element = [];
    $input = [
      'title' => ['nested' => 'array'],
      'given' => 'John',
      'family' => ['should' => 'be_ignored'],
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::valueCallback($element, $input, $form_state);

    $expected = [
      'title' => '',
      'given' => 'John',
      'family' => '',
      'middle' => '',
      'generational' => '',
      'credentials' => '',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the valueCallback method with empty input.
   *
   * @covers ::valueCallback
   */
  public function testValueCallbackWithEmptyInput(): void {
    $element = [];
    $input = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::valueCallback($element, $input, $form_state);

    $expected = [
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests the valueCallback method with NULL input.
   *
   * @covers ::valueCallback
   */
  public function testValueCallbackWithNullInput(): void {
    $element = [];
    $input = NULL;
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::valueCallback($element, $input, $form_state);

    $expected = [
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests that renderComponent() adds expected wrapper classes.
   *
   * @covers ::renderComponent
   * @covers ::buildElementAttributes
   */
  public function testRenderComponentAddsClasses(): void {
    $component = $this->buildRenderComponentTextComponent();
    $base_element = $this->buildRenderComponentBaseElement();

    $core = Name::renderComponent(['given' => $component], 'given', $base_element, TRUE);
    $non_core = Name::renderComponent(['given' => $component], 'given', $base_element, FALSE);

    $this->assertContains('name-element', $core['#attributes']['class']);
    $this->assertContains('name-given', $core['#attributes']['class']);
    $this->assertContains('name-core-component', $core['#attributes']['class']);
    $this->assertContains('name-element', $non_core['#attributes']['class']);
    $this->assertContains('name-given', $non_core['#attributes']['class']);
    $this->assertNotContains('name-core-component', $non_core['#attributes']['class']);
  }

  /**
   * Tests that base properties are copied to form element attributes.
   *
   * @covers ::renderComponent
   * @covers ::buildElementAttributes
   */
  public function testRenderComponentSetsBaseAttributes(): void {
    $component = $this->buildRenderComponentTextComponent([
      'type' => 'textfield',
      'title' => 'Given Name',
      'size' => 64,
      'maxlength' => 128,
    ]);
    $element = Name::renderComponent(['given' => $component], 'given', $this->buildRenderComponentBaseElement(), TRUE);

    $this->assertSame('textfield', $element['#type']);
    $this->assertSame('Given Name', $element['#title']);
    $this->assertSame(64, $element['#size']);
    $this->assertSame(128, $element['#maxlength']);
  }

  /**
   * Tests default value assignment when #value contains component data.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentSetsDefaultValue(): void {
    $component = $this->buildRenderComponentTextComponent();
    $with_value = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(['#value' => ['given' => 'Pat']]),
      TRUE,
    );
    $without_value = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(['#value' => []]),
      TRUE,
    );

    $this->assertSame('Pat', $with_value['#default_value']);
    $this->assertArrayNotHasKey('#default_value', $without_value);
  }

  /**
   * Tests select option normalization when placeholder entry is present.
   *
   * @covers ::renderComponent
   * @covers ::normalizeSelectOptions
   */
  public function testRenderComponentNormalizesSelectWithPlaceholder(): void {
    $component = [
      'type' => 'select',
      'title' => 'Title',
      'title_display' => 'none',
      'size' => 10,
      'maxlength' => 32,
      'options' => [
        'placeholder' => '-- Select title --',
        'dr' => 'Dr.',
        'mr' => 'Mr.',
      ],
      'autocomplete' => FALSE,
    ];
    $element = Name::renderComponent(
      ['title' => $component],
      'title',
      $this->buildRenderComponentBaseElement(['#value' => ['title' => 'Dr.']]),
      TRUE,
    );

    $this->assertSame(1, $element['#size']);
    $this->assertSame('_none', $element['#empty_value']);
    $this->assertSame('Select title --', $element['#empty_option']);
    $this->assertSame(['Dr.' => 'Dr.', 'Mr.' => 'Mr.'], $element['#options']);
    $this->assertSame('Dr.', $element['#default_value']);
  }

  /**
   * Tests select keeps _none when options are pre-normalized.
   *
   * When NameOptionService is used, an option that starts with '--' gets mapped
   * to the '_none' key. The label of this option must not become a real option
   * value.
   *
   * @covers ::renderComponent
   * @covers ::normalizeSelectOptions
   */
  public function testRenderComponentNormalizesSelectWithCustomEmptyOption(): void {
    $component = [
      'type' => 'select',
      'title' => 'Title',
      'title_display' => 'none',
      'size' => 10,
      'maxlength' => 32,
      'options' => [
        '_none' => 'Please select a Title',
        'Dr.' => 'Dr.',
        'Mr.' => 'Mr.',
      ],
      'autocomplete' => FALSE,
    ];
    $element = Name::renderComponent(
      ['title' => $component],
      'title',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertSame('_none', $element['#empty_value']);
    $this->assertSame('Please select a Title', $element['#empty_option']);
    $this->assertArrayNotHasKey('Please select a Title', $element['#options']);
    $this->assertSame(['Dr.' => 'Dr.', 'Mr.' => 'Mr.'], $element['#options']);
  }

  /**
   * Tests select option normalization when no placeholder is present.
   *
   * @covers ::renderComponent
   * @covers ::normalizeSelectOptions
   */
  public function testRenderComponentNormalizesSelectWithoutPlaceholder(): void {
    $component = [
      'type' => 'select',
      'title' => 'Title',
      'title_display' => 'none',
      'size' => 10,
      'maxlength' => 32,
      'options' => [
        'dr' => 'Dr.',
        'mr' => 'Mr.',
      ],
      'autocomplete' => FALSE,
    ];
    $element = Name::renderComponent(
      ['title' => $component],
      'title',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertArrayNotHasKey('#empty_value', $element);
    $this->assertArrayNotHasKey('#empty_option', $element);
    $this->assertSame(['Dr.' => 'Dr.', 'Mr.' => 'Mr.'], $element['#options']);
  }

  /**
   * Tests that array-valued attributes are merged.
   *
   * @covers ::renderComponent
   * @covers ::buildElementAttributes
   */
  public function testRenderComponentMergesAttributeArrays(): void {
    $component = $this->buildRenderComponentTextComponent([
      '#attributes' => [
        'class' => ['seed-class'],
      ],
      'attributes' => [
        'class' => ['extra-class'],
      ],
    ]);
    $element = Name::renderComponent(['given' => $component], 'given', $this->buildRenderComponentBaseElement(), TRUE);

    $this->assertContains('seed-class', $element['#attributes']['class']);
    $this->assertContains('extra-class', $element['#attributes']['class']);
  }

  /**
   * Tests that scalar attributes are concatenated with a space separator.
   *
   * @covers ::renderComponent
   * @covers ::buildElementAttributes
   */
  public function testRenderComponentMergesAttributeStrings(): void {
    $component = $this->buildRenderComponentTextComponent([
      '#attributes' => [
        'data-role' => 'seed',
      ],
      'attributes' => [
        'data-role' => 'merged',
      ],
    ]);
    $element = Name::renderComponent(['given' => $component], 'given', $this->buildRenderComponentBaseElement(), TRUE);

    $this->assertSame('seed merged', $element['#attributes']['data-role']);
  }

  /**
   * Tests autocomplete metadata is copied for non-select components.
   *
   * @covers ::renderComponent
   * @covers ::buildElementAttributes
   */
  public function testRenderComponentSetsAutocomplete(): void {
    $component = $this->buildRenderComponentTextComponent([
      'autocomplete' => [
        '#autocomplete_route_name' => 'name.autocomplete',
      ],
    ]);
    $element = Name::renderComponent(['given' => $component], 'given', $this->buildRenderComponentBaseElement(), TRUE);

    $this->assertSame('name.autocomplete', $element['#autocomplete_route_name']);
  }

  /**
   * Tests required flags are suppressed in default value widget context.
   *
   * @covers ::renderComponent
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentSuppressesRequiredFlagsInDefaultValueContext(): void {
    $base_element = $this->buildRenderComponentBaseElement([
      '#field_parents' => ['default_value_input'],
    ]);
    $element = Name::renderComponent(
      ['given' => $this->buildRenderComponentTextComponent(['title_display' => 'title'])],
      'given',
      $base_element,
      TRUE,
    );

    $this->assertArrayNotHasKey('#required', $element);
    $this->assertArrayNotHasKey('#label_attributes', $element);
  }

  /**
   * Tests required flags are suppressed when field parent metadata is missing.
   *
   * @covers ::renderComponent
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentSuppressesRequiredFlagsWhenParentsMissing(): void {
    $base_element = $this->buildRenderComponentBaseElement();
    unset($base_element['#field_parents']);

    $element = Name::renderComponent(
      ['given' => $this->buildRenderComponentTextComponent(['title_display' => 'title'])],
      'given',
      $base_element,
      TRUE,
    );

    $this->assertArrayNotHasKey('#required', $element);
    $this->assertArrayNotHasKey('#label_attributes', $element);
  }

  /**
   * Tests that non-core components never receive required marker flags.
   *
   * @covers ::renderComponent
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentNonCoreIgnoresRequiredFlags(): void {
    $element = Name::renderComponent(
      ['given' => $this->buildRenderComponentTextComponent(['title_display' => 'title'])],
      'given',
      $this->buildRenderComponentBaseElement(),
      FALSE,
    );

    $this->assertArrayNotHasKey('#required', $element);
    $this->assertArrayNotHasKey('#label_attributes', $element);
  }

  /**
   * Tests that title and generational values do not make a name non-empty.
   *
   * @covers ::validateIsEmpty
   */
  public function testValidateIsEmptyIgnoresTitleAndGenerational(): void {
    $item = [
      'title' => 'Dr.',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => 'Jr.',
      'credentials' => '',
    ];

    $this->assertTrue(Name::validateIsEmpty($item));
  }

  /**
   * Tests that required name components make a name non-empty.
   *
   * @covers ::validateIsEmpty
   */
  public function testValidateIsEmptyDetectsCoreValues(): void {
    $base = [
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ];

    foreach (['given', 'middle', 'family', 'credentials'] as $key) {
      $item = $base;
      $item[$key] = 'present';
      $this->assertFalse(Name::validateIsEmpty($item), sprintf('%s should make the element non-empty.', $key));
    }
  }

  /**
   * Tests that the description render array receives a for attribute.
   *
   * @covers ::componentDescriptionAfterBuildLabelAlter
   */
  public function testComponentDescriptionAfterBuildLabelAlterAddsFor(): void {
    $element = [
      '#id' => 'edit-name-given',
      '#description' => [
        '#theme' => 'form_element_label',
        '#title' => 'Given',
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::componentDescriptionAfterBuildLabelAlter($element, $form_state);

    $this->assertSame('edit-name-given', $result['#description']['#for']);
  }

  /**
   * Tests that non-array descriptions remain unchanged.
   *
   * @covers ::componentDescriptionAfterBuildLabelAlter
   */
  public function testComponentDescriptionAfterBuildLabelAlterLeavesOtherValuesAlone(): void {
    $element = [
      '#id' => 'edit-name-given',
      '#description' => 'Help text',
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = Name::componentDescriptionAfterBuildLabelAlter($element, $form_state);

    $this->assertSame($element, $result);
  }

  /**
   * Tests the 'title' display with required flags fully enabled.
   *
   * Exercises the flag_required_input and show_component_required_marker
   * branches inside applyTitleDisplay's 'title' case (lines 381-388).
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentApplyTitleDisplayTitleWithRequiredEnabled(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'title',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertSame('before', $element['#title_display']);
    $this->assertTrue($element['#required']);
    $this->assertContains('js-form-required', $element['#label_attributes']['class']);
    $this->assertContains('form-required', $element['#label_attributes']['class']);
  }

  /**
   * Tests the 'title' display with marker enabled only.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentApplyTitleDisplayTitleWithMarkerOnly(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'title',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement([
        '#flag_required_input' => FALSE,
      ]),
      TRUE,
    );

    $this->assertSame('before', $element['#title_display']);
    $this->assertArrayNotHasKey('#required', $element);
    $this->assertContains('js-form-required', $element['#label_attributes']['class']);
    $this->assertContains('form-required', $element['#label_attributes']['class']);
  }

  /**
   * Tests the 'title' display with required input enabled only.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentApplyTitleDisplayTitleWithRequiredOnly(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'title',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement([
        '#show_component_required_marker' => FALSE,
      ]),
      TRUE,
    );

    $this->assertSame('before', $element['#title_display']);
    $this->assertTrue($element['#required']);
    $this->assertArrayNotHasKey('#label_attributes', $element);
  }

  /**
   * Tests the 'placeholder' display without required markers.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   */
  public function testRenderComponentApplyTitleDisplayPlaceholder(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'placeholder',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      FALSE,
    );

    $this->assertSame('Given', $element['#attributes']['placeholder']);
    $this->assertSame('invisible', $element['#title_display']);
    $this->assertArrayNotHasKey('#required', $element);
  }

  /**
   * Tests the 'placeholder' display with the required marker appended.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   */
  public function testRenderComponentApplyTitleDisplayPlaceholderWithRequiredMarker(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'placeholder',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertStringContainsString(
      'Required',
      (string) $element['#attributes']['placeholder'],
    );
    $this->assertTrue($element['#required']);
    $this->assertSame('invisible', $element['#title_display']);
  }

  /**
   * Tests the 'attribute' display without required markers.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   */
  public function testRenderComponentApplyTitleDisplayAttribute(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'attribute',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      FALSE,
    );

    $this->assertSame('attribute', $element['#title_display']);
    $this->assertSame('Given', $element['#attributes']['title']);
  }

  /**
   * Tests the 'attribute' display with the required marker appended.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   */
  public function testRenderComponentApplyTitleDisplayAttributeWithRequiredMarker(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'attribute',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertSame('attribute', $element['#title_display']);
    $this->assertStringContainsString(
      'Required',
      (string) $element['#attributes']['title'],
    );
  }

  /**
   * Tests the 'description' display mode (default case in the switch).
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   */
  public function testRenderComponentApplyTitleDisplayDescription(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'description',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertSame('invisible', $element['#title_display']);
    $this->assertTrue($element['#required']);
    $this->assertIsArray($element['#description']);
    $this->assertSame('form_element_label', $element['#description']['#theme']);
    $this->assertSame('Given', $element['#description']['#title']);
    $this->assertContains(
      [Name::class, 'componentDescriptionAfterBuildLabelAlter'],
      $element['#after_build'],
    );
  }

  /**
   * Tests that new attributes are copied to the form element.
   *
   * @covers ::renderComponent
   * @covers ::buildElementAttributes
   */
  public function testRenderComponentSetsNewAttribute(): void {
    $component = $this->buildRenderComponentTextComponent([
      'attributes' => [
        'data-custom' => 'value',
      ],
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertSame('value', $element['#attributes']['data-custom']);
  }

  /**
   * Tests the 'none' display mode with required input enabled.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentApplyTitleDisplayNoneWithRequired(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'none',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement(),
      TRUE,
    );

    $this->assertSame('invisible', $element['#title_display']);
    $this->assertTrue($element['#required']);
  }

  /**
   * Tests the 'none' display mode without required input enabled.
   *
   * @covers ::renderComponent
   * @covers ::applyTitleDisplay
   * @covers ::resolveRequiredFlags
   */
  public function testRenderComponentApplyTitleDisplayNoneWithoutRequired(): void {
    $component = $this->buildRenderComponentTextComponent([
      'title_display' => 'none',
    ]);
    $element = Name::renderComponent(
      ['given' => $component],
      'given',
      $this->buildRenderComponentBaseElement([
        '#flag_required_input' => FALSE,
      ]),
      TRUE,
    );

    $this->assertSame('invisible', $element['#title_display']);
    $this->assertArrayNotHasKey('#required', $element);
  }

  /**
   * Tests the default wrapper fallback.
   *
   * @covers ::applyDetailsWrapper
   */
  public function testApplyDetailsWrapperDefaultsToFieldset(): void {
    $element = [];

    Name::applyDetailsWrapper($element);

    $this->assertSame(['fieldset'], $element['#theme_wrappers']);
    $this->assertArrayNotHasKey('#prefix', $element);
    $this->assertArrayNotHasKey('#suffix', $element);
  }

  /**
   * Tests supported non-details wrapper types.
   *
   * @covers ::applyDetailsWrapper
   */
  public function testApplyDetailsWrapperKeepsContainer(): void {
    $element = ['#wrapper_type' => 'container'];

    Name::applyDetailsWrapper($element);

    $this->assertSame(['container'], $element['#theme_wrappers']);
    $this->assertArrayNotHasKey('#prefix', $element);
    $this->assertArrayNotHasKey('#suffix', $element);
  }

  /**
   * Tests invalid wrapper type fallback.
   *
   * @covers ::applyDetailsWrapper
   */
  public function testApplyDetailsWrapperRejectsInvalidWrapper(): void {
    $element = ['#wrapper_type' => 'invalid'];

    Name::applyDetailsWrapper($element);

    $this->assertSame(['fieldset'], $element['#theme_wrappers']);
    $this->assertArrayNotHasKey('#prefix', $element);
    $this->assertArrayNotHasKey('#suffix', $element);
  }

  /**
   * Tests details wrapper markup for open required elements.
   *
   * @covers ::applyDetailsWrapper
   */
  public function testApplyDetailsWrapperBuildsOpenRequiredDetails(): void {
    $element = [
      '#wrapper_type' => 'details',
      '#title' => 'Name <Widget>',
      '#required' => TRUE,
      '#open' => TRUE,
    ];

    Name::applyDetailsWrapper($element);

    $this->assertSame(['container'], $element['#theme_wrappers']);
    $this->assertStringStartsWith('<details open><summary', $element['#prefix']);
    $this->assertStringContainsString(
      'class="js-form-required form-required"',
      $element['#prefix'],
    );
    $this->assertStringContainsString(
      '>Name &lt;Widget&gt;</summary>',
      $element['#prefix'],
    );
    $this->assertSame('</details>', $element['#suffix']);
  }

  /**
   * Tests details wrapper markup defaults without a title.
   *
   * @covers ::applyDetailsWrapper
   */
  public function testApplyDetailsWrapperBuildsClosedUntitledDetails(): void {
    $element = ['#wrapper_type' => 'details'];

    Name::applyDetailsWrapper($element);

    $this->assertSame(['container'], $element['#theme_wrappers']);
    $this->assertSame('<details><summary></summary>', $element['#prefix']);
    $this->assertSame('</details>', $element['#suffix']);
  }

  /**
   * Tests default widget layout fallback without the service.
   *
   * @covers ::resolveWidgetLayout
   */
  public function testResolveWidgetLayoutDefaultsWithoutService(): void {
    $layout = Name::resolveWidgetLayout(NULL, NULL);

    $this->assertSame([], $layout['library']);
    $this->assertSame(
      ['name-widget-wrapper'],
      $layout['wrapper_attributes']['class'],
    );
  }

  /**
   * Tests default widget layout fallback for an empty service response.
   *
   * @covers ::resolveWidgetLayout
   */
  public function testResolveWidgetLayoutDefaultsForEmptyLayouts(): void {
    $layout = Name::resolveWidgetLayout(
      $this->buildWidgetLayoutService([]),
      'inline',
    );

    $this->assertSame([], $layout['library']);
    $this->assertSame(
      ['name-widget-wrapper'],
      $layout['wrapper_attributes']['class'],
    );
  }

  /**
   * Tests requested widget layout selection.
   *
   * @covers ::resolveWidgetLayout
   */
  public function testResolveWidgetLayoutUsesRequestedLayout(): void {
    $layout = Name::resolveWidgetLayout(
      $this->buildWidgetLayoutService([
        'stacked' => [
          'library' => [],
          'wrapper_attributes' => [
            'class' => ['stacked-wrapper'],
          ],
        ],
        'inline' => [
          'library' => ['name/widget.inline'],
          'wrapper_attributes' => [
            'class' => ['form--inline', 'clearfix'],
          ],
        ],
      ]),
      'inline',
    );

    $this->assertSame(['name/widget.inline'], $layout['library']);
    $this->assertSame(
      ['form--inline', 'clearfix', 'name-widget-wrapper'],
      $layout['wrapper_attributes']['class'],
    );
  }

  /**
   * Tests stacked widget layout fallback.
   *
   * @covers ::resolveWidgetLayout
   */
  public function testResolveWidgetLayoutFallsBackToStacked(): void {
    $layout = Name::resolveWidgetLayout(
      $this->buildWidgetLayoutService([
        'stacked' => [
          'wrapper_attributes' => [
            'class' => ['stacked-wrapper'],
          ],
        ],
        'inline' => [
          'wrapper_attributes' => [
            'class' => ['inline-wrapper'],
          ],
        ],
      ]),
      'missing',
    );

    $this->assertSame([], $layout['library']);
    $this->assertSame(
      ['stacked-wrapper', 'name-widget-wrapper'],
      $layout['wrapper_attributes']['class'],
    );
  }

  /**
   * Tests first widget layout fallback when stacked is absent.
   *
   * @covers ::resolveWidgetLayout
   */
  public function testResolveWidgetLayoutFallsBackToFirstLayout(): void {
    $layout = Name::resolveWidgetLayout(
      $this->buildWidgetLayoutService([
        'inline' => [
          'wrapper_attributes' => [
            'class' => ['inline-wrapper'],
          ],
        ],
        'custom' => [
          'wrapper_attributes' => [
            'class' => ['custom-wrapper'],
          ],
        ],
      ]),
      NULL,
    );

    $this->assertSame(
      ['inline-wrapper', 'name-widget-wrapper'],
      $layout['wrapper_attributes']['class'],
    );
  }

  /**
   * Builds a standard base element for renderComponent unit tests.
   *
   * @param array $overrides
   *   Optional values to override.
   *
   * @return array
   *   Base element.
   */
  private function buildRenderComponentBaseElement(array $overrides = []): array {
    return $overrides + [
      '#required' => TRUE,
      '#show_component_required_marker' => TRUE,
      '#flag_required_input' => TRUE,
      '#field_parents' => ['field_name'],
      '#value' => ['given' => 'Pat'],
    ];
  }

  /**
   * Builds a text component for renderComponent unit tests.
   *
   * @param array $overrides
   *   Optional values to override.
   *
   * @return array
   *   Component definition.
   */
  private function buildRenderComponentTextComponent(array $overrides = []): array {
    return $overrides + [
      'type' => 'textfield',
      'title' => 'Given',
      'title_display' => 'none',
      'size' => 32,
      'maxlength' => 255,
      'autocomplete' => FALSE,
    ];
  }

  /**
   * Builds a widget layout service test double.
   *
   * @param array $layouts
   *   Layout definitions returned by the service.
   *
   * @return \Drupal\name\Service\WidgetLayoutInterface
   *   A widget layout service test double.
   */
  private function buildWidgetLayoutService(array $layouts): WidgetLayoutInterface {
    return new class($layouts) implements WidgetLayoutInterface {

      /**
       * The layout definitions returned by the service.
       *
       * @var array
       */
      private array $layouts;

      /**
       * Constructs a widget layout service test double.
       *
       * @param array $layouts
       *   Layout definitions returned by the service.
       */
      public function __construct(array $layouts) {
        $this->layouts = $layouts;
      }

      /**
       * {@inheritdoc}
       */
      public function getLayouts(): array {
        return $this->layouts;
      }

    };
  }

}

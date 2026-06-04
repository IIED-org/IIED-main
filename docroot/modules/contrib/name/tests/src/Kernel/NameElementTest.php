<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Element\Name;
use Drupal\name\Service\WidgetLayoutInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the Name element integration.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Element\Name
 */
class NameElementTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'system',
    'user',
  ];

  /**
   * The element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected ElementInfoManagerInterface $elementInfoManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);

    $this->elementInfoManager = $this->container->get('plugin.manager.element_info');
  }

  /**
   * Tests that the Name element is properly registered.
   *
   * @covers ::getInfo
   */
  public function testElementRegistration(): void {
    $info = $this->elementInfoManager->getInfo('name');
    $field_settings = $this->container->get('plugin.manager.field.field_type')
      ->getDefaultFieldSettings('name');

    $this->assertNotEmpty($info);
    $this->assertTrue($info['#input']);
    $this->assertContains([Name::class, 'process'], $info['#process']);
    $this->assertContains([Name::class, 'preRender'], $info['#pre_render']);
    $this->assertContains([Name::class, 'validateElement'], $info['#element_validate']);
    $this->assertContains('fieldset', $info['#theme_wrappers']);
    $this->assertSame([
      'title' => '',
      'given' => '',
      'middle' => '',
      'family' => '',
      'generational' => '',
      'credentials' => '',
    ], $info['#default_value']);
    $this->assertSame($field_settings['minimum_components'], $info['#minimum_components']);
    $this->assertSame($field_settings['allow_family_or_given'], $info['#allow_family_or_given']);
    $this->assertSame($field_settings['field_type']['title'], $info['#components']['title']['type']);
    $this->assertEquals($field_settings['title_options'], $info['#components']['title']['options']);
    $this->assertSame($field_settings['size']['given'], $info['#components']['given']['size']);
    $this->assertSame($field_settings['max_length']['family'], $info['#components']['family']['maxlength']);
  }

  /**
   * Tests process behavior for excluded and wrapped components.
   *
   * @covers ::process
   */
  public function testProcessSkipsExcludedComponentsAndAddsBreakClass(): void {
    $element = $this->elementInfoManager->getInfo('name');
    $element['#components']['middle']['exclude'] = TRUE;
    $element['#minimum_components'] = ['given' => 'given'];
    $element['#required'] = FALSE;
    unset($element['#value']);

    $form_state = new FormState();
    $result = Name::process($element, $form_state, []);

    $this->assertTrue($result['#tree']);
    $this->assertSame([], $result['#value']);
    $this->assertArrayHasKey('given', $result);
    $this->assertContains('name-core-component', $result['given']['#attributes']['class']);
    $this->assertArrayNotHasKey('middle', $result);
    $this->assertStringContainsString('name-credentials-wrapper', $result['credentials']['#prefix']);
    $this->assertStringContainsString('name-component-break', $result['credentials']['#prefix']);
  }

  /**
   * Tests that inline credentials do not receive the break wrapper class.
   *
   * @covers ::process
   */
  public function testProcessRespectsInlineCredentials(): void {
    $element = $this->elementInfoManager->getInfo('name');
    $element['#credentials_inline'] = TRUE;
    $element['#required'] = FALSE;
    $element['#value'] = ['credentials' => 'PhD'];

    $form_state = new FormState();
    $result = Name::process($element, $form_state, []);

    $this->assertStringContainsString('name-credentials-wrapper', $result['credentials']['#prefix']);
    $this->assertStringNotContainsString('name-component-break', $result['credentials']['#prefix']);
    $this->assertSame('PhD', $result['credentials']['#default_value']);
  }

  /**
   * Tests rendering a component for each title display mode.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentTitleDisplayVariants(): void {
    $base_element = $this->getBaseElement();

    $title = Name::renderComponent([
      'given' => $this->getTextComponent(['title_display' => 'title']),
    ], 'given', $base_element, TRUE);
    $this->assertSame('before', $title['#title_display']);
    $this->assertTrue($title['#required']);
    $this->assertContains('js-form-required', $title['#label_attributes']['class']);
    $this->assertContains('form-required', $title['#label_attributes']['class']);

    $placeholder = Name::renderComponent([
      'given' => $this->getTextComponent(['title_display' => 'placeholder']),
    ], 'given', $base_element, TRUE);
    $this->assertSame('invisible', $placeholder['#title_display']);
    $this->assertSame('Given (Required)', $placeholder['#attributes']['placeholder']);
    $this->assertTrue($placeholder['#required']);

    $none = Name::renderComponent([
      'given' => $this->getTextComponent(['title_display' => 'none']),
    ], 'given', $base_element, TRUE);
    $this->assertSame('invisible', $none['#title_display']);
    $this->assertTrue($none['#required']);

    $attribute = Name::renderComponent([
      'given' => $this->getTextComponent(['title_display' => 'attribute']),
    ], 'given', $base_element, TRUE);
    $this->assertSame('attribute', $attribute['#title_display']);
    $this->assertSame('Given (Required)', $attribute['#attributes']['title']);

    $description = Name::renderComponent([
      'given' => $this->getTextComponent(['title_display' => NULL]),
    ], 'given', $base_element, TRUE);
    $this->assertSame('invisible', $description['#title_display']);
    $this->assertTrue($description['#required']);
    $this->assertSame('form_element_label', $description['#description']['#theme']);
    $this->assertSame('Given', $description['#description']['#title']);
    $this->assertTrue($description['#description']['#required']);
    $this->assertContains([Name::class, 'componentDescriptionAfterBuildLabelAlter'], $description['#after_build']);
  }

  /**
   * Tests select option normalization for placeholder labels.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentNormalizesSelectOptions(): void {
    $element = Name::renderComponent([
      'title' => [
        'type' => 'select',
        'title' => 'Title',
        'title_display' => 'title',
        'size' => 10,
        'maxlength' => 32,
        'options' => [
          'placeholder' => '-- Select a title --',
          'dr' => 'Dr.',
          'mr' => 'Mr.',
        ],
        'autocomplete' => FALSE,
      ],
    ], 'title', $this->getBaseElement([
      '#value' => ['title' => 'Dr.'],
    ]), TRUE);

    $this->assertSame(1, $element['#size']);
    $this->assertSame('_none', $element['#empty_value']);
    $this->assertSame('Select a title --', $element['#empty_option']);
    $this->assertSame([
      'Dr.' => 'Dr.',
      'Mr.' => 'Mr.',
    ], $element['#options']);
    $this->assertSame('Dr.', $element['#default_value']);
  }

  /**
   * Tests select keeps _none when options are pre-normalized.
   *
   * When NameOptionService maps a '--'-prefixed option to the '_none' key, the
   * label must become #empty_option and must not appear as a real option value.
   *
   * @covers ::renderComponent
   * @covers ::normalizeSelectOptions
   */
  public function testRenderComponentNormalizesSelectWithCustomEmptyOption(): void {
    $element = Name::renderComponent([
      'title' => [
        'type'          => 'select',
        'title'         => 'Title',
        'title_display' => 'title',
        'size'          => 10,
        'maxlength'     => 32,
        'options'       => [
          '_none' => 'Please select a Title',
          'Dr.'   => 'Dr.',
          'Mr.'   => 'Mr.',
        ],
        'autocomplete' => FALSE,
      ],
    ], 'title', $this->getBaseElement(), TRUE);

    $this->assertSame('_none', $element['#empty_value']);
    $this->assertSame('Please select a Title', $element['#empty_option']);
    $this->assertArrayNotHasKey('Please select a Title', $element['#options']);
    $this->assertSame(['Dr.' => 'Dr.', 'Mr.' => 'Mr.'], $element['#options']);
  }

  /**
   * Tests attribute and autocomplete merging for text components.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentMergesAttributesAndAutocomplete(): void {
    $element = Name::renderComponent([
      'given' => $this->getTextComponent([
        '#attributes' => [
          'class' => ['seed-class'],
          'data-role' => 'seed',
        ],
        'attributes' => [
          'class' => ['extra-class'],
          'data-role' => 'merged',
          'data-extra' => 'fresh',
        ],
        'autocomplete' => [
          '#autocomplete_route_name' => 'name.autocomplete',
        ],
      ]),
    ], 'given', $this->getBaseElement(), TRUE);

    $this->assertContains('name-element', $element['#attributes']['class']);
    $this->assertContains('name-given', $element['#attributes']['class']);
    $this->assertContains('name-core-component', $element['#attributes']['class']);
    $this->assertContains('seed-class', $element['#attributes']['class']);
    $this->assertContains('extra-class', $element['#attributes']['class']);
    $this->assertSame('seed merged', $element['#attributes']['data-role']);
    $this->assertSame('fresh', $element['#attributes']['data-extra']);
    $this->assertSame('name.autocomplete', $element['#autocomplete_route_name']);
    $this->assertSame('Pat', $element['#default_value']);
  }

  /**
   * Tests placeholder display without required marker appending.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentPlaceholderTitleDisplayWithoutRequiredMarker(): void {
    $base_element = $this->getBaseElement([
      '#show_component_required_marker' => FALSE,
    ]);
    $element = Name::renderComponent([
      'given' => $this->getTextComponent(['title_display' => 'placeholder']),
    ], 'given', $base_element, TRUE);

    $this->assertSame('invisible', $element['#title_display']);
    $this->assertSame('Given', $element['#attributes']['placeholder']);
    $this->assertTrue($element['#required']);
  }

  /**
   * Tests attribute display without required marker appending.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentAttributeTitleDisplayWithoutRequiredMarker(): void {
    $base_element = $this->getBaseElement([
      '#show_component_required_marker' => FALSE,
    ]);
    $element = Name::renderComponent([
      'given' => $this->getTextComponent(['title_display' => 'attribute']),
    ], 'given', $base_element, TRUE);

    $this->assertSame('attribute', $element['#title_display']);
    $this->assertSame('Given', $element['#attributes']['title']);
  }

  /**
   * Tests select empty option fallback when placeholder label is blank.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentSelectEmptyLabelEmptyString(): void {
    $element = Name::renderComponent([
      'title' => [
        'type' => 'select',
        'title' => 'Title',
        'title_display' => 'title',
        'size' => 10,
        'maxlength' => 32,
        'options' => [
          'placeholder' => '--',
          'dr' => 'Dr.',
          'mr' => 'Mr.',
        ],
        'autocomplete' => FALSE,
      ],
    ], 'title', $this->getBaseElement([
      '#value' => ['title' => 'Mr.'],
    ]), TRUE);

    $this->assertSame('_none', $element['#empty_value']);
    $this->assertSame('--', $element['#empty_option']);
    $this->assertSame([
      'Dr.' => 'Dr.',
      'Mr.' => 'Mr.',
    ], $element['#options']);
  }

  /**
   * Tests that only the first dashed row is treated as placeholder.
   *
   * @covers ::renderComponent
   */
  public function testRenderComponentSelectFirstDashRowOnlyStripped(): void {
    $element = Name::renderComponent([
      'title' => [
        'type' => 'select',
        'title' => 'Title',
        'title_display' => 'title',
        'size' => 10,
        'maxlength' => 32,
        'options' => [
          'placeholder_1' => '-- Select one --',
          'placeholder_2' => '-- Second dashed row --',
          'dr' => 'Dr.',
        ],
        'autocomplete' => FALSE,
      ],
    ], 'title', $this->getBaseElement(), TRUE);

    $this->assertSame('Select one --', $element['#empty_option']);
    $this->assertSame([
      '-- Second dashed row --' => '-- Second dashed row --',
      'Dr.' => 'Dr.',
    ], $element['#options']);
  }

  /**
   * Tests validateElement when the validator service is unavailable.
   *
   * @covers ::validateElement
   */
  public function testValidateElementWithoutValidatorService(): void {
    $element = [
      '#title' => 'Name',
      '#value' => ['given' => 'Pat'],
    ];
    $form_state = new FormState();
    $original_container = \Drupal::getContainer();

    \Drupal::setContainer(new ContainerBuilder());

    try {
      $result = Name::validateElement($element, $form_state);
    }
    finally {
      \Drupal::setContainer($original_container);
    }

    $this->assertSame($element, $result);
  }

  /**
   * Tests the preRender method with default layout.
   *
   * @covers ::preRender
   */
  public function testPreRenderWithDefaultLayout(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
        '#options' => ['Dr.' => 'Dr.', 'Mr.' => 'Mr.'],
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      'family' => [
        '#type' => 'textfield',
        '#title' => 'Family Name',
      ],
      '#widget_layout' => 'stacked',
    ];

    $result = Name::preRender($element);

    // Check that the element structure is correct.
    $this->assertArrayHasKey('_name', $result);
    $this->assertArrayHasKey('#prefix', $result['_name']);
    $this->assertArrayHasKey('#suffix', $result['_name']);

    // Check that the wrapper div is created.
    $this->assertStringContainsString('<div', $result['_name']['#prefix']);
    $this->assertStringContainsString('</div>', $result['_name']['#suffix']);

    // Check that components are moved to _name.
    $this->assertArrayHasKey('title', $result['_name']);
    $this->assertArrayHasKey('given', $result['_name']);
    $this->assertArrayHasKey('family', $result['_name']);

    // Check that original components are removed.
    $this->assertArrayNotHasKey('title', $result);
    $this->assertArrayNotHasKey('given', $result);
    $this->assertArrayNotHasKey('family', $result);
  }

  /**
   * Tests the preRender method with inline layout.
   *
   * @covers ::preRender
   */
  public function testPreRenderWithInlineLayout(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      'family' => [
        '#type' => 'textfield',
        '#title' => 'Family Name',
      ],
      '#widget_layout' => 'inline',
    ];

    $result = Name::preRender($element);

    // Check that the element structure is correct.
    $this->assertArrayHasKey('_name', $result);
    $this->assertArrayHasKey('#prefix', $result['_name']);
    $this->assertArrayHasKey('#suffix', $result['_name']);

    // Check that the wrapper div is created.
    $this->assertStringContainsString('<div', $result['_name']['#prefix']);
    $this->assertStringContainsString('</div>', $result['_name']['#suffix']);

    // Check that components are moved to _name.
    $this->assertArrayHasKey('title', $result['_name']);
    $this->assertArrayHasKey('given', $result['_name']);
    $this->assertArrayHasKey('family', $result['_name']);
  }

  /**
   * Tests the preRender method with custom component layout.
   *
   * @covers ::preRender
   */
  public function testPreRenderWithCustomComponentLayout(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      'family' => [
        '#type' => 'textfield',
        '#title' => 'Family Name',
      ],
      '#component_layout' => 'custom_layout',
      '#widget_layout' => 'stacked',
    ];

    $result = Name::preRender($element);

    // Check that the element structure is correct.
    $this->assertArrayHasKey('_name', $result);
    $this->assertArrayHasKey('title', $result['_name']);
    $this->assertArrayHasKey('given', $result['_name']);
    $this->assertArrayHasKey('family', $result['_name']);
  }

  /**
   * Tests preRender with a supported component layout.
   *
   * @covers ::preRender
   */
  public function testPreRenderAppliesAsianComponentLayout(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      'family' => [
        '#type' => 'textfield',
        '#title' => 'Family Name',
      ],
      'generational' => [
        '#type' => 'select',
        '#title' => 'Generational',
        '#default_value' => 'Jr.',
      ],
      '#component_layout' => 'asian',
      '#widget_layout' => 'stacked',
    ];

    $result = Name::preRender($element);

    $this->assertSame(1, $result['_name']['family']['#weight']);
    $this->assertSame(3, $result['_name']['given']['#weight']);
    $this->assertSame(4, $result['_name']['title']['#weight']);
    $this->assertSame('', $result['_name']['generational']['#default_value']);
    $this->assertFalse($result['_name']['generational']['#access']);
  }

  /**
   * Tests the preRender method with library attachment.
   *
   * @covers ::preRender
   */
  public function testPreRenderWithLibraryAttachment(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      '#widget_layout' => 'inline',
    ];

    $result = Name::preRender($element);

    // Check that library is attached for inline layout.
    $this->assertArrayHasKey('#attached', $result);
    $this->assertArrayHasKey('library', $result['#attached']);
    $this->assertContains('name/widget.inline', $result['#attached']['library']);
  }

  /**
   * Tests the preRender method with all name components.
   *
   * @covers ::preRender
   */
  public function testPreRenderWithAllComponents(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      'middle' => [
        '#type' => 'textfield',
        '#title' => 'Middle Name',
      ],
      'family' => [
        '#type' => 'textfield',
        '#title' => 'Family Name',
      ],
      'generational' => [
        '#type' => 'select',
        '#title' => 'Generational',
      ],
      'credentials' => [
        '#type' => 'textfield',
        '#title' => 'Credentials',
      ],
      '#widget_layout' => 'stacked',
    ];

    $result = Name::preRender($element);

    // Check that all components are moved to _name.
    $this->assertArrayHasKey('title', $result['_name']);
    $this->assertArrayHasKey('given', $result['_name']);
    $this->assertArrayHasKey('middle', $result['_name']);
    $this->assertArrayHasKey('family', $result['_name']);
    $this->assertArrayHasKey('generational', $result['_name']);
    $this->assertArrayHasKey('credentials', $result['_name']);

    // Check that original components are removed.
    $this->assertArrayNotHasKey('title', $result);
    $this->assertArrayNotHasKey('given', $result);
    $this->assertArrayNotHasKey('middle', $result);
    $this->assertArrayNotHasKey('family', $result);
    $this->assertArrayNotHasKey('generational', $result);
    $this->assertArrayNotHasKey('credentials', $result);
  }

  /**
   * Tests the preRender method with empty element.
   *
   * @covers ::preRender
   */
  public function testPreRenderWithEmptyElement(): void {
    $element = [
      '#widget_layout' => 'stacked',
    ];

    $result = Name::preRender($element);

    // Check that the element structure is correct even with no components.
    $this->assertArrayHasKey('_name', $result);
    $this->assertArrayHasKey('#prefix', $result['_name']);
    $this->assertArrayHasKey('#suffix', $result['_name']);

    // Check that the wrapper div is created.
    $this->assertStringContainsString('<div', $result['_name']['#prefix']);
    $this->assertStringContainsString('</div>', $result['_name']['#suffix']);
  }

  /**
   * Tests the preRender method with invalid widget layout.
   *
   * @covers ::preRender
   */
  public function testPreRenderWithInvalidWidgetLayout(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
      ],
      'given' => [
        '#type' => 'textfield',
        '#title' => 'Given Name',
      ],
      '#widget_layout' => 'invalid_layout',
    ];

    $result = Name::preRender($element);

    // Should fall back to stacked layout.
    $this->assertArrayHasKey('_name', $result);
    $this->assertArrayHasKey('#prefix', $result['_name']);
    $this->assertArrayHasKey('#suffix', $result['_name']);

    // Check that the wrapper div is created.
    $this->assertStringContainsString('<div', $result['_name']['#prefix']);
    $this->assertStringContainsString('</div>', $result['_name']['#suffix']);
  }

  /**
   * Tests that unsupported wrapper types fall back to fieldset.
   *
   * @covers ::preRender
   */
  public function testPreRenderInvalidWrapperTypeFallsBackToFieldset(): void {
    $result = Name::preRender([
      '#widget_layout' => 'stacked',
      '#wrapper_type' => 'invalid_wrapper',
    ]);

    $this->assertSame(['fieldset'], $result['#theme_wrappers']);
    $this->assertArrayNotHasKey('#prefix', $result);
    $this->assertArrayNotHasKey('#suffix', $result);
  }

  /**
   * Tests details wrapper markup for open and required states.
   *
   * @covers ::preRender
   */
  public function testPreRenderDetailsWrapperWithOpenAndRequired(): void {
    $result = Name::preRender([
      '#widget_layout' => 'stacked',
      '#wrapper_type' => 'details',
      '#title' => 'Name <Widget>',
      '#required' => TRUE,
      '#open' => TRUE,
    ]);

    $this->assertSame(['container'], $result['#theme_wrappers']);
    $this->assertStringStartsWith('<details open><summary', $result['#prefix']);
    $this->assertStringContainsString(
      'class="js-form-required form-required"',
      $result['#prefix'],
    );
    $this->assertStringContainsString(
      '>Name &lt;Widget&gt;</summary>',
      $result['#prefix'],
    );
    $this->assertSame('</details>', $result['#suffix']);
  }

  /**
   * Tests details wrapper markup defaults for optional closed elements.
   *
   * @covers ::preRender
   */
  public function testPreRenderDetailsWrapperDefaultAttributes(): void {
    $result = Name::preRender([
      '#widget_layout' => 'stacked',
      '#wrapper_type' => 'details',
      '#title' => 'Name',
    ]);

    $this->assertSame(['container'], $result['#theme_wrappers']);
    $this->assertStringStartsWith('<details><summary>Name</summary>', $result['#prefix']);
    $this->assertStringNotContainsString(' open', $result['#prefix']);
    $this->assertStringNotContainsString('js-form-required', $result['#prefix']);
    $this->assertStringNotContainsString('form-required', $result['#prefix']);
    $this->assertSame('</details>', $result['#suffix']);
  }

  /**
   * Tests preRender fallback behavior without optional services.
   *
   * @covers ::preRender
   * @covers ::getComponentTranslations
   */
  public function testPreRenderWithoutOptionalServices(): void {
    $element = [
      'title' => [
        '#type' => 'select',
        '#title' => 'Title',
      ],
    ];
    $original_container = \Drupal::getContainer();

    \Drupal::setContainer(new ContainerBuilder());

    try {
      $result = Name::preRender($element);
    }
    finally {
      \Drupal::setContainer($original_container);
    }

    $this->assertArrayHasKey('_name', $result);
    $this->assertStringContainsString('name-widget-wrapper', $result['_name']['#prefix']);
    $this->assertArrayHasKey('title', $result);
    $this->assertArrayNotHasKey('title', $result['_name']);
  }

  /**
   * Tests that preRender appends the standard wrapper class when missing.
   *
   * @covers ::preRender
   */
  public function testPreRenderAddsMissingWrapperClass(): void {
    $container = new ContainerBuilder();
    $container->set('name.widget_layouts', new class implements WidgetLayoutInterface {

      /**
       * Returns a custom layout definition for the test container.
       */
      public function getLayouts(): array {
        return [
          'custom' => [
            'library' => [],
            'wrapper_attributes' => [
              'class' => ['custom-wrapper'],
            ],
          ],
        ];
      }

    });
    $container->set('name.component_metadata', new class {

      /**
       * Returns the translated component labels used by the test.
       */
      public function getTranslations(): array {
        return ['title' => 'Title'];
      }

    });

    $original_container = \Drupal::getContainer();
    \Drupal::setContainer($container);

    try {
      $result = Name::preRender([
        'title' => [
          '#type' => 'select',
          '#title' => 'Title',
        ],
        '#widget_layout' => 'custom',
      ]);
    }
    finally {
      \Drupal::setContainer($original_container);
    }

    $this->assertStringContainsString('custom-wrapper', $result['_name']['#prefix']);
    $this->assertStringContainsString('name-widget-wrapper', $result['_name']['#prefix']);
    $this->assertArrayHasKey('title', $result['_name']);
  }

  /**
   * Tests that the element implements TrustedCallbackInterface.
   */
  public function testTrustedCallbackInterface(): void {
    $callbacks = Name::trustedCallbacks();
    $this->assertIsArray($callbacks);
    $this->assertContains('preRender', $callbacks);
  }

  /**
   * Tests that the element extends FormElementBase.
   */
  public function testFormElementBaseInheritance(): void {
    $info = $this->elementInfoManager->getInfo('name');
    $this->assertNotEmpty($info);
    $this->assertTrue($info['#input']);
  }

  /**
   * Builds a standard base element for renderComponent tests.
   */
  private function getBaseElement(array $overrides = []): array {
    return $overrides + [
      '#required' => TRUE,
      '#show_component_required_marker' => TRUE,
      '#flag_required_input' => TRUE,
      '#field_parents' => ['field_name'],
      '#value' => ['given' => 'Pat'],
    ];
  }

  /**
   * Builds a standard text component definition.
   */
  private function getTextComponent(array $overrides = []): array {
    $component = [
      'type' => 'textfield',
      'title' => 'Given',
      'title_display' => 'description',
      'size' => 32,
      'maxlength' => 255,
      'autocomplete' => FALSE,
    ];

    foreach ($overrides as $key => $value) {
      if ($value === NULL) {
        unset($component[$key]);
        continue;
      }
      $component[$key] = $value;
    }

    return $component;
  }

}

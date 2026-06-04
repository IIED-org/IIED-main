<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Traits;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\NameComponentMetadataService;
use Drupal\name\Traits\NameFieldSettingsTrait;
use Drupal\name\Traits\NameFormSettingsHelperTrait;

/**
 * Tests for NameFieldSettingsTrait defaults, form, and validators.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameFieldSettingsTrait
 */
class NameFieldSettingsTraitTest extends UnitTestCase {

  /**
   * Consumer of the trait used as the test subject.
   */
  protected object $traitObject;

  /**
   * Mocked module handler so taxonomy/help toggles can be exercised.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $renderer = $this->createMock(RendererInterface::class);
    $renderer->method('render')->willReturnCallback(static function (array $build): string {
      if (isset($build['#title'])) {
        return '<a>' . (string) $build['#title'] . '</a>';
      }
      return '';
    });
    $path_validator = $this->createMock(PathValidatorInterface::class);
    $path_validator->method('getUrlIfValidWithoutAccessCheck')
      ->willReturnCallback(static fn (string $path): Url => Url::fromUri('base:' . $path));
    $link_generator = $this->createMock(LinkGeneratorInterface::class);
    $link_generator->method('generate')
      ->willReturnCallback(static function ($text, Url $url): GeneratedLink {
        return (new GeneratedLink())
          ->setGeneratedLink('<a>' . (string) $text . '</a>');
      });

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('name.component_metadata', new NameComponentMetadataService(
      $container->get('string_translation'),
    ));
    $container->set('link_generator', $link_generator);
    $container->set('module_handler', $this->moduleHandler);
    $container->set('path.validator', $path_validator);
    $container->set('renderer', $renderer);
    \Drupal::setContainer($container);

    $this->traitObject = new class() {
      use NameFieldSettingsTrait;
      use NameFormSettingsHelperTrait;
      use StringTranslationTrait;

      /**
       * Exposes the component_layout setting consumed by the form builder.
       */
      public function getSetting(string $name): string {
        return $name === 'component_layout' ? 'default' : '';
      }

      /**
       * Exposes the protected defaults for direct assertion.
       *
       * @return array<string, mixed>
       *   Default field settings array.
       */
      public function publicDefaults(): array {
        return self::getDefaultNameFieldSettings();
      }

      /**
       * Exposes the protected form builder for direct assertion.
       *
       * @return array<string, mixed>
       *   The generated form element structure.
       */
      public function publicForm(array $settings, array &$form, FormStateInterface $form_state): array {
        return $this->getDefaultNameFieldSettingsForm($settings, $form, $form_state);
      }

    };
  }

  /**
   * @covers ::getDefaultNameFieldSettings
   */
  public function testDefaultsTopLevelKeys(): void {
    $defaults = $this->traitObject->publicDefaults();
    foreach ([
      'components',
      'minimum_components',
      'allow_family_or_given',
      'max_length',
      'field_type',
      'autocomplete_source',
      'autocomplete_separator',
      'autocomplete_match',
      'autocomplete_match_overrides',
      'title_options',
      'generational_options',
      'sort_options',
      'component_layout',
    ] as $key) {
      $this->assertArrayHasKey($key, $defaults);
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettings
   */
  public function testDefaultMatchModeIsStartsWithWithEmptyOverrides(): void {
    $defaults = $this->traitObject->publicDefaults();
    $this->assertSame('starts_with', $defaults['autocomplete_match']);
    $this->assertCount(6, $defaults['autocomplete_match_overrides']);
    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertSame('', $defaults['autocomplete_match_overrides'][$component], "override for $component should be empty by default");
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettingsForm
   */
  public function testFormExposesMatchModeRadiosAndOverrideDetails(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $defaults = $this->traitObject->publicDefaults();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = $this->traitObject->publicForm($defaults, $form, $form_state);

    $this->assertArrayHasKey('autocomplete_match', $element);
    $this->assertSame('radios', $element['autocomplete_match']['#type']);
    $this->assertSame(['starts_with', 'contains'], array_keys($element['autocomplete_match']['#options']));
    $this->assertSame('starts_with', $element['autocomplete_match']['#default_value']);
    $this->assertSame('none', $element['autocomplete_match']['#table_group']);

    $this->assertArrayHasKey('autocomplete_match_overrides', $element);
    $this->assertSame('details', $element['autocomplete_match_overrides']['#type']);
    $this->assertTrue($element['autocomplete_match_overrides']['#tree']);
    $this->assertSame('none', $element['autocomplete_match_overrides']['#table_group']);
    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertArrayHasKey($component, $element['autocomplete_match_overrides']);
      $override = $element['autocomplete_match_overrides'][$component];
      $this->assertSame('select', $override['#type']);
      $this->assertSame(['', 'starts_with', 'contains'], array_keys($override['#options']));
      $this->assertSame('', $override['#default_value']);
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettings
   */
  public function testDefaultsSixComponentsEverywhere(): void {
    $defaults = $this->traitObject->publicDefaults();
    $components = ['title', 'given', 'middle', 'family', 'generational', 'credentials'];
    $groups = [
      'components',
      'minimum_components',
      'max_length',
      'field_type',
      'autocomplete_source',
      'autocomplete_separator',
      'sort_options',
    ];
    foreach ($groups as $group) {
      $this->assertCount(6, $defaults[$group]);
      foreach ($components as $component) {
        $this->assertArrayHasKey($component, $defaults[$group], "$group.$component missing");
      }
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettings
   */
  public function testDefaultAutocompleteSourcesOnlyOnTitleAndGenerational(): void {
    $defaults = $this->traitObject->publicDefaults();
    $this->assertSame(['title'], $defaults['autocomplete_source']['title']);
    $this->assertSame(['generation'], $defaults['autocomplete_source']['generational']);
    foreach (['given', 'middle', 'family', 'credentials'] as $component) {
      $this->assertSame([], $defaults['autocomplete_source'][$component]);
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettings
   */
  public function testDefaultFieldTypeMapping(): void {
    $defaults = $this->traitObject->publicDefaults();
    $this->assertSame('select', $defaults['field_type']['title']);
    $this->assertSame('select', $defaults['field_type']['generational']);
    foreach (['given', 'middle', 'family', 'credentials'] as $component) {
      $this->assertSame('text', $defaults['field_type'][$component]);
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettingsForm
   */
  public function testFormExposesFieldDataOnEveryComponent(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $defaults = $this->traitObject->publicDefaults();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = $this->traitObject->publicForm($defaults, $form, $form_state);

    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $this->assertArrayHasKey($component, $element['autocomplete_source']);
      $options = $element['autocomplete_source'][$component]['#options'];
      $this->assertArrayHasKey('data', $options, "'data' missing from $component row");
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettingsForm
   */
  public function testFormTitleAndGenerationalOptionsAreRowRestricted(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $defaults = $this->traitObject->publicDefaults();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = $this->traitObject->publicForm($defaults, $form, $form_state);

    $this->assertArrayHasKey('title', $element['autocomplete_source']['title']['#options']);
    $this->assertArrayHasKey('generational', $element['autocomplete_source']['generational']['#options']);
    foreach (['given', 'middle', 'family', 'credentials', 'generational'] as $component) {
      $this->assertArrayNotHasKey('title', $element['autocomplete_source'][$component]['#options'], "title leaked into $component");
    }
    foreach (['title', 'given', 'middle', 'family', 'credentials'] as $component) {
      $this->assertArrayNotHasKey('generational', $element['autocomplete_source'][$component]['#options'], "generational leaked into $component");
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettingsForm
   */
  public function testFormFieldTypeOptionsPerComponent(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $defaults = $this->traitObject->publicDefaults();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = $this->traitObject->publicForm($defaults, $form, $form_state);

    foreach (['title', 'given', 'middle', 'family', 'generational', 'credentials'] as $component) {
      $options = $element['field_type'][$component]['#options'];
      $this->assertArrayHasKey('text', $options);
      $this->assertArrayHasKey('autocomplete', $options);
    }
    $this->assertArrayHasKey('select', $element['field_type']['title']['#options']);
    $this->assertArrayHasKey('select', $element['field_type']['generational']['#options']);
    foreach (['given', 'middle', 'family', 'credentials'] as $component) {
      $this->assertArrayNotHasKey('select', $element['field_type'][$component]['#options']);
    }
  }

  /**
   * @covers ::getDefaultNameFieldSettingsForm
   */
  public function testFormTaxonomyDescriptionOnlyWhenModuleEnabled(): void {
    $this->moduleHandler->method('moduleExists')->willReturnMap([
      ['taxonomy', FALSE],
      ['help', FALSE],
    ]);

    $defaults = $this->traitObject->publicDefaults();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = $this->traitObject->publicForm($defaults, $form, $form_state);

    $this->assertStringNotContainsString('vocabulary:xxx', (string) $element['title_options']['#description']);
    $this->assertStringNotContainsString('vocabulary:xxx', (string) $element['generational_options']['#description']);
  }

  /**
   * @covers ::getDefaultNameFieldSettingsForm
   */
  public function testFormTaxonomyDescriptionWhenTaxonomyEnabled(): void {
    $this->moduleHandler->method('moduleExists')->willReturnMap([
      ['taxonomy', TRUE],
      ['help', FALSE],
    ]);

    $defaults = $this->traitObject->publicDefaults();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = $this->traitObject->publicForm($defaults, $form, $form_state);

    $this->assertStringContainsString('vocabulary:xxx', (string) $element['title_options']['#description']);
    $this->assertStringContainsString('vocabulary:xxx', (string) $element['generational_options']['#description']);
  }

  /**
   * @covers ::getDefaultNameFieldSettingsForm
   */
  public function testFormHelpDescriptionWhenHelpModuleEnabled(): void {
    $this->moduleHandler->method('moduleExists')->willReturnMap([
      ['taxonomy', FALSE],
      ['help', TRUE],
    ]);

    $defaults = $this->traitObject->publicDefaults();
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $element = $this->traitObject->publicForm($defaults, $form, $form_state);

    $description = (string) $element['title_options']['#description'];
    $this->assertStringContainsString('See the help topic for a comprehensive list of', $description);
    $this->assertStringContainsString('titles', $description);
  }

  /**
   * @covers ::validateMinimumComponents
   */
  public function testValidateMinimumComponentsErrorsWhenGivenAndFamilyMissing(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(function (array $key) {
      return match ($key) {
        ['settings', 'minimum_components'] => ['title' => 'title'],
        ['settings', 'components'] => ['title' => 'title'],
        default => NULL,
      };
    });
    $form_state->expects($this->atLeastOnce())->method('setError');

    $class = get_class($this->traitObject);
    $class::validateMinimumComponents(['#title' => 'Minimum components'], $form_state);
  }

  /**
   * @covers ::validateMinimumComponents
   */
  public function testValidateMinimumComponentsErrorsWhenMinNotInComponents(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(function (array $key) {
      return match ($key) {
        ['settings', 'minimum_components'] => ['given' => 'given', 'middle' => 'middle'],
        ['settings', 'components'] => ['given' => 'given'],
        default => NULL,
      };
    });
    $form_state->expects($this->atLeastOnce())->method('setError');

    $class = get_class($this->traitObject);
    $class::validateMinimumComponents(['#title' => 'Minimum components'], $form_state);
  }

  /**
   * @covers ::validateMinimumComponents
   */
  public function testValidateMinimumComponentsAcceptsValidConfiguration(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(function (array $key) {
      return match ($key) {
        ['settings', 'minimum_components'] => ['given' => 'given', 'family' => 'family'],
        ['settings', 'components'] => [
          'given' => 'given',
          'family' => 'family',
          'title' => 'title',
        ],
        default => NULL,
      };
    });
    $form_state->expects($this->never())->method('setError');

    $class = get_class($this->traitObject);
    $class::validateMinimumComponents(['#title' => 'Minimum components'], $form_state);
  }

  /**
   * @covers ::validateTitleOptions
   */
  public function testValidateTitleOptionsFlagsLongOptions(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(function (array $key) {
      return $key === ['settings', 'max_length', 'title'] ? 3 : NULL;
    });
    $form_state->expects($this->atLeastOnce())
      ->method('setError')
      ->with($this->anything(), $this->callback(fn ($message) => str_contains((string) ($message->getUntranslatedString() ?? $message), 'exceed')));

    $class = get_class($this->traitObject);
    $class::validateTitleOptions(
      ['#title' => 'Title options', '#value' => "Mr.\nLongerThanLimit"],
      $form_state,
    );
  }

  /**
   * @covers ::validateGenerationalOptions
   */
  public function testValidateGenerationalOptionsFlagsLongOptions(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(function (array $key) {
      return $key === ['settings', 'max_length', 'generational'] ? 2 : NULL;
    });
    $form_state->expects($this->atLeastOnce())
      ->method('setError')
      ->with($this->anything(), $this->callback(fn ($message) => str_contains((string) ($message->getUntranslatedString() ?? $message), 'exceed')));

    $class = get_class($this->traitObject);
    $class::validateGenerationalOptions(
      ['#title' => 'Generational options', '#value' => "Jr.\nLonger"],
      $form_state,
    );
  }

  /**
   * @covers ::validateTitleOptions
   */
  public function testValidateTitleOptionsAcceptsValid(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(function (array $key) {
      return $key === ['settings', 'max_length', 'title'] ? 31 : NULL;
    });
    $form_state->expects($this->never())->method('setError');

    $class = get_class($this->traitObject);
    $class::validateTitleOptions(
      ['#title' => 'Title options', '#value' => "Mr.\nMrs."],
      $form_state,
    );
  }

  /**
   * @covers ::validateTitleOptions
   */
  public function testValidateTitleOptionsFlagsMultipleDefaults(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')->willReturnCallback(function (array $key) {
      return $key === ['settings', 'max_length', 'title'] ? 31 : NULL;
    });
    $form_state->expects($this->atLeastOnce())
      ->method('setError')
      ->with($this->anything(), $this->callback(fn ($message) => str_contains((string) ($message->getUntranslatedString() ?? $message), 'blank value')));

    $class = get_class($this->traitObject);
    $class::validateTitleOptions(
      ['#title' => 'Title options', '#value' => "-- --\n-- Pick\nMr."],
      $form_state,
    );
  }

}

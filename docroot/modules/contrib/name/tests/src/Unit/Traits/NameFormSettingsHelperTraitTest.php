<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Traits;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\NameComponentMetadataService;
use Drupal\name\Traits\NameFormSettingsHelperTrait;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Tests for the less-exercised branches of NameFormSettingsHelperTrait.
 *
 * @group name
 * @coversDefaultClass \Drupal\name\Traits\NameFormSettingsHelperTrait
 */
class NameFormSettingsHelperTraitTest extends UnitTestCase {

  /**
   * The mocked module handler for taxonomy toggles.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The mocked entity type manager for vocabulary lookups.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The mocked taxonomy vocabulary storage.
   */
  protected EntityStorageInterface $vocabularyStorage;

  /**
   * Trait consumer used for instance methods.
   */
  protected object $consumer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->vocabularyStorage = $this->createMock(EntityStorageInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager
      ->method('getStorage')
      ->with('taxonomy_vocabulary')
      ->willReturn($this->vocabularyStorage);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('module_handler', $this->moduleHandler);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('name.component_metadata', new NameComponentMetadataService(
      $container->get('string_translation'),
    ));
    \Drupal::setContainer($container);

    $this->consumer = new class() {
      use NameFormSettingsHelperTrait;
      use StringTranslationTrait;

      /**
       * Exposes the protected static validator.
       */
      public static function publicValidateOptions($element, FormStateInterface $form_state, $values, $max_length): void {
        static::validateOptions($element, $form_state, $values, $max_length);
      }

      /**
       * Exposes the protected static allowed-values parser.
       *
       * @return array<int|string, string>
       *   Non-empty trimmed lines.
       */
      public static function publicExtractAllowedValues(string $string): array {
        return static::extractAllowedValues($string);
      }

    };
  }

  /**
   * Captures every setError() message the validator emits.
   *
   * @param \Drupal\Core\Form\FormStateInterface&\PHPUnit\Framework\MockObject\MockObject $form_state
   *   Mock form state to wire up.
   * @param array<int, string> $messages
   *   Array to accumulate captured messages into.
   */
  protected function captureErrorsOn(FormStateInterface $form_state, array &$messages): void {
    $form_state->method('setError')
      ->willReturnCallback(function ($element, $message) use (&$messages) {
        $messages[] = (string) $message;
      });
  }

  /**
   * @covers ::trustedCallbacks
   */
  public function testTrustedCallbacks(): void {
    $class = get_class($this->consumer);
    $this->assertSame(['fieldSettingsFormPreRender'], $class::trustedCallbacks());
  }

  /**
   * @covers ::extractAllowedValues
   */
  public function testExtractAllowedValues(): void {
    $class = get_class($this->consumer);
    $input = "  Mr.\nMrs.\n\n  Ms.  \n";
    $this->assertSame(['Mr.', 'Mrs.', 'Ms.'], array_values($class::publicExtractAllowedValues($input)));
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsAcceptsPlainValuesAndSetsMergedValue(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->never())->method('setError');
    $form_state->expects($this->once())
      ->method('setValueForElement')
      ->with(
        $this->anything(),
        $this->equalTo(['-- pick --', 'Mr.', 'Mrs.']),
      );

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['-- pick --', 'Mr.', 'Mrs.'],
      31,
    );
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsErrorsOnLongValues(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('setError')
      ->with(
        $this->anything(),
        $this->callback(fn ($message) => str_contains((string) $message, 'exceed')),
      );

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['Mr.', 'ExtremelyLongOptionValue'],
      3,
    );
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsErrorsWhenNoValidOptions(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('setError')
      ->with(
        $this->anything(),
        $this->callback(fn ($message) => str_contains((string) $message, 'required')),
      );

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['', '  '],
      31,
    );
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsErrorsOnMultipleDefaults(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->atLeastOnce())
      ->method('setError')
      ->with(
        $this->anything(),
        $this->callback(fn ($message) => str_contains((string) $message, 'blank value')),
      );

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['-- a --', '-- b --', 'Mr.'],
      31,
    );
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsErrorsWhenTaxonomyMissing(): void {
    $this->moduleHandler->method('moduleExists')
      ->willReturnMap([['taxonomy', FALSE]]);

    $messages = [];
    $this->captureErrorsOn($form_state = $this->createMock(FormStateInterface::class), $messages);

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['[vocabulary:titles]'],
      31,
    );

    $this->assertStringContainsString('taxonomy module must be enabled', implode("\n", $messages));
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsErrorsWhenTagNotOnOwnLine(): void {
    $this->moduleHandler->method('moduleExists')
      ->willReturnMap([['taxonomy', TRUE]]);

    $messages = [];
    $this->captureErrorsOn($form_state = $this->createMock(FormStateInterface::class), $messages);

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['[vocabulary:titles] extra'],
      31,
    );

    $this->assertStringContainsString('on a line by itself', implode("\n", $messages));
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsErrorsWhenVocabularyMissing(): void {
    $this->moduleHandler->method('moduleExists')
      ->willReturnMap([['taxonomy', TRUE]]);
    $this->vocabularyStorage->method('load')->with('ghost')->willReturn(NULL);

    $messages = [];
    $this->captureErrorsOn($form_state = $this->createMock(FormStateInterface::class), $messages);

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['[vocabulary:ghost]'],
      31,
    );

    $this->assertStringContainsString('could not be found', implode("\n", $messages));
  }

  /**
   * @covers ::validateOptions
   */
  public function testValidateOptionsAcceptsValidVocabularyTag(): void {
    $this->moduleHandler->method('moduleExists')
      ->willReturnMap([['taxonomy', TRUE]]);
    $this->vocabularyStorage->method('load')->with('titles')
      ->willReturn($this->createMock(VocabularyInterface::class));

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->never())->method('setError');
    $form_state->expects($this->once())
      ->method('setValueForElement')
      ->with($this->anything(), $this->equalTo(['[vocabulary:titles]']));

    $class = get_class($this->consumer);
    $class::publicValidateOptions(
      ['#title' => 'Title options'],
      $form_state,
      ['[vocabulary:titles]'],
      31,
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::routeTableGroupChild
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderSkipsElementsMarkedTableGroupNone(): void {
    $form = [
      'hidden_helper' => [
        '#type'        => 'textfield',
        '#title'       => 'Hidden',
        '#table_group' => 'none',
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertArrayNotHasKey('hidden_helper', $result['name_settings']['table']);
    $this->assertArrayNotHasKey('hidden_helper', $result['top']);
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::routeTableGroupChild
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderMovesTableGroupAboveIntoTop(): void {
    $form = [
      'language_layout' => [
        '#type'        => 'radios',
        '#title'       => 'Language layout',
        '#table_group' => 'above',
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertArrayHasKey('language_layout', $result['top']);
    $this->assertSame('Language layout', $result['top']['language_layout']['#title']);
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::routeTableGroupChild
   * @covers ::buildIndentRow
   * @covers ::buildColspanContainer
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderNestsTableGroupValueIntoTargetKey(): void {
    $form = [
      'components_extra' => [
        '#indent_row' => TRUE,
      ],
      'my_helper' => [
        '#type'        => 'checkbox',
        '#title'       => 'Helper',
        '#table_group' => 'components_extra',
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertArrayHasKey(
      'my_helper',
      $result['name_settings']['table']['components_extra']['elements'],
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::routeTableGroupChild
   * @covers ::buildIndentRow
   * @covers ::buildColspanContainer
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderMergesDeferredGroupedElementsIntoIndentRow(): void {
    $form = [
      'components_helper' => [
        '#type'        => 'checkbox',
        '#title'       => 'Components helper',
        '#table_group' => 'components_extra',
      ],
      'components_extra' => [
        '#indent_row' => TRUE,
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertArrayHasKey('components_extra', $result['name_settings']['table']);
    $this->assertArrayHasKey('elements', $result['name_settings']['table']['components_extra']);
    $this->assertArrayHasKey(
      'components_helper',
      $result['name_settings']['table']['components_extra']['elements'],
    );
    $this->assertSame(
      'container',
      $result['name_settings']['table']['components_extra']['elements']['#type'],
    );
    $this->assertArrayNotHasKey('components_helper', $result);
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::routeTableGroupChild
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::buildColspanContainer
   * @covers ::appendFootnotes
   */
  public function testPreRenderCreatesElementsContainerOnStandardRowForDeferredGroup(): void {
    $form = [
      'labels_helper' => [
        '#type'        => 'textfield',
        '#title'       => 'Labels helper',
        '#table_group' => 'labels',
      ],
      'labels' => [
        '#title' => 'Labels',
        'given'  => [
          '#type'          => 'textfield',
          '#title'         => 'Given',
          '#default_value' => 'Given',
        ],
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertArrayHasKey('labels', $result['name_settings']['table']);
    $this->assertArrayHasKey('elements', $result['name_settings']['table']['labels']);
    $this->assertSame(
      'container',
      $result['name_settings']['table']['labels']['elements']['#type'],
    );
    $this->assertSame(
      6,
      $result['name_settings']['table']['labels']['elements']['#wrapper_attributes']['colspan'],
    );
    $this->assertArrayHasKey(
      'labels_helper',
      $result['name_settings']['table']['labels']['elements'],
    );
    $this->assertArrayNotHasKey('labels_helper', $result);
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildIndentRow
   * @covers ::buildColspanContainer
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderIndentRowAccountsForExcludedComponents(): void {
    $form = [
      '#excluded_components' => [
        'middle'       => 'Middle name(s)',
        'generational' => 'Generational',
      ],
      'components_extra' => [
        '#indent_row' => TRUE,
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $row = $result['name_settings']['table']['components_extra'];
    $this->assertSame('&nbsp;', $row['field']['#markup']);
    $this->assertSame(
      4,
      $row['elements']['#wrapper_attributes']['colspan'],
      '6 components minus 2 excluded should produce a colspan of 4.',
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderAddsFootnoteForElementsWithDescription(): void {
    $form = [
      'labels' => [
        '#type'        => 'item',
        '#title'       => 'Labels',
        '#description' => 'Choose a label.',
        'given'        => [
          '#type'          => 'textfield',
          '#title'         => 'Given',
          '#default_value' => 'Given',
        ],
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $row = $result['name_settings']['table']['labels'];
    $this->assertSame('Labels', $row['field']['title']['#plain_text']);
    $this->assertStringContainsString('<sup>1</sup>', (string) $row['field']['footnote']['#markup']);
    $this->assertArrayHasKey('footnotes', $result['name_settings']);
    $this->assertSame('Choose a label.', (string) $result['name_settings']['footnotes']['help_items']['#items'][0]);
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderSilencesCheckboxTitlesAndFillsMissingComponents(): void {
    $form = [
      'components' => [
        '#title' => 'Components',
        'given'  => [
          '#type'  => 'checkbox',
          '#title' => 'Given',
        ],
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertSame(
      'invisible',
      $result['name_settings']['table']['components']['given']['#title_display'],
    );
    $this->assertSame(
      'Given',
      $result['name_settings']['table']['components']['given']['#attributes']['title'],
    );
    $this->assertSame(
      '&nbsp;',
      $result['name_settings']['table']['components']['title']['#markup'],
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderMovesExcludedComponentsIntoHiddenSection(): void {
    $form = [
      '#excluded_components' => [
        'middle' => 'Middle name(s)',
      ],
      'labels' => [
        '#title' => 'Labels',
        'given'  => [
          '#type'          => 'textfield',
          '#title'         => 'Given',
          '#default_value' => 'Given',
        ],
        'middle' => [
          '#type'          => 'textfield',
          '#title'         => 'Middle',
          '#default_value' => 'Middle',
        ],
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertFalse($result['hidden']['labels']['middle']['#access']);
    $this->assertArrayNotHasKey(
      'middle',
      $result['name_settings']['table']['labels'],
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::routeTableGroupChild
   * @covers ::buildColspanContainer
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderCreatesGroupedRowWhenTargetDoesNotExist(): void {
    $form = [
      'orphan_helper' => [
        '#type' => 'textfield',
        '#title' => 'Orphan helper',
        '#table_group' => 'missing_target',
      ],
    ];

    $result = $this->consumer->fieldSettingsFormPreRender($form);

    $this->assertArrayHasKey('missing_target', $result['name_settings']['table']);
    $this->assertArrayHasKey('elements', $result['name_settings']['table']['missing_target']);
    $this->assertArrayHasKey(
      'orphan_helper',
      $result['name_settings']['table']['missing_target']['elements'],
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderSetsFormSortedFalse(): void {
    $result = $this->consumer->fieldSettingsFormPreRender([
      'labels' => [
        '#title' => 'Labels',
      ],
    ]);

    $this->assertFalse($result['#sorted']);
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderTableHeaderExcludesFilteredComponents(): void {
    $result = $this->consumer->fieldSettingsFormPreRender([
      '#excluded_components' => [
        'middle'       => 'Middle name(s)',
        'generational' => 'Generational',
      ],
      'labels' => [
        '#title' => 'Labels',
      ],
    ]);

    $headers = array_map(
      static fn (array $header): string => (string) $header['data'],
      $result['name_settings']['table']['#header'],
    );
    $this->assertSame(
      ['Field', 'Title', 'Given', 'Family', 'Credentials'],
      $headers,
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderComponentCellReceivesAttributeTitle(): void {
    $result = $this->consumer->fieldSettingsFormPreRender([
      'labels' => [
        '#title' => 'Labels',
        'given'  => [
          '#type'  => 'textfield',
          '#title' => 'Given label',
        ],
      ],
    ]);

    $this->assertSame(
      'Given label',
      $result['name_settings']['table']['labels']['given']['#attributes']['title'],
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderComponentRowHasNoStates(): void {
    $result = $this->consumer->fieldSettingsFormPreRender([
      'components' => [
        '#title' => 'Components',
        'given'  => [
          '#type'  => 'checkbox',
          '#title' => 'Given',
        ],
      ],
    ]);

    $this->assertArrayNotHasKey(
      '#states',
      $result['name_settings']['table']['components']['given'],
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildLabelCell
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderEmptyCellCreatedForMissingComponent(): void {
    $result = $this->consumer->fieldSettingsFormPreRender([
      'labels' => [
        '#title' => 'Labels',
        'given'  => [
          '#type'  => 'textfield',
          '#title' => 'Given label',
        ],
      ],
    ]);

    $this->assertSame(
      '&nbsp;',
      $result['name_settings']['table']['labels']['title']['#markup'],
    );
  }

  /**
   * @covers ::fieldSettingsFormPreRender
   * @covers ::resolveComponents
   * @covers ::initFormScaffold
   * @covers ::buildStandardRow
   * @covers ::buildComponentColumns
   * @covers ::buildEmptyCell
   * @covers ::buildComponentVisibilityStates
   * @covers ::flushOrphanedGroupedElements
   * @covers ::appendFootnotes
   */
  public function testPreRenderStandardRowWithNoTitleHasNoFieldCell(): void {
    $result = $this->consumer->fieldSettingsFormPreRender([
      'labels' => [
        'given' => [
          '#type'  => 'textfield',
          '#title' => 'Given label',
        ],
      ],
    ]);

    $this->assertArrayNotHasKey('field', $result['name_settings']['table']['labels']);
  }

}

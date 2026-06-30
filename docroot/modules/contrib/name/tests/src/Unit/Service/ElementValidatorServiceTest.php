<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Service;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Service\ElementValidatorInterface;
use Drupal\name\Service\ElementValidatorService;
use Drupal\name\Service\NameComponentMetadataInterface;
use Drupal\name\Service\NameComponentMetadataService;
use PHPUnit\Framework\Assert;

/**
 * @coversDefaultClass \Drupal\name\Service\ElementValidatorService
 *
 * @group name
 */
class ElementValidatorServiceTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testConstruct(): void {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $stringTranslation = $this->getStringTranslationStub();
    $componentMetadata = $this->createMock(NameComponentMetadataInterface::class);

    $service = new ElementValidatorService(
      $moduleHandler,
      $stringTranslation,
      $componentMetadata,
    );

    $this->assertInstanceOf(ElementValidatorInterface::class, $service);
    $reflection = new \ReflectionClass($service);

    $moduleHandlerProp = $reflection->getProperty('moduleHandler');
    $moduleHandlerProp->setAccessible(TRUE);
    $this->assertSame($moduleHandler, $moduleHandlerProp->getValue($service));

    $metadataProp = $reflection->getProperty('componentMetadata');
    $metadataProp->setAccessible(TRUE);
    $this->assertSame($componentMetadata, $metadataProp->getValue($service));

    $translationProp = $reflection->getProperty('stringTranslation');
    $translationProp->setAccessible(TRUE);
    $this->assertSame($stringTranslation, $translationProp->getValue($service));
  }

  /**
   * @covers ::validate
   */
  public function testSkipsWhenNeedsValidationEmpty(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->never())->method('setError');

    $service = new ElementValidatorService(
      $this->createMock(ModuleHandlerInterface::class),
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => FALSE,
      '#minimum_components' => ['given' => 'given'],
      '#components' => [
        'given' => ['title' => 'Given'],
      ],
      '#value' => [],
    ];
    $this->assertSame($element, $service->validate($element, $formState));
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::setPartialInputErrors
   * @covers ::validate
   */
  public function testMinimumComponentsNotMetSetsErrors(): void {
    $familyElement = ['#type' => 'textfield', '#name' => 'family'];

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->exactly(2))
      ->method('setError')
      ->willReturnCallback(static function (array &$el, $message = '') use ($familyElement): void {
        Assert::assertSame($familyElement, $el);
      });

    $service = new ElementValidatorService(
      $this->createMock(ModuleHandlerInterface::class),
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#minimum_components' => [
        'given' => 'given',
        'family' => 'family',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given name'],
        'family' => ['type' => 'textfield', 'title' => 'Family name'],
      ],
      '#value' => [
        'given' => 'Jane',
        'family' => '',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
      'given' => ['#type' => 'textfield', '#name' => 'given'],
      'family' => $familyElement,
    ];

    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::validate
   */
  public function testAllowFamilyOrGivenCountsBothWhenOnePresent(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->never())->method('setError');

    $service = new ElementValidatorService(
      $this->createMock(ModuleHandlerInterface::class),
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#allow_family_or_given' => TRUE,
      '#minimum_components' => [
        'given' => 'given',
        'family' => 'family',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given name'],
        'family' => ['type' => 'textfield', 'title' => 'Family name'],
      ],
      '#value' => [
        'given' => 'Jane',
        'family' => '',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
    ];
    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::validate
   */
  public function testExcludedComponentsAreIgnoredInLabels(): void {
    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->never())->method('setError');

    $service = new ElementValidatorService(
      $this->createMock(ModuleHandlerInterface::class),
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#minimum_components' => [
        'given' => 'given',
        'middle' => 'middle',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given name'],
        'middle' => [
          'type' => 'textfield',
          'title' => 'Middle name(s)',
          'exclude' => TRUE,
        ],
      ],
      '#value' => [
        'given' => 'Jane',
        'middle' => '',
        'family' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
      'given' => ['#type' => 'textfield', '#name' => 'given'],
    ];

    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::setPartialInputErrors
   * @covers ::validate
   */
  public function testAllowFamilyOrGivenBothEmptyDoesNotSatisfyMinimum(): void {
    $givenElement = ['#type' => 'textfield', '#name' => 'given'];
    $familyElement = ['#type' => 'textfield', '#name' => 'family'];

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->exactly(3))
      ->method('setError')
      ->willReturnCallback(
        static function (array &$el, $message = '') use (
          $givenElement,
          $familyElement,
        ): void {
          static $calls = 0;
          $calls++;

          if ($calls === 3) {
            Assert::assertSame($familyElement, $el);
            return;
          }

          Assert::assertSame($givenElement, $el);
        }
      );

    $service = new ElementValidatorService(
      $this->createMock(ModuleHandlerInterface::class),
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#allow_family_or_given' => TRUE,
      '#minimum_components' => [
        'given' => 'given',
        'family' => 'family',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given name'],
        'middle' => ['type' => 'textfield', 'title' => 'Middle name(s)'],
        'family' => ['type' => 'textfield', 'title' => 'Family name'],
      ],
      '#value' => [
        'given' => '',
        'middle' => 'Middle',
        'family' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
      'given' => $givenElement,
      'family' => $familyElement,
    ];

    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::setPartialInputErrors
   * @covers ::validate
   */
  public function testAllowFamilyOrGivenIgnoredWhenLabelsLackBothKeys(): void {
    $familyElement = ['#type' => 'textfield', '#name' => 'family'];

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->exactly(2))
      ->method('setError')
      ->willReturnCallback(
        static function (array &$el, $message = '') use (
          $familyElement,
        ): void {
          Assert::assertSame($familyElement, $el);
        }
      );

    $service = new ElementValidatorService(
      $this->createMock(ModuleHandlerInterface::class),
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#allow_family_or_given' => TRUE,
      '#minimum_components' => [
        'family' => 'family',
      ],
      '#components' => [
        'family' => ['type' => 'textfield', 'title' => 'Family name'],
      ],
      '#value' => [
        'given' => 'Jane',
        'family' => '',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
      'family' => $familyElement,
    ];

    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::setPartialInputErrors
   * @covers ::validate
   */
  public function testSelectNoneTreatedAsEmpty(): void {
    $givenElement = ['#type' => 'select', '#name' => 'given'];

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->exactly(2))
      ->method('setError')
      ->willReturnCallback(static function (array &$el, $message = '') use ($givenElement): void {
        Assert::assertSame($givenElement, $el);
      });

    $service = new ElementValidatorService(
      $this->createMock(ModuleHandlerInterface::class),
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#minimum_components' => [
        'given' => 'given',
        'family' => 'family',
      ],
      '#components' => [
        'given' => ['type' => 'select', 'title' => 'Given name'],
        'family' => ['type' => 'textfield', 'title' => 'Family name'],
      ],
      '#value' => [
        'given' => '_none',
        'family' => 'Doe',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
      'given' => $givenElement,
      'family' => ['#type' => 'textfield', '#name' => 'family'],
    ];

    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::setPartialInputErrors
   * @covers ::validate
   */
  public function testInlineErrorsUseComponentLocalMessagesForPartialInput(): void {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('moduleExists')->with('inline_form_errors')->willReturn(TRUE);

    $middleElement = ['#type' => 'textfield', '#name' => 'middle'];
    $familyElement = ['#type' => 'textfield', '#name' => 'family'];

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->exactly(2))
      ->method('setError')
      ->willReturnCallback(static function (array &$el, $message = '') use ($middleElement, $familyElement): void {
        static $calls = 0;
        $calls++;
        $message = (string) $message;

        if ($calls === 1) {
          Assert::assertSame($middleElement, $el);
          Assert::assertStringContainsString('Middle name(s)', $message);
          Assert::assertStringNotContainsString('Family', $message);
          return;
        }

        Assert::assertSame($familyElement, $el);
        Assert::assertStringContainsString('Family', $message);
        Assert::assertStringNotContainsString('Middle name(s)', $message);
      });

    $service = new ElementValidatorService(
      $moduleHandler,
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name Test',
      '#minimum_components' => [
        'given' => 'given',
        'middle' => 'middle',
        'family' => 'family',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given'],
        'middle' => ['type' => 'textfield', 'title' => 'Middle name(s)'],
        'family' => ['type' => 'textfield', 'title' => 'Family'],
      ],
      '#value' => [
        'given' => 'steve',
        'middle' => '',
        'family' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => TRUE,
      'given' => ['#type' => 'textfield', '#name' => 'given'],
      'middle' => $middleElement,
      'family' => $familyElement,
    ];

    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::setRequiredErrors
   * @covers ::validate
   */
  public function testRequiredEmptyWithInlineFormErrors(): void {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('moduleExists')->with('inline_form_errors')->willReturn(TRUE);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->exactly(2))->method('setError');

    $service = new ElementValidatorService(
      $moduleHandler,
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#minimum_components' => [
        'given' => 'given',
        'family' => 'family',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given name'],
        'family' => ['type' => 'textfield', 'title' => 'Family name'],
      ],
      '#value' => [
        'given' => '',
        'family' => '',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => TRUE,
      'given' => ['#type' => 'textfield'],
      'family' => ['#type' => 'textfield'],
    ];

    $service->validate($element, $formState);
  }

  /**
   * @covers ::applyFamilyOrGivenLogic
   * @covers ::resolveFilledComponents
   * @covers ::resolveLabels
   * @covers ::resolveMissingLabels
   * @covers ::setRequiredErrors
   * @covers ::validate
   */
  public function testRequiredEmptyWithoutInlineFormErrors(): void {
    $moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $moduleHandler->method('moduleExists')->with('inline_form_errors')->willReturn(FALSE);

    $formState = $this->createMock(FormStateInterface::class);
    $formState->expects($this->once())
      ->method('setError')
      ->with(
        $this->callback(static function (array $el) {
          return ($el['#title'] ?? NULL) === 'Name';
        }),
        $this->anything(),
      );

    $service = new ElementValidatorService(
      $moduleHandler,
      $this->getStringTranslationStub(),
      new NameComponentMetadataService($this->getStringTranslationStub()),
    );

    $element = [
      '#needs_validation' => TRUE,
      '#title' => 'Name',
      '#minimum_components' => [
        'given' => 'given',
      ],
      '#components' => [
        'given' => ['type' => 'textfield', 'title' => 'Given name'],
      ],
      '#value' => [
        'given' => '',
        'family' => '',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => TRUE,
      'given' => ['#type' => 'textfield'],
    ];

    $service->validate($element, $formState);
  }

}

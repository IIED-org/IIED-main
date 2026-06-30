<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Service;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Service\ElementValidatorService;

/**
 * @coversDefaultClass \Drupal\name\Service\ElementValidatorService
 *
 * @group name
 */
class ElementValidatorServiceTest extends KernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['field', 'name']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * @covers ::validate
   */
  public function testServiceRegistered(): void {
    $service = $this->container->get('name.element_validator');
    $this->assertInstanceOf(ElementValidatorService::class, $service);
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoErrorsWhenMinimumsSatisfied(): void {
    /** @var \Drupal\name\Service\ElementValidatorService $service */
    $service = $this->container->get('name.element_validator');
    $formState = new FormState();

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
        'given' => 'Pat',
        'family' => 'Smith',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
    ];

    $service->validate($element, $formState);
    $this->assertCount(0, $formState->getErrors());
  }

  /**
   * @covers ::validate
   */
  public function testValidateSetsErrorsWhenMinimumMissing(): void {
    /** @var \Drupal\name\Service\ElementValidatorService $service */
    $service = $this->container->get('name.element_validator');
    $formState = new FormState();

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
        'given' => 'Pat',
        'family' => '',
        'middle' => '',
        'title' => '',
        'generational' => '',
        'credentials' => '',
      ],
      '#required' => FALSE,
      'given' => [
        '#type' => 'textfield',
        '#parents' => ['name', 'given'],
      ],
      'family' => [
        '#type' => 'textfield',
        '#parents' => ['name', 'family'],
      ],
    ];

    $service->validate($element, $formState);
    $this->assertNotEmpty($formState->getErrors());
  }

}

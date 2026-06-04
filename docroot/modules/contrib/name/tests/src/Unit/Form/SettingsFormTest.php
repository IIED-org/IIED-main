<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Unit\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\name\Form\SettingsForm;

/**
 * @coversDefaultClass \Drupal\name\Form\SettingsForm
 *
 * @group name
 */
final class SettingsFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('messenger', $this->createMock(MessengerInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getFormId
   */
  public function testGetFormIdReturnsExpectedId(): void {
    $form = $this->createForm([
      'sep1' => ',',
      'sep2' => ' ',
      'sep3' => '',
    ]);

    $this->assertSame('name_admin_settings', $form->getFormId());
  }

  /**
   * @covers ::getEditableConfigNames
   */
  public function testGetEditableConfigNamesReturnsNameSettings(): void {
    $form = $this->createForm([
      'sep1' => ',',
      'sep2' => ' ',
      'sep3' => '',
    ]);

    $method = new \ReflectionMethod(SettingsForm::class, 'getEditableConfigNames');
    $method->setAccessible(TRUE);

    $this->assertSame(['name.settings'], $method->invoke($form));
  }

  /**
   * @covers ::buildForm
   */
  public function testBuildFormUsesStoredDefaultValues(): void {
    $form = $this->createForm([
      'sep1' => ',',
      'sep2' => '; ',
      'sep3' => ' and ',
    ]);

    $built_form = $form->buildForm([], new FormState());

    $this->assertTrue($built_form['name_settings']['#tree']);
    $this->assertSame(',', $built_form['name_settings']['sep1']['#default_value']);
    $this->assertSame('; ', $built_form['name_settings']['sep2']['#default_value']);
    $this->assertSame(' and ', $built_form['name_settings']['sep3']['#default_value']);
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormStoresSubmittedSeparators(): void {
    $config_factory = $this->getConfigFactoryStub([]);
    $captured_values = [];
    $editable_config = $this->getMockBuilder(Config::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['set', 'save'])
      ->getMock();
    $editable_config->expects($this->exactly(3))
      ->method('set')
      ->willReturnCallback(function (string $key, mixed $value) use (&$captured_values, $editable_config): Config {
        $captured_values[$key] = $value;
        return $editable_config;
      });
    $editable_config->expects($this->once())
      ->method('save');

    $form = $this->getMockBuilder(SettingsForm::class)
      ->setConstructorArgs([
        $config_factory,
        $this->createMock(TypedConfigManagerInterface::class),
      ])
      ->onlyMethods(['config'])
      ->getMock();
    $form->setStringTranslation($this->getStringTranslationStub());
    $form->method('config')
      ->with('name.settings')
      ->willReturn($editable_config);

    $form_state = new FormState();
    $form_state->setValues([
      'name_settings' => [
        'sep1' => 'one',
        'sep2' => 'two',
        'sep3' => 'three',
      ],
    ]);

    $form_array = [];
    $form->submitForm($form_array, $form_state);

    $this->assertSame([
      'sep1' => 'one',
      'sep2' => 'two',
      'sep3' => 'three',
    ], $captured_values);
  }

  /**
   * Creates a settings form with stubbed config.
   */
  private function createForm(array $settings): SettingsForm {
    $config_factory = $this->getConfigFactoryStub([
      'name.settings' => $settings,
    ]);
    \Drupal::getContainer()->set('config.factory', $config_factory);

    $form = new SettingsForm(
      $config_factory,
      $this->createMock(TypedConfigManagerInterface::class),
    );
    $form->setStringTranslation($this->getStringTranslationStub());

    return $form;
  }

}

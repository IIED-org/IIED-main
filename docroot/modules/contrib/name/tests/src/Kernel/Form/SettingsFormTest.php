<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Form\SettingsForm;

/**
 * @coversDefaultClass \Drupal\name\Form\SettingsForm
 *
 * @group name
 */
final class SettingsFormTest extends KernelTestBase {

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
   * @covers ::buildForm
   */
  public function testBuildFormUsesConfiguredDefaults(): void {
    $this->config('name.settings')
      ->set('sep1', ' / ')
      ->set('sep2', ' + ')
      ->set('sep3', ' & ')
      ->save();

    $form = $this->createSettingsForm();
    $built_form = $form->buildForm([], new FormState());

    $this->assertSame(' / ', $built_form['name_settings']['sep1']['#default_value']);
    $this->assertSame(' + ', $built_form['name_settings']['sep2']['#default_value']);
    $this->assertSame(' & ', $built_form['name_settings']['sep3']['#default_value']);
  }

  /**
   * @covers ::submitForm
   */
  public function testSubmitFormPersistsSubmittedValues(): void {
    $form = $this->createSettingsForm();
    $form_state = new FormState();
    $form_state->setValues([
      'name_settings' => [
        'sep1' => 'first',
        'sep2' => 'second',
        'sep3' => 'third',
      ],
    ]);

    $form_array = [];
    $form->submitForm($form_array, $form_state);

    $this->assertSame('first', $this->config('name.settings')->get('sep1'));
    $this->assertSame('second', $this->config('name.settings')->get('sep2'));
    $this->assertSame('third', $this->config('name.settings')->get('sep3'));
  }

  /**
   * Creates the settings form from the service container.
   */
  private function createSettingsForm(): SettingsForm {
    /** @var \Drupal\name\Form\SettingsForm $form */
    $form = SettingsForm::create($this->container);
    return $form;
  }

}

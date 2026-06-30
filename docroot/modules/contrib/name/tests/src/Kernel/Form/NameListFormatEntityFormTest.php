<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Entity\NameListFormat;
use Drupal\name\Form\NameListFormatForm;

/**
 * @coversDefaultClass \Drupal\name\Form\NameListFormatForm
 *
 * @group name
 */
class NameListFormatEntityFormTest extends KernelTestBase {

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
   * @covers ::form
   * @covers ::actions
   */
  public function testBuildFormIncludesExpectedElementsAndFormatterOptions(): void {
    /** @var \Drupal\name\Service\NameFormatterInterface $formatter */
    $formatter = $this->container->get('name.formatter');

    $entity = NameListFormat::create([]);
    $form_object = $this->getNameListFormatForm($entity);
    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);

    $this->assertSame('textfield', $form['label']['#type']);
    $this->assertSame('machine_name', $form['id']['#type']);
    $this->assertSame('textfield', $form['delimiter']['#type']);
    $this->assertSame('radios', $form['and']['#type']);
    $this->assertSame('radios', $form['delimiter_precedes_last']['#type']);
    $this->assertSame('select', $form['el_al_min']['#type']);
    $this->assertSame('select', $form['el_al_first']['#type']);

    $this->assertEquals(
      $formatter->getLastDelimiterTypes(),
      $form['and']['#options']
    );
    $this->assertEquals(
      $formatter->getLastDelimiterBehaviors(),
      $form['delimiter_precedes_last']['#options']
    );

    $this->assertSame(
      'Save list format',
      (string) $form['actions']['submit']['#value']
    );
  }

  /**
   * @covers ::form
   */
  public function testBuildFormEditDisablesMachineName(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_list_format');
    /** @var \Drupal\name\Entity\NameListFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_list_format_edit',
      'label' => 'Kernel list edit',
    ]);
    $entity->save();

    $form_object = $this->getNameListFormatForm($entity);
    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);

    $this->assertTrue($form['id']['#disabled']);
  }

  /**
   * @covers ::exists
   */
  public function testExistsCallbackReflectsStorage(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_list_format');
    /** @var \Drupal\name\Entity\NameListFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_list_format_exists',
      'label' => 'Exists list',
    ]);
    $entity->save();

    $form_object = $this->getNameListFormatForm($entity);
    $form_state = new FormState();

    $this->assertNotNull(
      $form_object->exists('kernel_list_format_exists', [], $form_state)
    );
    $this->assertNull(
      $form_object->exists('no_such_list_format', [], $form_state)
    );
  }

  /**
   * @covers ::save
   */
  public function testSubmitSavePersistsAndSetsRedirect(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_list_format');
    /** @var \Drupal\name\Entity\NameListFormat $entity */
    $entity = $storage->create([]);
    $form_object = $this->getNameListFormatForm($entity);

    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);
    $form_state->setValues([
      'label' => 'Kernel list new',
      'id' => 'kernel_list_format_new',
      'delimiter' => '; ',
      'and' => 'text',
      'delimiter_precedes_last' => 'never',
      'el_al_min' => 0,
      'el_al_first' => 1,
    ]);

    $form_object->validateForm($form, $form_state);
    $this->assertCount(0, $form_state->getErrors());

    $form_object->submitForm($form, $form_state);
    $form_object->save($form, $form_state);

    $reloaded = NameListFormat::load('kernel_list_format_new');
    $this->assertNotNull($reloaded);
    $this->assertSame('Kernel list new', $reloaded->label());
    $this->assertSame('; ', $reloaded->delimiter);

    $redirect = $form_state->getRedirect();
    $this->assertNotNull($redirect);
    $this->assertSame('name.name_list_format_list', $redirect->getRouteName());
  }

  /**
   * @covers ::save
   */
  public function testSaveExistingEntityAddsUpdatedStatusMessage(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_list_format');
    /** @var \Drupal\name\Entity\NameListFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_list_format_update_msg',
      'label' => 'Before update',
    ]);
    $entity->save();

    $form_object = $this->getNameListFormatForm($entity);
    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);
    $form_state->setValues([
      'label' => 'After update',
      'id' => 'kernel_list_format_update_msg',
      'delimiter' => '; ',
      'and' => 'text',
      'delimiter_precedes_last' => 'never',
      'el_al_min' => 0,
      'el_al_first' => 1,
    ]);

    $form_object->validateForm($form, $form_state);
    $this->assertCount(0, $form_state->getErrors());

    $messenger = $this->container->get('messenger');
    $messenger->deleteAll();

    $form_object->submitForm($form, $form_state);
    $form_object->save($form, $form_state);

    $status_messages = $messenger->messagesByType(MessengerInterface::TYPE_STATUS);
    $this->assertCount(1, $status_messages);
    $this->assertSame(
      'Name list format <em class="placeholder">After update</em> has been updated.',
      (string) $status_messages[0]
    );
  }

  /**
   * Verifies the delete action sets redirect to the entity delete form route.
   *
   * @covers \Drupal\name\Form\NameListFormatForm::delete
   */
  public function testDeleteSetsRedirectToDeleteFormRoute(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_list_format');
    /** @var \Drupal\name\Entity\NameListFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_list_format_delete_btn',
      'label' => 'Delete redirect list',
    ]);
    $entity->save();

    $form_object = $this->getNameListFormatForm($entity);
    $form = [];
    $form_state = new FormState();
    $form_object->delete($form, $form_state);

    $redirect = $form_state->getRedirect();
    $this->assertNotNull($redirect);
    $this->assertSame(
      'entity.name_list_format.delete_form',
      $redirect->getRouteName()
    );
    $this->assertSame(
      ['name_list_format' => 'kernel_list_format_delete_btn'],
      $redirect->getRouteParameters()
    );
  }

  /**
   * Gets the name list format add/edit form with dependencies.
   */
  private function getNameListFormatForm(NameListFormat $entity): NameListFormatForm {
    /** @var \Drupal\name\Form\NameListFormatForm $form_object */
    $form_object = $this->container->get('entity_type.manager')
      ->getFormObject(
        'name_list_format',
        $entity->isNew() ? 'add' : 'edit'
      );
    $form_object->setEntity($entity);
    $form_object->setModuleHandler($this->container->get('module_handler'));
    return $form_object;
  }

}

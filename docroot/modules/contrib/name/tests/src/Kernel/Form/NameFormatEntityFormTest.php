<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Entity\NameFormat;
use Drupal\name\Form\NameFormatForm;

/**
 * @coversDefaultClass \Drupal\name\Form\NameFormatForm
 *
 * @group name
 */
class NameFormatEntityFormTest extends KernelTestBase {

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
  public function testBuildFormAddIncludesExpectedElements(): void {
    $entity = NameFormat::create([]);
    $form_object = $this->getNameFormatForm($entity);
    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);

    $this->assertSame('textfield', $form['label']['#type']);
    $this->assertSame('machine_name', $form['id']['#type']);
    $this->assertSame('textfield', $form['pattern']['#type']);
    $this->assertIsArray($form['help']);
    $this->assertSame(
      'Save format',
      (string) $form['actions']['submit']['#value']
    );
  }

  /**
   * @covers ::form
   */
  public function testBuildFormEditDisablesMachineName(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_format');
    /** @var \Drupal\name\Entity\NameFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_name_format_edit',
      'label' => 'Kernel edit',
      'pattern' => '!g !f',
    ]);
    $entity->save();

    $form_object = $this->getNameFormatForm($entity);
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
      ->getStorage('name_format');
    /** @var \Drupal\name\Entity\NameFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_name_format_exists',
      'label' => 'Exists test',
      'pattern' => '!g',
    ]);
    $entity->save();

    $form_object = $this->getNameFormatForm($entity);
    $form_state = new FormState();

    $this->assertNotNull(
      $form_object->exists('kernel_name_format_exists', [], $form_state)
    );
    $this->assertNull(
      $form_object->exists('no_such_name_format_id', [], $form_state)
    );
  }

  /**
   * @covers ::save
   */
  public function testSubmitSavePersistsAndSetsRedirect(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_format');
    /** @var \Drupal\name\Entity\NameFormat $entity */
    $entity = $storage->create([]);
    $form_object = $this->getNameFormatForm($entity);

    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);
    $form_state->setValues([
      'label' => 'Kernel new format',
      'id' => 'kernel_name_format_new',
      'pattern' => '!t !g !f',
    ]);

    $form_object->validateForm($form, $form_state);
    $this->assertCount(0, $form_state->getErrors());

    $form_object->submitForm($form, $form_state);
    $form_object->save($form, $form_state);

    $reloaded = NameFormat::load('kernel_name_format_new');
    $this->assertNotNull($reloaded);
    $this->assertSame('Kernel new format', $reloaded->label());
    $this->assertSame('!t !g !f', $reloaded->get('pattern'));

    $redirect = $form_state->getRedirect();
    $this->assertNotNull($redirect);
    $this->assertSame('name.name_format_list', $redirect->getRouteName());
  }

  /**
   * @covers ::save
   */
  public function testSaveExistingEntityAddsUpdatedStatusMessage(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_format');
    /** @var \Drupal\name\Entity\NameFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_name_format_update_msg',
      'label' => 'Before update',
      'pattern' => '!g',
    ]);
    $entity->save();

    $form_object = $this->getNameFormatForm($entity);
    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);
    $form_state->setValues([
      'label' => 'After update',
      'id' => 'kernel_name_format_update_msg',
      'pattern' => '!f !g',
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
      'Name format <em class="placeholder">After update</em> has been updated.',
      (string) $status_messages[0]
    );
  }

  /**
   * Verifies the delete action sets redirect to the entity delete form route.
   *
   * @covers \Drupal\name\Form\NameFormatForm::delete
   */
  public function testDeleteSetsRedirectToDeleteFormRoute(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_format');
    /** @var \Drupal\name\Entity\NameFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_name_format_delete_btn',
      'label' => 'Delete redirect',
      'pattern' => '!g',
    ]);
    $entity->save();

    $form_object = $this->getNameFormatForm($entity);
    $form = [];
    $form_state = new FormState();
    $form_object->delete($form, $form_state);

    $redirect = $form_state->getRedirect();
    $this->assertNotNull($redirect);
    $this->assertSame(
      'entity.name_format.delete_form',
      $redirect->getRouteName()
    );
    $this->assertSame(
      ['name_format' => 'kernel_name_format_delete_btn'],
      $redirect->getRouteParameters()
    );
  }

  /**
   * Gets the name format add/edit form with dependencies from the container.
   */
  private function getNameFormatForm(NameFormat $entity): NameFormatForm {
    /** @var \Drupal\name\Form\NameFormatForm $form_object */
    $form_object = $this->container->get('entity_type.manager')
      ->getFormObject('name_format', $entity->isNew() ? 'add' : 'edit');
    $form_object->setEntity($entity);
    $form_object->setModuleHandler($this->container->get('module_handler'));
    return $form_object;
  }

}

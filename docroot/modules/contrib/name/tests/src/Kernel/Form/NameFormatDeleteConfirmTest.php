<?php

declare(strict_types=1);

namespace Drupal\Tests\name\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\name\Entity\NameFormat;

/**
 * @coversDefaultClass \Drupal\name\Form\NameFormatDeleteConfirm
 *
 * @group name
 */
class NameFormatDeleteConfirmTest extends KernelTestBase {

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
   * @covers ::submitForm
   */
  public function testSubmitFormDeletesEntityAndRedirectsToList(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('name_format');
    /** @var \Drupal\name\Entity\NameFormat $entity */
    $entity = $storage->create([
      'id' => 'kernel_name_format_to_delete',
      'label' => 'To delete',
      'pattern' => '!g',
    ]);
    $entity->save();

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $form_object = $this->container->get('entity_type.manager')
      ->getFormObject('name_format', 'delete');
    $form_object->setEntity($entity);
    $form_object->setModuleHandler($this->container->get('module_handler'));

    $form = [];
    $form_state = new FormState();
    $form = $form_object->buildForm($form, $form_state);
    $form_object->submitForm($form, $form_state);

    $this->assertNull(NameFormat::load('kernel_name_format_to_delete'));

    $redirect = $form_state->getRedirect();
    $this->assertNotNull($redirect);
    $this->assertSame('name.name_format_list', $redirect->getRouteName());
  }

}

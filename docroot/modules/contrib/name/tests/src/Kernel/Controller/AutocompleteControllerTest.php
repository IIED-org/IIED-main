<?php

namespace Drupal\Tests\name\Kernel\Controller;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\name\Functional\NameTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\name\Controller\AutocompleteController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests name autocomplete.
 *
 * @group name
 */
class AutocompleteControllerTest extends KernelTestBase {

  use NameTestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'name',
    'field',
    'entity_test',
    'system',
    'user',
  ];

  /**
   * The entity listener.
   *
   * @var \Drupal\Core\Entity\EntityTypeListener
   */
  protected $entityListener;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(self::$modules);
    $this->installEntitySchema('user');

    $this->entityListener = \Drupal::service('entity_type.listener');
    $this->entityListener->onEntityTypeCreate(\Drupal::entityTypeManager()->getDefinition('entity_test'));

    $this->field = $this->createNameField('field_name_test', 'entity_test', 'entity_test');

    // The autocomplete controller gates its response on entity create access
    // for the target bundle, so the test must run as a user with entity_test
    // create permission.
    $this->setUpCurrentUser([], ['administer entity_test content']);
  }

  /**
   * Tests the controller.
   */
  public function testAutocompleteController() {
    $autocomplete = AutocompleteController::create($this->container);
    $request = new Request();
    $request->attributes->add(['q' => 'Bob']);

    $result = $autocomplete->autocomplete($request, 'field_name_test', 'entity_test', 'entity_test', 'family');
    $this->assertInstanceOf(JsonResponse::class, $result);
  }

  /**
   * Tests the controller with an invalid bundle.
   *
   * In this case it expected that an exception of type
   * AccessDeniedHttpException is thrown.
   */
  public function testAutocompleteControllerWithInvalidBundle() {
    $autocomplete = AutocompleteController::create($this->container);
    $request = new Request();
    $request->attributes->add(['q' => 'Bob']);

    $this->expectException(AccessDeniedHttpException::class);
    $autocomplete->autocomplete($request, 'field_name_test', 'entity_test', 'invalid_bundle', 'family');
  }

  /**
   * Tests access denied when the field exists but is not a name field.
   */
  public function testAutocompleteControllerWithNonNameField(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_string_test',
      'entity_type' => 'entity_test',
      'type' => 'string',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_string_test',
      'entity_type' => 'entity_test',
      'type' => 'string',
      'bundle' => 'entity_test',
    ])->save();

    $autocomplete = AutocompleteController::create($this->container);
    $request = new Request();

    $this->expectException(AccessDeniedHttpException::class);
    $autocomplete->autocomplete($request, 'field_string_test', 'entity_test', 'entity_test', 'family');
  }

  /**
   * Tests the service.
   */
  public function testAutocomplete() {
    $autocomplete = \Drupal::service('name.autocomplete');

    // Title component.
    $matches = $autocomplete->getMatches($this->field, 'title', 'M');
    $this->assertEquals($matches, $this->mapAssoc(['Mr.', 'Mrs.', 'Miss', 'Ms.']));

    $matches = $autocomplete->getMatches($this->field, 'title', 'Mr');
    $this->assertEquals($matches, $this->mapAssoc(['Mr.', 'Mrs.']));

    $matches = $autocomplete->getMatches($this->field, 'title', 'Pr');
    $this->assertEquals($matches, $this->mapAssoc(['Prof.']));

    $matches = $autocomplete->getMatches($this->field, 'title', 'X');
    $this->assertEquals($matches, []);

    // First name component.
    $names = [
      'SpongeBob SquarePants',
      'Patrick Star',
      'Squidward Tentacles',
      'Eugene Krabs',
      'Sandy Cheeks',
      'Gary Snail',
    ];
    foreach ($names as $name) {
      $name = explode(' ', $name);
      $entity = $this->container->get('entity_type.manager')
        ->getStorage('entity_test')
        ->create([
          'bundle' => 'entity_test',
          'field_name_test' => [
            'given' => $name[0],
            'family' => $name[1],
          ],
        ]);
      $entity->save();
    }

    $this->field->setSetting('autocomplete_source', [
      'title' => [],
      'given' => ['data'],
      'middle' => ['data'],
      'family' => ['data'],
      'generational' => [],
      'credentials' => [],
    ]);
    $this->field->save();

    $matches = $autocomplete->getMatches($this->field, 'name', 'S');
    $this->assertArrayHasKey('SpongeBob', $matches);
    $this->assertArrayHasKey('SquarePants', $matches);
    $this->assertArrayHasKey('Sandy', $matches);
    $this->assertArrayHasKey('Snail', $matches);
    $this->assertArrayNotHasKey('Patrick', $matches);
  }

}

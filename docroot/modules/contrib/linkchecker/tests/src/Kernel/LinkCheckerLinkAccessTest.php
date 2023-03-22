<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests basic linkchecker link access functionality.
 *
 * @group linkchecker
 */
class LinkCheckerLinkAccessTest extends KernelTestBase {

  use UserCreationTrait {
    createUser as drupalCreateUser;
    createRole as drupalCreateRole;
    createAdminRole as drupalCreateAdminRole;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'datetime',
    'user',
    'system',
    'filter',
    'field',
    'text',
    'path_alias',
    'dynamic_entity_reference',
    'linkchecker',
  ];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * List of fieldable entity types.
   *
   * @var \Drupal\Core\Entity\EntityType[]
   */
  protected $entityTypeDefinitions;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('linkchecker', 'linkchecker_index');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig('node');
    $this->installConfig('linkchecker');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    $this->accessHandler = $entityTypeManager
      ->getAccessControlHandler('linkcheckerlink');

    // Find all fieldable entities except LinkCheckerLink.
    foreach ($entityTypeManager->getDefinitions() as $definition) {
      if ($definition->entityClassImplements(FieldableEntityInterface::class)
        && $definition->id() != 'linkcheckerlink') {
        $this->entityTypeDefinitions[] = $definition;
      }
    }

    $this->entityTypeManager = $entityTypeManager;

    // Clear permissions for authenticated users.
    $this->config('user.role.' . RoleInterface::AUTHENTICATED_ID)
      ->set('permissions', [])
      ->save();

    // Create user 1 who has special permissions.
    $this->drupalCreateUser();
  }

  /**
   * Runs basic tests for link access.
   */
  public function testLinkAccess() {
    $webUsers = [];
    // Ensures user with 'access content' permission can view links.
    $webUsers[] = $this->drupalCreateUser([
      'administer linkchecker',
      'access content',
    ]);

    // Ensures user with 'access content' permission can view links.
    $webUsers[] = $this->drupalCreateUser([
      'edit linkchecker link settings',
      'access content',
    ]);

    // Ensures user without 'access content' permission can do nothing.
    $webUsers[] = $this->drupalCreateUser([
      'administer linkchecker',
    ]);

    // Ensures user with 'access content' permission can do nothing.
    $webUsers[] = $this->drupalCreateUser([
      'edit linkchecker link settings',
    ]);

    // Create each fieldable entity and test link access against it.
    foreach ($this->entityTypeDefinitions as $entityTypeDefinition) {
      $bundleId = $this->createBundle($entityTypeDefinition);

      // Create dummy field to which link will be assigned.
      $field_storage = [
        'field_name' => 'test_text_field',
        'entity_type' => $entityTypeDefinition->id(),
        'type' => 'text_long',
      ];
      FieldStorageConfig::create($field_storage)->save();
      $field = [
        'field_name' => $field_storage['field_name'],
        'entity_type' => $entityTypeDefinition->id(),
        'bundle' => $bundleId,
      ];
      FieldConfig::create($field)->save();

      $entity = $this->createEntity($entityTypeDefinition, $bundleId);

      $link = LinkCheckerLink::create([
        'url' => 'http://example.com/',
        'entity_id' => [
          'target_id' => $entity->id(),
          'target_type' => $entity->getEntityTypeId(),
        ],
        'entity_field' => $field_storage['field_name'],
        'entity_langcode' => $entity->language()->getId(),
      ]);
      $link->save();

      foreach ($webUsers as $user) {
        $access = $entity->get($field_storage['field_name'])
          ->access('view', $user, FALSE);
        $access = $access && $entity->access('view', $user, FALSE);
        $this->assertLinkAccess([
          'view' => $access,
        ], $link, $user);
      }
    }

    // Check access if parent entity is not exists.
    // By default such links will be cleaned up, but in a case if somebody will
    // remove parent entity directly from database we should handle it.
    $link = LinkCheckerLink::create([
      'url' => 'http://example.com/',
      'entity_id' => [
        'target_id' => 9999,
        'target_type' => 'node',
      ],
      'entity_field' => 'test_text_field',
      'entity_langcode' => 'en',
    ]);

    $link->save();

    foreach ($webUsers as $user) {
      $this->assertLinkAccess([
        'view' => FALSE,
      ], $link, $user);
    }
  }

  /**
   * Asserts that link access correctly grants or denies access.
   *
   * @param array $ops
   *   An associative array of the expected link access grants for the link
   *   and account, with each key as the name of an operation (e.g. 'view',
   *   'delete') and each value a Boolean indicating whether access to that
   *   operation should be granted.
   * @param \Drupal\linkchecker\LinkCheckerLinkInterface $link
   *   The link object to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account for which to check access.
   */
  public function assertLinkAccess(array $ops, LinkCheckerLinkInterface $link, AccountInterface $account) {
    foreach ($ops as $op => $result) {
      $this->assertEquals($result, $this->accessHandler->access($link, $op, $account), $this->linkAccessAssertMessage($op, $result));
    }
  }

  /**
   * Constructs an assert message to display which link access was tested.
   *
   * @param string $operation
   *   The operation to check access for.
   * @param bool $result
   *   Whether access should be granted or not.
   *
   * @return string
   *   An assert message string which contains information in plain English
   *   about the link access permission test that was performed.
   */
  public function linkAccessAssertMessage($operation, $result) {
    return new FormattableMarkup(
      'LinkCheckerLink access returns @result with operation %op.',
      [
        '@result' => $result ? 'true' : 'false',
        '%op' => $operation,
      ]
    );
  }

  /**
   * Helper function for bundle creation.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityTypeDefinition
   *   The enity type.
   *
   * @return string
   *   The nudle ID.
   */
  protected function createBundle(EntityTypeInterface $entityTypeDefinition) {
    if ($bundleEntityType = $entityTypeDefinition->getBundleEntityType()) {
      $bundleStorage = $this->entityTypeManager->getStorage($bundleEntityType);
      // To be sure that we will create non-existing bundle.
      do {
        $bundleId = strtolower($this->randomMachineName(8));
      } while ($bundleStorage->load($bundleId));

      $bundleTypeDefinition = $this->entityTypeManager->getDefinition($bundleEntityType);
      $bundle = $bundleStorage->create([
        $bundleTypeDefinition->getKey('id') => $bundleId,
        $bundleTypeDefinition->getKey('label') => $bundleId,
      ]);
      $bundle->save();
    }
    // Some entities does not have bundles.
    else {
      $bundleId = $entityTypeDefinition->id();
    }

    return $bundleId;
  }

  /**
   * Helper function for entity creation.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityTypeDefinition
   *   The entity type.
   * @param string $bundleId
   *   Bundle ID.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity.
   */
  protected function createEntity(EntityTypeInterface $entityTypeDefinition, $bundleId) {
    $entityData = [];

    $entityData[$entityTypeDefinition->getKey('bundle')] = $bundleId;
    if ($entityTypeDefinition->hasKey('published')) {
      $entityData[$entityTypeDefinition->getKey('published')] = 1;
    }

    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $this->entityTypeManager
      ->getStorage($entityTypeDefinition->id())
      ->create($entityData);

    foreach ($entity->getFieldDefinitions() as $fieldDefinition) {
      // Skip read-only fields.
      if ($fieldDefinition->isReadOnly()) {
        continue;
      }
      // Skip non-required fields.
      if (!$fieldDefinition->isRequired()) {
        continue;
      }

      // Skip non-empty fields.
      if (!$entity->get($fieldDefinition->getName())->isEmpty()) {
        continue;
      }

      $field = $entity->get($fieldDefinition->getName());
      switch ($fieldDefinition->getType()) {
        case 'integer':
          $field->setValue(['value' => 1]);
          break;

        default:
          $field->setValue(['value' => $this->randomString()]);
          break;
      }
    }

    $entity->save();
    return $entity;
  }

}

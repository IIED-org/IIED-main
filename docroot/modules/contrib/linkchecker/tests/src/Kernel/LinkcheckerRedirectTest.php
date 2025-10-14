<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Test linkchecker with the redirect module.
 *
 * @group linkchecker
 */
class LinkcheckerRedirectTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  public $cron;

  /**
   * The redirect storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $redirectStorage;

  /**
   * The linkcheckerlink storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkCheckerLinkStorage;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'node',
    'filter',
    'field',
    'text',
    'system',
    'path_alias',
    'link',
    'views',
    'linkchecker',
    'redirect',
    'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Installing sequences table is deprecated since 10.2 release so call it
    // conditionally.
    // @see https://www.drupal.org/node/3349345
    if (version_compare(\Drupal::VERSION, '10.2', '<')) {
      $this->installSchema('system', 'sequences');
    }
    $this->installSchema('linkchecker', 'linkchecker_index');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('redirect');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig([
      'field',
      'node',
      'filter',
      'linkchecker',
      'redirect',
    ]);

    $this->cron = $this->container->get('cron');
    $this->redirectStorage = $this->container->get('entity_type.manager')
      ->getStorage('redirect');
    $this->linkCheckerLinkStorage = $this->container->get('entity_type.manager')
      ->getStorage('linkcheckerlink');

    NodeType::create(['name' => 'Links', 'type' => 'links']);
  }

  /**
   * Test the linkchecker module with redirect integration.
   */
  public function testLinkcheckerRedirect() {
    $node = $this->createNode(['type' => 'links']);
    $this->linkCheckerLinkStorage->create([
      'url' => '/non-existing-url',
      'parent_entity_type_id' => $node->getEntityTypeId(),
      'parent_entity_id' => $node->id(),
      'entity_field' => 'body',
      'entity_langcode' => $node->language()->getId(),
      'last_check' => 680356800,
      'fail_count' => 3,
      'status' => 1,
    ])->save();

    $redirect = $this->redirectStorage->create();
    $redirect->setSource('non-existing-url');
    $redirect->setRedirect('<front>');
    $redirect->setStatusCode(301);
    $redirect->save();

    $links = $this->linkCheckerLinkStorage->loadByProperties(['url' => '/non-existing-url']);
    $this->assertNotEmpty($links);
    $link = current($links);

    // Make sure the last_check value is reset when a redirect is created.
    $this->assertNull($link->get('last_check')->value);
  }

}

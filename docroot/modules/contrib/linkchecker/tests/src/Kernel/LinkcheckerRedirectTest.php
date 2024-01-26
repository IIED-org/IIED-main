<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;

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
    'dynamic_entity_reference',
    'linkchecker',
    'redirect',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('linkchecker', 'linkchecker_index');
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
      'url' => '/unexisting-url',
      'entity_id' => [
        'target_id' => $node->id(),
        'target_type' => $node->getEntityTypeId(),
      ],
      'entity_field' => 'body',
      'entity_langcode' => $node->language()->getId(),
      'last_check' => 680356800,
      'fail_count' => 3,
      'status' => 1,
    ])->save();

    $redirect = $this->redirectStorage->create();
    $redirect->setSource('unexisting-url');
    $redirect->setRedirect('<front>');
    $redirect->setStatusCode(301);
    $redirect->save();

    $links = $this->linkCheckerLinkStorage->loadByProperties(['url' => '/unexisting-url']);
    $this->assertNotEmpty($links);
    $link = current($links);

    // Make sure the last_check value is reset when a redirect is created.
    $this->assertNull($link->get('last_check')->value);
  }

}

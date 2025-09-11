<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\Core\Queue\DatabaseQueue;
use Drupal\KernelTests\KernelTestBase;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Test for linkchecker.clean_up service.
 *
 * @coversDefaultClass \Drupal\linkchecker\LinkCleanUp
 *
 * @group linkchecker
 */
class LinkcheckerCleanUpTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'linkchecker',
    'node',
    'user',
    'field',
    'filter',
    'text',
  ];

  /**
   * The link checker service.
   *
   * @var \Drupal\linkchecker\LinkCheckerService
   */
  protected $linkChecker;

  /**
   * The link clean up service.
   *
   * @var \Drupal\linkchecker\LinkCleanUp
   */
  protected $linkCleanUp;

  /**
   * The link checker link storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $linkCheckerLinkStorage;

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
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('user');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig(['field', 'node', 'filter', 'linkchecker']);
    $this->installSchema('linkchecker', 'linkchecker_index');

    $this->linkCleanUp = $this->container->get('linkchecker.clean_up');
    $this->linkCheckerLinkStorage = $this->container->get('entity_type.manager')
      ->getStorage('linkcheckerlink');
    $this->linkChecker = $this->container->get('linkchecker.checker');

    // Prepare queue table cuz it's being used in hook_entity_delete().
    $database_connection = $this->container->get('database');
    $database_schema = $database_connection->schema();
    $database_queue = new DatabaseQueue($this->randomString(), $database_connection);
    $schema_definition = $database_queue->schemaDefinition();
    $database_schema->createTable(DatabaseQueue::TABLE_NAME, $schema_definition);
  }

  /**
   * @covers ::cleanUpForEntity
   */
  public function testEntityCleanup() {
    $urls = [
      'http://httpstat.us/304',
      'http://httpstat.us/503',
    ];

    $node_type = NodeType::create([
      'type' => 'page',
    ]);
    $node_type->save();
    node_add_body_field($node_type);
    $node = $this->createNode([
      'type' => 'page',
      'body' => [
        [
          'value' => '
          <a href="http://httpstat.us/304">The nightmare continues</a>',
        ],
      ],
    ]);
    $fieldDefinition = $node->get('body')->getFieldDefinition();
    /** @var \Drupal\field\Entity\FieldConfig $config */
    $config = $fieldDefinition->getConfig($node->bundle());
    $config->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $config->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $config->save();

    foreach ($urls as $url) {
      $link = $this->createDummyLink($url);
      $link->setParentEntity($node);
      $link->setParentEntityFieldName($config->getName());
      $link->save();
    }

    // So, given we have 2 link entities that seemingly belong to this new
    // entity, and then we run the cleanup function to see which links should
    // really be there, we now expect it to be 1 link, since only one of them
    // are found in the node body.
    $this->assertCount(2, $this->linkCheckerLinkStorage->loadMultiple(NULL));
    $this->linkCleanUp->cleanUpForEntity($node);
    $this->assertCount(1, $this->linkCheckerLinkStorage->loadMultiple(NULL));
  }

  /**
   * @covers ::removeAllBatch
   */
  public function testRemoveAllBatch() {
    $urls = [
      'https://existing.com',
      'https://not-existing.com',
      'https://example.com/existing',
    ];

    foreach ($urls as $url) {
      $this->createDummyLink($url);
    }

    $this->assertCount(3, $this->linkCheckerLinkStorage->loadMultiple(NULL));

    $this->linkCleanUp->removeAllBatch();
    $this->runBatch();

    $this->assertEmpty($this->linkCheckerLinkStorage->loadMultiple($this->linkCheckerLinkStorage->loadMultiple(NULL)));
  }

  /**
   * Checks if removed links are being cleared from queues.
   */
  public function testQueueCleanUp(): void {
    $type = NodeType::create(['name' => 'Links', 'type' => 'links']);
    $type->save();
    node_add_body_field($type);

    $node = $this->createNode([
      'type' => 'links',
      'body' => [
        [
          'value' => '<a href="https://existing.com"></a>',
        ],
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);

    $fieldDefinition = $node->get('body')->getFieldDefinition();
    $config = $fieldDefinition->getConfig($node->bundle());
    $config->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $config->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $config->save();

    // Re-save the node, so we can find it in the linkchecker entities.
    $node->save();

    // We expect it to be queued 1 item if we run the service now.
    $number = $this->linkChecker->queueLinks(TRUE);
    self::assertEquals(1, $number);

    // Now delete the link entity and see if it was cleared from queue.
    $this->linkCheckerLinkStorage->delete($this->linkCheckerLinkStorage->loadMultiple());
    $number_after_deletion = $this->linkChecker->queueLinks();
    self::assertEquals(0, $number_after_deletion);

    // Try a case when 1 queue item contains multiple linkchecker entities ids.
    // Only the id of the entity that was deleted should be cleaned but the
    // queue item should stay.
    $node2 = $this->createNode([
      'type' => 'links',
      'body' => [
        [
          'value' => '<a href="https://existing.com"></a> and another link - <a href="https://google.com/non-existing"></a>',
        ],
      ],
      'status' => NodeInterface::PUBLISHED,
    ]);
    $node2->save();

    $number = $this->linkChecker->queueLinks(TRUE);
    // Verify that only 1 item is in queue.
    self::assertEquals(1, $number);
    $links_ids = $this->linkCheckerLinkStorage->getQuery()->accessCheck(FALSE)->execute();
    // Verify that both links are created.
    self::assertCount(2, $links_ids);
    [$to_delete_id, $remaining_id] = array_values($links_ids);
    // Delete 1 entity and verify that queue item is not deleted but containing
    // only 1 id.
    $this->linkCheckerLinkStorage->delete([$this->linkCheckerLinkStorage->load($to_delete_id)]);
    $queue_item = $this->container->get('database')
      ->select('queue', 'q')
      ->fields('q', ['data'])
      ->condition('name', 'linkchecker_check')
      ->execute()
      ->fetchCol();
    self::assertNotEmpty($queue_item);
    $queue_item_unserialized = unserialize(reset($queue_item), ['allowed_classes' => FALSE]);
    self::assertTrue(in_array($remaining_id, $queue_item_unserialized, TRUE) && !in_array($to_delete_id, $queue_item_unserialized, TRUE));
  }

  /**
   * Helper function for link creation.
   */
  protected function createDummyLink($url) {
    /** @var \Drupal\linkchecker\Entity\LinkCheckerLink $link */
    $link = LinkCheckerLink::create([
      'url' => $url,
      'parent_entity_type_id' => 'dummy_type',
      'parent_entity_id' => 1,
      'entity_field' => 'dummy_field',
      'entity_langcode' => 'en',
    ]);
    $link->save();
    return $link;
  }

  /**
   * Runs the currently set batch, if any exists.
   */
  protected function runBatch() {
    $batch = &batch_get();
    if ($batch) {
      $batch['progressive'] = FALSE;
      batch_process();
    }
  }

}

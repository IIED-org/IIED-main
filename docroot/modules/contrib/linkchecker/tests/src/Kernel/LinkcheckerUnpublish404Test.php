<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\Core\Queue\DatabaseQueue;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use GuzzleHttp\Psr7\Response;

/**
 * Test for Unpublish on 404 status handling.
 *
 * @group linkchecker
 */
class LinkcheckerUnpublish404Test extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'user',
    'system',
    'field',
    'filter',
    'text',
    'linkchecker',
    'path_alias',
  ];

  /**
   * Link checker service.
   *
   * @var \Drupal\linkchecker\LinkCheckerService
   */
  protected $checkerService;

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $linkcheckerSetting;

  /**
   * The handler.
   *
   * @var \Drupal\linkchecker\Plugin\LinkStatusHandler\Unpublish404
   */
  protected $unpublish404Handler;

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
    $this->installSchema('node', 'node_access');
    $this->installSchema('linkchecker', 'linkchecker_index');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig([
      'field',
      'user',
      'node',
      'filter',
      'linkchecker',
      'system',
    ]);

    $this->checkerService = $this->container->get('linkchecker.checker');
    $this->linkcheckerSetting = $this->container->get('config.factory')
      ->getEditable('linkchecker.settings');
    $this->unpublish404Handler = $this->container->get('plugin.manager.link_status_handler')
      ->createInstance('unpublish_404');

    // Prepare queue table cuz it's being used in hook_entity_delete().
    $database_connection = $this->container->get('database');
    $database_schema = $database_connection->schema();
    $database_queue = new DatabaseQueue($this->randomString(), $database_connection);
    $schema_definition = $database_queue->schemaDefinition();
    $database_schema->createTable(DatabaseQueue::TABLE_NAME, $schema_definition);

    // Since install hooks are not being called in Kernel tests - do it
    // manually.
    // We're calling it to set the impersonate_account in the config.
    // @see \Drupal\KernelTests\KernelTestBase::enableModules()
    $this->setUpCurrentUser(['uid' => 1], [
      'administer linkchecker',
      'access content',
    ]);
    linkchecker_install();
  }

  /**
   * Test link checker service status handling.
   */
  public function testStatusHandling() {
    // Extract all links.
    $this->linkcheckerSetting->set('check_links_types', LinkCheckerLinkInterface::TYPE_ALL);
    $this->linkcheckerSetting->save(TRUE);

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
    ]);
    // Make sure that node is published.
    $node->setPublished();
    $node->save();

    $fieldDefinition = $node->get('body')->getFieldDefinition();
    $config = $fieldDefinition->getConfig($node->bundle());
    $config->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $config->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $config->save();

    $urls = [
      'https://existing.com',
      'https://not-existing.com',
    ];

    /** @var \Drupal\linkchecker\LinkCheckerLinkInterface[] $links */
    $links = [];
    foreach ($urls as $url) {
      $tmpLink = LinkCheckerLink::create([
        'url' => $url,
        'parent_entity_type_id' => $node->getEntityTypeId(),
        'parent_entity_id' => $node->id(),
        'entity_field' => 'body',
        'entity_langcode' => $node->language()->getId(),
      ]);
      $tmpLink->save();
      $links[] = $tmpLink;
    }

    // Check if this handler will not unpublish if it disabled.
    $this->linkcheckerSetting->set('error.action_status_code_404', 0);
    $this->linkcheckerSetting->save(TRUE);
    foreach ($links as $link) {
      $this->unpublish404Handler->handle($link, new Response());
      $node = $this->reloadNode($node);
      $this->assertTrue($node->isPublished());
    }

    // Enable unpublish on 404 and update fail count to each link.
    $this->linkcheckerSetting->set('error.action_status_code_404', 2);
    $this->linkcheckerSetting->save(TRUE);
    foreach ($links as $link) {
      $link->setFailCount(2);
      $link->save();
    }

    // Make sure that node is published.
    $node->setPublished();
    $node->save();
    // Check if this handler will not unpublish
    // if link is not exists in content.
    $this->unpublish404Handler->handle($links[1], new Response());
    $node = $this->reloadNode($node);
    $this->assertTrue($node->isPublished());

    // Make sure that node is published.
    $node->setPublished();
    $node->save();
    // Check if this handler will unpublish
    // if link is exists in content and fail count is reached.
    $this->unpublish404Handler->handle($links[0], new Response());
    $node = $this->reloadNode($node);
    $this->assertTrue(!$node->isPublished());
  }

  /**
   * Gets node last updated data from DB.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to reload.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\node\Entity\Node|null
   *   Reloaded node.
   */
  protected function reloadNode(NodeInterface $node) {
    return Node::load($node->id());
  }

}

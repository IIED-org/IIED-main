<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\node\Entity\NodeType;

/**
 * Test for making sure queueing links gets us the number we expect.
 *
 * @group linkchecker
 */
class QueueLinksTest extends KernelTestBase {

  use NodeCreationTrait;

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
    $this->installConfig(['field', 'user', 'node', 'filter', 'linkchecker']);

    $this->checkerService = $this->container->get('linkchecker.checker');
  }

  /**
   * Test link checker unpublished link handling.
   */
  public function testUnpublishedLink() {
    $link = LinkCheckerLink::create([
      'url' => 'https://do-not-test.com',
      'parent_entity_type_id' => 'dummy_type',
      'parent_entity_id' => 1,
      'entity_field' => 'dummy_field',
      'entity_langcode' => 'en',
    ]);
    $link->setDisableLinkCheck();
    $link->save();

    $number = $this->checkerService->queueLinks(TRUE);
    self::assertEquals(0, $number);
  }

  /**
   * Test link checker service status handling.
   */
  public function testNumberOfItems() {
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

    // Re-save the node, so we can find it in the linkchecker entities.
    $node->save();

    // We expect it to be queued 1 item if we run the service now.
    $number = $this->checkerService->queueLinks(TRUE);
    self::assertEquals(1, $number);
  }

}

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
 * Test for Repair on 301 status handling.
 *
 * @group linkchecker
 */
class LinkcheckerRepair301Test extends KernelTestBase {

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
  protected $repair301;

  /**
   * HTTP protocol.
   *
   * @var string
   */
  protected $httpProtocol;

  /**
   * Base url.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

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
    $this->repair301 = $this->container->get('plugin.manager.link_status_handler')
      ->createInstance('repair_301');

    $this->request = $this->container->get('request_stack')
      ->getCurrentRequest();

    if (isset($this->request)) {
      $this->httpProtocol = $this->request->getScheme() . '://';
      $this->baseUrl = $this->request->getSchemeAndHttpHost() . $this->request->getBasePath();
    }
    else {
      $this->httpProtocol = $this->linkcheckerSetting->get('default_url_scheme');
      $this->baseUrl = $this->httpProtocol . $this->linkcheckerSetting->get('base_path');
    }
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
          'value' => '<a href="https://existing.com"></a>'
          . '<a href="/internal"></a>',
        ],
      ],
    ]);

    $fieldDefinition = $node->get('body')->getFieldDefinition();
    $config = $fieldDefinition->getConfig($node->bundle());
    $config->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $config->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $config->save();

    // Test external link.
    $link = LinkCheckerLink::create([
      'url' => 'https://existing.com',
      'parent_entity_type_id' => $node->getEntityTypeId(),
      'parent_entity_id' => $node->id(),
      'entity_field' => 'body',
      'entity_langcode' => $node->language()->getId(),
    ]);
    $link->save();

    // Check if this handler will not replace link if it disabled.
    $this->linkcheckerSetting->set('error.action_status_code_301', 0);
    $this->linkcheckerSetting->save(TRUE);
    $this->repair301->handle($link, new Response(301, ['Location' => 'https://existing.com/redirect']));
    $this->assertTrue($link->isExists());

    // Enable repair on 301 and update fail count to each link.
    $this->linkcheckerSetting->set('error.action_status_code_301', 2);
    $this->linkcheckerSetting->save(TRUE);
    $link->setFailCount(2);
    $link->save();

    // Check if this handler will replace link
    // if link is exists in content and fail count is reached.
    $this->repair301->handle($link, new Response(301, ['Location' => 'https://existing.com/redirect']));
    $this->assertFalse($link->isExists());
    $node = $this->reloadNode($node);
    $body = $node->body->value;
    // Put link inside href attribute to be sure that it was replaced
    // without errors.
    $this->assertFalse(strpos($body, 'href="' . $link->getUrl() . '"'));
    $this->assertNotFalse(strpos($body, 'href="https://existing.com/redirect"'));

    // Test internal link.
    $link = LinkCheckerLink::create([
      'url' => $this->baseUrl . '/internal',
      'parent_entity_type_id' => $node->getEntityTypeId(),
      'parent_entity_id' => $node->id(),
      'entity_field' => 'body',
      'entity_langcode' => $node->language()->getId(),
    ]);
    $link->setFailCount(2);
    $link->save();

    // Check if this handler will replace link
    // if link is exists in content and fail count is reached.
    $this->repair301->handle($link, new Response(301, ['Location' => $this->baseUrl . '/replaced']));
    $this->assertFalse($link->isExists());
    $node = $this->reloadNode($node);
    $body = $node->body->value;
    // Put link inside href attribute to be sure that it was replaced
    // without errors.
    $this->assertFalse(strpos($body, 'href="/internal"'));
    $this->assertNotFalse(strpos($body, 'href="/replaced"'));
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

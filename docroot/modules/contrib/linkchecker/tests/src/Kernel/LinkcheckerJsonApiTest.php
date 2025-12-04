<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\jsonapi\ResourceResponse;
use Drupal\node\Entity\NodeType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test linkchecker & json api.
 *
 * @group linkchecker
 */
class LinkcheckerJsonApiTest extends KernelTestBase {

  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'linkchecker',
    'node',
    'user',
    'field',
    'jsonapi',
    'serialization',
    'file',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Base preparation.
    $this->installSchema('linkchecker', 'linkchecker_index');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig(['field', 'node', 'linkchecker']);

    // Create node type with body field.
    $type = NodeType::create(['name' => 'Article', 'type' => 'article']);
    $type->save();
    node_add_body_field($type);

    // Prepare just 1 test node.
    $node = $this->createNode([
      'type' => 'article',
      'body' => [
        'value' => '<a href="https://drupal.org/non-existing-page-123">Awesome link</a>',
      ],
    ]);

    $field_definition = $node->get('body')->getFieldDefinition();
    /** @var \Drupal\field\Entity\FieldConfig $config */
    $config = $field_definition->getConfig('article');
    $config->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $config->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $config->save();

    // Generate linkchecker entities.
    $extractor = \Drupal::service('linkchecker.extractor');
    $links = $extractor->extractFromEntity($node);
    $this->assertNotEmpty($links);
    $extractor->saveLinkMultiple($links);
  }

  /**
   * Checks if json api endpoint returns correct response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testLinkcheckerJsonApi() {
    $request = Request::create('/jsonapi/linkcheckerlink/linkcheckerlink');
    $entity_resource = $this->container->get('jsonapi.entity_resource');
    $resource_type = $this->container->get('jsonapi.resource_type.repository')->get('linkcheckerlink', 'linkcheckerlink');
    $response = $entity_resource->getCollection($resource_type, $request);
    $this->assertInstanceOf(ResourceResponse::class, $response);
    $this->assertEquals(200, $response->getStatusCode());
    // It should be empty because anonymous users have no appropriate
    // permissions.
    $this->assertEmpty($response->getResponseData()->getData()->toArray());
    // To check if our entity is actually available via JSON:API - login
    // as user with administer linkchecker permission or as user with
    // edit linkchecker link settings && access content.
    // @see \Drupal\linkchecker\LinkCheckerLinkAccessControlHandler::checkAccess
    $this->setUpCurrentUser([], [
      'access content',
      'edit linkchecker link settings',
    ]);
    $response = $entity_resource->getCollection($resource_type, $request);
    $this->assertNotEmpty($response->getResponseData()->getData()->toArray());
  }

}

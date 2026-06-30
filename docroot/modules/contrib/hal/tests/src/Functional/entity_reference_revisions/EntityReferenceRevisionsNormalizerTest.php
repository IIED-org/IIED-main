<?php

namespace Drupal\Tests\hal\Functional\entity_reference_revisions;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Tests the entity_reference_revisions configuration.
 *
 * @group hal
 * @requires module entity_reference_revisions
 */
#[Group('hal')]
#[RunTestsInSeparateProcesses]
class EntityReferenceRevisionsNormalizerTest extends BrowserTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'entity_reference_revisions',
    'field_ui',
    'block',
    'hal',
    'serialization',
    'rest',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create paragraphs and article content types.
    $this->drupalCreateContentType(['type' => 'entity_revisions', 'name' => 'Entity revisions']);
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
  }

  /**
   * Tests the entity reference revisions configuration.
   */
  public function testEntityReferenceRevisions() {
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer nodes',
      'create article content',
      'create entity_revisions content',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'edit any article content',
    ]);
    $this->drupalLogin($admin_user);
    // Create entity reference revisions field.
    static::fieldUIAddNewField('admin/structure/types/manage/entity_revisions', 'entity_reference_revisions', 'Entity reference revisions', 'entity_reference_revisions', [
      'settings[target_type]' => 'node',
      'cardinality' => '-1',
    ], ['settings[handler_settings][target_bundles][article]' => TRUE]);
    $this->assertSession()->pageTextContains('Saved Entity reference revisions configuration.');
    \Drupal::service(EntityFieldManagerInterface::class)->clearCachedFieldDefinitions();

    // Create an article.
    $title = $this->randomMachineName();
    $edit = [
      'title[0][value]' => $title,
      'body[0][value]' => 'Revision 1',
    ];
    $this->drupalGet('node/add/article');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 1');
    $node = $this->drupalGetNodeByTitle($title);

    // Create entity revisions content that includes the above article.
    $err_title = 'Entity reference revision content';
    $edit = [
      'title[0][value]' => $err_title,
      'field_entity_reference_revisions[0][target_id]' => $node->label() . ' (' . $node->id() . ')',
    ];
    $this->drupalGet('node/add/entity_revisions');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('Entity revisions Entity reference revision content has been created.');
    $err_node = $this->drupalGetNodeByTitle($err_title);
    self::assertEquals('entity_revisions', $err_node->bundle());
    self::assertFalse($err_node->get('field_entity_reference_revisions')->isEmpty());

    $this->assertSession()->pageTextContains($err_title);
    $this->assertSession()->pageTextContains($title);
    $this->assertSession()->pageTextContains('Revision 1');

    // Create 2nd revision of the article.
    $edit = [
      'body[0][value]' => 'Revision 2',
      'revision' => TRUE,
    ];
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm($edit, 'Save');
    $serializer = $this->container->get('serializer');
    $err_node = \Drupal::entityTypeManager()->getStorage('node')->loadUnchanged($err_node->id());
    $normalized = $serializer->normalize($err_node, 'hal_json');
    $request = \Drupal::request();
    $link_domain = $request->getSchemeAndHttpHost() . $request->getBasePath();
    $this->assertEquals($err_node->field_entity_reference_revisions->target_revision_id, $normalized['_embedded'][$link_domain . '/rest/relation/node/entity_revisions/field_entity_reference_revisions'][0]['target_revision_id']);
    $new_err_node = $serializer->denormalize($normalized, Node::class, 'hal_json');
    $this->assertEquals($err_node->field_entity_reference_revisions->target_revision_id, $new_err_node->field_entity_reference_revisions->target_revision_id);
  }

}

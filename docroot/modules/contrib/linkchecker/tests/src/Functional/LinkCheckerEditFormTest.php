<?php

namespace Drupal\Tests\linkchecker\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Test Link checker module edit form.
 *
 * @group linkchecker
 */
class LinkCheckerEditFormTest extends BrowserTestBase {

  use StringTranslationTrait;

  const NODE_TYPE = 'page';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public $adminUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'content_translation',
    'linkchecker',
    'node',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();
    // Create Basic page and Article node types.
    $node_type = NodeType::create([
      'type' => static::NODE_TYPE,
      'name' => 'Basic page',
      'format' => 'full_html',
    ]);
    $node_type->save();

    // Create a body field instance for the 'page' node type.
    $node_body_field = node_add_body_field($node_type);
    $node_body_field->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $node_body_field->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $node_body_field->save();
    $this->adminUser = $this->drupalCreateUser([
      'administer linkchecker',
      'bypass node access',
      'access broken links report',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test that we can go to individual edit URls of entities.
   *
   * @see https://www.drupal.org/project/linkchecker/issues/3118940
   */
  public function testEditUrlWorks() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    /** @var \Drupal\node\NodeInterface $entity */
    $entity = $entity_type_manager->getStorage('node')->create([
      'type' => self::NODE_TYPE,
      'title' => 'test node',
    ]);
    $field_item_list = $entity->get('body');
    $field_item_list->setValue('<a href="https://example.com">test</a>');
    $entity->save();
    /** @var \Drupal\linkchecker\Entity\LinkCheckerLink $link */
    $link = $entity_type_manager->getStorage('linkcheckerlink')
      ->create([
        'entity_id' => [
          'target_id' => $entity->id(),
          'target_type' => $entity->getEntityTypeId(),
        ],
        'entity_field' => $field_item_list->getFieldDefinition()->getName(),
        'entity_langcode' => $field_item_list->getLangcode(),
      ]);
    $link->save();
    // Run cron.
    $this->container->get('cron')->run();

    // Now visit edit form for the linkchecker entity.
    $this->drupalGet($link->toUrl('edit-form')->toString());
    $this->assertEquals($this->getSession()->getStatusCode(), 200);
  }

}

<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests linkchecker migrations.
 *
 * @group linkchecker
 */
class LinkCheckerMigrationTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'linkchecker',
    'node',
    'text',
    'filter',
    'menu_ui',
    'comment',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('linkchecker'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Tests linkchecker node to field migration.
   */
  public function testMigration(): void {
    $this->installConfig(['node', 'comment']);
    $this->installSchema('linkchecker', ['linkchecker_index']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->executeMigrations([
      'd7_node_type',
      'd7_comment_type',
      'd7_filter_format',
      'd7_field',
      'd7_field_instance',
    ]);
    $article_body_field = FieldConfig::load('node.article.body');
    assert($article_body_field instanceof FieldConfigInterface);
    $this->assertEquals([
      'scan' => TRUE,
      'extractor' => 'html_link_extractor',
    ],
      $article_body_field->getThirdPartySettings('linkchecker')
    );
    $page_body_field = FieldConfig::load('node.page.body');
    assert($page_body_field instanceof FieldConfigInterface);
    $this->assertEquals([
      'scan' => TRUE,
      'extractor' => 'html_link_extractor',
    ],
      $page_body_field->getThirdPartySettings('linkchecker')
    );
    $article_comment_field = FieldConfig::load('comment.comment_node_article.comment_body');
    assert($article_comment_field instanceof FieldConfigInterface);
    $this->assertEquals([
      'scan' => TRUE,
      'extractor' => 'html_link_extractor',
    ],
      $article_comment_field->getThirdPartySettings('linkchecker')
    );
  }

}

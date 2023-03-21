<?php

namespace Drupal\Tests\linkchecker\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\filter\Entity\FilterFormat;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Base browser testcase for the Link checker module.
 *
 * @group linkchecker
 */
abstract class LinkCheckerBaseTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'linkchecker',
    'node',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Full HTML text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $filtered_html_format->save();

    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $full_html_format->save();

    // Create Basic page and Article node types.
    $page_node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'format' => 'full_html',
    ]);
    $page_node_type->save();

    $article_node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'format' => 'full_html',
    ]);
    $article_node_type->save();

    // Create a body field instance for the 'page' node type.
    $node_body_field = node_add_body_field($page_node_type);
    $node_body_field->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $node_body_field->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $node_body_field->save();

    // Configure basic settings.
    $this->config('linkchecker.settings')->set('default_url_scheme', 'http://')->save();
    $this->config('linkchecker.settings')->set('base_path', 'unexistingdomain.org/')->save();

    $this->config('linkchecker.settings')->set('check.disable_link_check_for_urls', '')->save();
    $this->config('linkchecker.settings')->set('check_links_types', LinkCheckerLinkInterface::TYPE_ALL)->save();

    // Core enables the URL filter for "Full HTML" by default.
    // -> Blacklist / Disable URL filter for testing.
    $this->config('linkchecker.settings')->set('extract.filter_blacklist', ['filter_url' => 'filter_url'])->save();

    // Extract from all link checker supported HTML tags.
    $this->config('linkchecker.settings')->set('extract.from_a', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_audio', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_embed', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_iframe', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_img', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_object', 1)->save();
    $this->config('linkchecker.settings')->set('extract.from_video', 1)->save();

    // User to set up link checker.
    $this->adminUser = $this->drupalCreateUser([
      'administer linkchecker',
    ]);
    $this->drupalLogin($this->adminUser);
  }

}

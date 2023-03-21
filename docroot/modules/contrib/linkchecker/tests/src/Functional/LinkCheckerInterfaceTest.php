<?php

namespace Drupal\Tests\linkchecker\Functional;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Random;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\filter\Entity\FilterFormat;
use Drupal\linkchecker\Entity\LinkCheckerLink;
use Drupal\linkchecker\LinkCheckerLinkInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Test case for interface tests.
 *
 * @group linkchecker
 */
class LinkCheckerInterfaceTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block_content',
    'comment',
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

    $full_html_format = FilterFormat::create([
      'format' => 'full_html',
      'name' => 'Full HTML',
    ]);
    $full_html_format->save();

    // Create Basic page and Article node types.
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Basic page',
      'format' => 'full_html',
    ]);
    $node_type->save();
    $node_body_field = node_add_body_field($node_type);
    $node_body_field->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $node_body_field->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $node_body_field->save();

    $block_type = BlockContentType::create([
      'id' => 'block',
      'label' => 'Basic block',
    ]);
    $block_type->save();
    $block_body_field = block_content_add_body_field($block_type->id());
    $block_body_field->setThirdPartySetting('linkchecker', 'scan', TRUE);
    $block_body_field->setThirdPartySetting('linkchecker', 'extractor', 'html_link_extractor');
    $block_body_field->save();

    // Configure basic settings.
    $this->config('linkchecker.settings')->set('default_url_scheme', 'http://')->save();
    $this->config('linkchecker.settings')->set('base_path', 'example.org/')->save();

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

    $permissions = [
      // Block permissions.
      'administer blocks',
      // Comment permissions.
      'administer comments',
      'access comments',
      'post comments',
      'skip comment approval',
      'edit own comments',
      // Node permissions.
      'create page content',
      'edit own page content',
      // Path aliase permissions.
      'administer url aliases',
      'create url aliases',
      // Content filter permissions.
      $full_html_format->getPermissionName(),
    ];

    // User to set up linkchecker.
    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test node with link.
   */
  public function testLinkCheckerCreateNodeWithBrokenLinks() {
    $url1 = 'http://example.com/node/broken/link';
    $body = 'Lorem ipsum dolor sit amet <a href="' . $url1 . '">broken link</a> sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat';

    // Save folder names in variables for reuse.
    $random = new Random();
    $folder1 = $random->name(10);
    $folder2 = $random->name(5);

    // Fill node array.
    $edit = [];
    $edit['title[0][value]'] = $random->name(32);
    $edit['body[0][value]'] = $body;
    //$edit["body[0][format]"] = 'full_html';
    $edit['path[0][alias]'] = '/' . $folder1 . '/' . $folder2;

    // Extract only full qualified URLs.
    $this->config('linkchecker.settings')->set('check_links_types', LinkCheckerLinkInterface::TYPE_EXTERNAL)->save();

    // Verify path input field appears on add "Basic page" form.
    $this->drupalGet('node/add/page');
    // Verify path input is present.
    $this->assertSession()->fieldExists('path[0][alias]');

    // Save node.
    $this->drupalGet('/node/add/page');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains($this->t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit["title[0][value]"]]));

    $node = $this->drupalGetNodeByTitle($edit['title[0][value]']);
    $this->assertNotEmpty($node);

    // Verify if the content link is extracted properly.
    $link = $this->getLinkCheckerLinkByUrl($url1);

    if ($link) {
      $this->assertSame($link->get('url')->value, $url1, new FormattableMarkup('URL %url found.', ['%url' => $url1]));
    }
    else {
      $this->fail(new FormattableMarkup('URL %url not found.', ['%url' => $url1]));
    }
  }

  /**
   * Test block with link.
   */
  public function testLinkCheckerCreateBlockWithBrokenLinks() {
    // Confirm that the add block link appears on block overview pages.
    $this->drupalGet(Url::fromRoute('entity.block_content.collection')->toString());

    $url1 = 'http://example.com/block/broken/link';
    $body = 'Lorem ipsum dolor sit amet <a href="' . $url1 . '">broken link</a> sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat';

    // Add a new custom block by filling out the input form on the
    // admin/structure/block/add page.
    $random = new Random();

    $custom_block = [
      'info[0][value]' => $random->name(8),
      'body[0][value]' => $body,
    ];
    $this->drupalGet(Url::fromRoute('block_content.add_page')->toString());
    $this->submitForm($custom_block, 'Save');
    // Confirm that the custom block has been created, and then query the
    // created bid.
    $this->assertSession()->pageTextContains($this->t('@type @title has been created.', ['@type' => 'Basic block', '@title' => $custom_block['info[0][value]']]));
    // Check that the block exists in the database.
    $blocks = \Drupal::entityQuery('block_content')->accessCheck()->condition('info', $custom_block['info[0][value]'])->execute();
    $block = BlockContent::load(reset($blocks));
    $this->assertNotEmpty($block);

    // Verify if the content link is extracted properly.
    $link = $this->getLinkCheckerLinkByUrl($url1);

    if ($link) {
      $this->assertSame($link->get('url')->value, $url1, new FormattableMarkup('URL %url found.', ['%url' => $url1]));
    }
    else {
      $this->fail(new FormattableMarkup('URL %url not found.', ['%url' => $url1]));
    }
   }

  /**
   * Get a link checker link entity by the given URl.
   *
   * @param string $url
   *   The url.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   A link checker link entity when found, else NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLinkCheckerLinkByUrl(string $url) {
    $links = \Drupal::entityTypeManager()
      ->getStorage('linkcheckerlink')
      ->loadByProperties([
        'urlhash' => LinkCheckerLink::generateHash($url),
      ]);
    return current($links);
  }

}

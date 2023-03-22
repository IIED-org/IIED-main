<?php

namespace Drupal\Tests\linkchecker\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\link\LinkItemInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Test html link extractor.
 *
 * @group linkchecker
 */
class LinkcheckerLinkLinkExtractorTest extends KernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'node',
    'filter',
    'system',
    'field',
    'text',
    'dynamic_entity_reference',
    'link',
    'linkchecker',
  ];

  /**
   * The Linkchecker settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $linkcheckerSetting;

  /**
   * Link link extractor.
   *
   * @var \Drupal\linkchecker\Plugin\LinkExtractor\LinkLinkExtractor
   */
  protected $linkLinkExtractor;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installSchema('system', 'sequences');
    $this->installSchema('linkchecker', 'linkchecker_index');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('linkcheckerlink');
    $this->installConfig(['field', 'node', 'filter', 'linkchecker']);

    $this->linkcheckerSetting = $this->container->get('config.factory')
      ->getEditable('linkchecker.settings');

    /** @var \Drupal\linkchecker\Plugin\LinkExtractorManager $extractorManager */
    $extractorManager = $this->container->get('plugin.manager.link_extractor');
    $this->linkLinkExtractor = $extractorManager->createInstance('link_link_extractor');
  }

  /**
   * Test Link field  extractor.
   */
  public function testLinkExtractor() {
    $type = NodeType::create(['name' => 'Links', 'type' => 'links']);
    $type->save();
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'entity_bundle' => 'links',
      'type' => 'link',
      'cardinality' => -1,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_test',
      'bundle' => 'links',
      'settings' => ['link_type' => LinkItemInterface::LINK_GENERIC],
    ])->save();

    $node = $this->createNode([
      'type' => 'links',
      'field_test' => [
        [
          $this->getTestLinks(),
        ],
      ],
    ]);

    $node->set('field_test', $this->getTestLinks());

    $htmlTagConfig = [
      'from_audio',
      'from_embed',
      'from_iframe',
      'from_img',
      'from_object',
      'from_video',
    ];

    // First disable extraction from each tag.
    foreach ($htmlTagConfig as $tagConfigName) {
      $this->linkcheckerSetting->set('extract.' . $tagConfigName, FALSE);
    }

    // Enable from_a for checking link field items.
    $this->linkcheckerSetting->set('extract.from_a', TRUE);
    $this->linkcheckerSetting->save(TRUE);

    $testCases = $this->getTestUrlList();

    $extractedUrls = [];
    $field_values = $node->get('field_test')->getValue();

    // Loop over field values extracting URLs from each.
    foreach ($field_values as $fv) {
      $extractedUrls = array_merge($extractedUrls, $this->linkLinkExtractor->extract([$fv]));
    }

    // Assert that each URL is found.
    foreach ($testCases as $url) {
      $this->assertContains($url, $extractedUrls, new FormattableMarkup('URL @url was not extracted from tag @tag!', [
        '@url' => $url,
        '@tag' => str_replace('from_', '', 'from_a'),
      ]));
    }

    // Assert that the number of extracted URLs matches the number of test
    // cases.
    $countTestCases = count($testCases);
    $countExtractedLinks = count($extractedUrls);
    $this->assertEquals($countTestCases, $countExtractedLinks, new FormattableMarkup('Expected to extract @count but get @actual links.', [
      '@count' => $countTestCases,
      '@actual' => $countExtractedLinks,
    ]));
  }

  /**
   * Get test link field values.
   *
   * @return array
   *   Link field values
   */
  protected function getTestLinks() {
    $links = $this->getTestUrlList();

    // Fill an array of link field values, where the URL and title are both
    // URLs.
    $values = [];
    foreach ($links as $link) {
      $values[] = [
        'uri' => $link,
        'title' => $link,
      ];
    }

    return $values;
  }

  /**
   * List of links to test.
   *
   * @return array
   *   Each element is a URI
   */
  protected function getTestUrlList() {
    return [
      'http://www.lagrandeepicerie.fr/#e-boutique/Les_produits_du_moment,2/coffret_vins_doux_naturels,149',
      'http://wetterservice.msn.de/phclip.swf?zip=60329&ort=Frankfurt',
      'http://www.msn.de/',
      'http://www.adobe.com/',
      'http://www.apple.com/qtactivex/qtplugin.cab',
      'http://example.net/video/foo1.mov',
      'http://example.net/video/foo2.mov',
      'http://example.net/video/foo3.mov',
      'http://example.org/video/foo1.mp4',
      'http://example.org/video/foo2.mp4',
      'http://example.org/video/foo3.mp4',
      'http://example.org/video/foo4.mp4',
      'http://example.org/video/foo5.mp4',
      'http://example.org/video/foo6.mp4',
      'http://example.org/video/player1.swf',
      'http://example.org/video/player2.swf',
      'http://example.org/video/player3.swf',
      'http://example.com/iframe/',
      'http://www.theora.org/cortado.jar',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.ogg',
      'http://v2v.cc/~j/theora_testsuite/pixel_aspect_ratio.mov',
      'http://v2v.cc/~j/theora_testsuite/320x240.ogg',
      'http://example.com/foo bar/is_valid-hack.test',
      'http://example.com/ajax.html#key1=value1&key2=value2',
      'http://example.com/test.html#test',
      'http://example.com/test.html#test%20ABC',
      'mailto:test@example.com',
      'javascript:foo()',
    ];
  }

}

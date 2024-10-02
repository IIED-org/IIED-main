<?php

use Behat\Testwork\Hook\Scope\AfterTestScope;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use Drupal\Core\Site\Settings;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\Entity\Node;
/**
 * Behat steps for testing the varnish_purger module.
 *
 * @codingStandardsIgnoreStart
 */
class VarnishPurgeFeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Setup for the test suite, enable some required modules and add content
   * title.
   *
   * @BeforeSuite
   */
  public static function prepare(BeforeSuiteScope $scope) {
    $data = Settings::getAll();

    // We need a concrete plugin class and one is included in the purge module.
    $data['extension_discovery_scan_tests'] = TRUE;

    $data['reverse_proxy_addresses'] = [
      '127.0.0.1',
    ];
    $data['varnish_purge_zeroconfig_port'] = 8080;
    new Settings($data);

    /** @var \Drupal\Core\Extension\ModuleHandler $moduleHandler */
    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('varnish_purger')) {
      \Drupal::service('module_installer')->install(['varnish_purger', 'varnish_purge_tags', 'purge_processor_test']);
    }

    // Also uninstall the inline form errors module for easier testing.
    if ($moduleHandler->moduleExists('inline_form_errors')) {
      \Drupal::service('module_installer')->uninstall(['inline_form_errors']);
    }

    // Set a 1 year expiry as recommended by the purge module.
    \Drupal::service('config.factory')->getEditable('system.performance')
      ->set('cache', [
        'page' => [
          'max_age' => 365 * 24 * 60 * 60,
        ],
      ])
      ->save();

    // Add the zeroconfig purger as the only purger.
    \Drupal::service('config.factory')->getEditable('purge.plugins')
      ->set('purgers', [
        [
          'order_index' =>  1,
          'instance_id' => '340fedee82',
          'plugin_id' => 'varnish_zeroconfig_purger',
        ],
      ])
      ->save();
  }

  /**
   * @param \Behat\Testwork\Hook\Scope\AfterSuiteScope $scope
   * @AfterScenario
   */
  public static function tearDown(AfterTestScope $scope) {
    $nodes = Node::loadMultiple();
    foreach ($nodes as $node) {
      $node->delete();
    }

    self::purgeEverything();
  }

  private static function purgeEverything() {
    $p = \Drupal::service('purge.purgers');
    // This dummy processor is literally called "a".
    $a = \Drupal::service('purge.processors')->get('a');
    $invalidations = [
      \Drupal::service('purge.invalidation.factory')
        ->get('everything', NULL)
    ];

    // Varnish does have a queue, so if we get random failures we may need a
    // sleep here.
    $p->invalidate($a, $invalidations);
  }

  /**
   * @When I purge nodes
   */
  public function iPurgeNodes() {
    $this->purgeTags(['node_list']);
  }

  /**
   * @When I purge nodes and media
   */
  public function iPurgeNodesAndMedia() {
    // Test we properly replace all spaces with pipes, by placing the node_list
    // tag at the end.
    $this->purgeTags(['user_list', 'media_list', 'node_list']);
  }

  /**
   * @When I purge everything
   */
  public function iPurgeEverything() {
    self::purgeEverything();
  }

  /**
   * @When I purge the home page
   */
  public function iPurgeTheHomepage() {
    $url = \Drupal::request()->getSchemeAndHttpHost() . '/';
    $this->purgeUrl($url);
  }

  /**
   * @When I purge no-star
   */
  public function iPurgeTheNodeList() {
    $url = \Drupal::request()->getSchemeAndHttpHost() . '/';
    static::purgeWildcardUrl($url . 'no.*');
  }

  private static function purgeTags(array $tags) {
    $p = \Drupal::service('purge.purgers');
    // This dummy processor is literally called "a".
    $a = \Drupal::service('purge.processors')->get('a');

    $invalidations = [];
    foreach ($tags as $tag) {
      $invalidations[] = \Drupal::service('purge.invalidation.factory')
          ->get('tag', $tag);
    }

    // Varnish does have a queue, so if we get random failures we may need a
    // sleep here.
    $p->invalidate($a, $invalidations);
  }

  /**
   * @param string $url
   */
  private static function purgeWildcardUrl(string $url) {
    $p = \Drupal::service('purge.purgers');
    // This dummy processor is literally called "a".
    $a = \Drupal::service('purge.processors')->get('a');
    $invalidations = [
      \Drupal::service('purge.invalidation.factory')
        ->get('wildcardurl', $url)
    ];

    // Varnish does have a queue, so if we get random failures we may need a
    // sleep here.
    $p->invalidate($a, $invalidations);
  }

  /**
   * @param string $url
   */
  private static function purgeUrl(string $url) {
    $p = \Drupal::service('purge.purgers');
    // This dummy processor is literally called "a".
    $a = \Drupal::service('purge.processors')->get('a');
    $invalidations = [
      \Drupal::service('purge.invalidation.factory')
        ->get('url', $url)
    ];

    // Varnish does have a queue, so if we get random failures we may need a
    // sleep here.
    $p->invalidate($a, $invalidations);
  }

}

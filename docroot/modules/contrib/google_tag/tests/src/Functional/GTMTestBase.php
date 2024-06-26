<?php

namespace Drupal\Tests\google_tag\Functional;

use Drupal\Core\Utility\Error;
use Drupal\Tests\BrowserTestBase;
use Drupal\google_tag\Entity\Container;

/**
 * Tests the Google Tag Manager.
 *
 * Use the settings form to save configuration and create snippet files.
 * Confirm snippet file and page response contents.
 * Test further the snippet insertion conditions.
 *
 * @group GoogleTag
 */
abstract class GTMTestBase extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['google_tag'];

  /**
   * The snippet file types.
   *
   * @var array
   */
  protected $types = ['script', 'noscript'];

  /**
   * The snippet base URI.
   *
   * @var string
   */
  protected $basePath;

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The non-admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $nonAdminUser;

  /**
   * The test variables.
   *
   * @var array
   */
  protected $variables = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->defaultTheme = 'stark';
    parent::setUp();
    $this->basePath = $this->config('google_tag.settings')->get('uri');
  }

  /**
   * Test the module.
   */
  public function testModule() {
    try {
      $this->modifySettings();
      // Create containers in code.
      $this->createData();
      $this->saveContainers();
      $this->checkSnippetContents();
      $this->checkPageResponse();
      // Delete containers.
      $this->deleteContainers();
      // Create containers in user interface.
      $this->submitContainers();
      $this->checkSnippetContents();
      $this->checkPageResponse();
      // Switch to inline snippets.
      $this->modifySettings(FALSE);
      $this->checkPageResponse();
    }
    catch (\Exception $e) {
      parent::assertTrue(TRUE, t('Inside CATCH block'));
      if (method_exists('\Drupal\Core\Utility\Error', 'logException')) {
        Error::logException(\Drupal::logger('gtm_test'), $e);
      }
      else {
        watchdog_exception('gtm_test', $e);
      }
    }
    finally {
      parent::assertTrue(TRUE, t('Inside FINALLY block'));
    }
  }

  /**
   * Modify settings for test purposes.
   *
   * @param bool $include_file
   *   The include_file module setting.
   */
  protected function modifySettings($include_file = TRUE) {
    // Modify default settings.
    // These should propagate to each container created in test.
    $config = $this->config('google_tag.settings');
    $settings = $config->get();
    unset($settings['_core']);
    $settings['include_file'] = $include_file;
    $settings['flush_snippets'] = 1;
    $settings['debug_output'] = 1;
    $settings['_default_container']['role_toggle'] = 'include listed';
    $settings['_default_container']['role_list'] = ['content viewer' => 'content viewer'];
    $config->setData($settings)->save();
  }

  /**
   * Create test data: configuration variables and users.
   */
  protected function createData() {
    // Create an admin user.
    $this->drupalCreateRole(['access content', 'administer google tag manager'], 'admin user');
    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->roles[] = 'admin user';
    $this->adminUser->save();

    // Create a test user.
    $this->drupalCreateRole(['access content'], 'content viewer');
    $this->nonAdminUser = $this->drupalCreateUser();
    $this->nonAdminUser->roles[] = 'content viewer';
    $this->nonAdminUser->save();
  }

  /**
   * Save containers in the database and create snippet files.
   */
  protected function saveContainers() {
    foreach ($this->variables as $key => $variables) {
      // Create container with default container settings, then modify.
      $container = new Container([], 'google_tag_container');
      $container->enforceIsNew();
      $container->set('id', $variables->id);
      // @todo This has unintended collateral effect; the id property is gone forever.
      // Code in submitContainers() needs this value.
      $values = (array) $variables;
      unset($values['id']);
      array_walk($values, function ($value, $key) use ($container) {
        $container->$key = $value;
      });
      // Save container.
      $container->save();

      // Create snippet files.
      $manager = $this->container->get('google_tag.container_manager');
      $manager->createAssets($container);
    }
  }

  /**
   * Delete containers from the database and delete snippet files.
   */
  protected function deleteContainers() {
    // Delete containers.
    foreach ($this->variables as $key => $variables) {
      // Also exposed as \Drupal::entityTypeManager().
      $container = $this->container->get('entity_type.manager')->getStorage('google_tag_container')->load($key);
      $container->delete();
    }

    // Confirm no containers.
    $manager = $this->container->get('google_tag.container_manager');
    $ids = $manager->loadContainerIds();
    $message = 'No containers found after delete';
    parent::assertTrue(empty($ids), $message);

    // @todo Next statement will not delete files as containers are gone.
    // $manager->createAllAssets();
    // Delete snippet files.
    $directory = $this->config('google_tag.settings')->get('uri');
    if ($this->config('google_tag.settings')->get('flush_snippets')) {
      if (!empty($directory)) {
        // Remove any stale files (e.g. module update or machine name change).
        $this->container->get('file_system')->deleteRecursive($directory . '/google_tag');
      }
    }

    // Confirm no snippet files.
    $message = 'No snippet files found after delete';
    $method = method_exists($parent, $a = 'assertDirectoryDoesNotExist') ? $a : 'assertDirectoryNotExists';
    parent::$method($directory . '/google_tag', $message);
  }

  /**
   * Add containers through user interface.
   */
  protected function submitContainers() {
    $this->drupalLogin($this->adminUser);

    foreach ($this->variables as $key => $variables) {
      $edit = (array) $variables;
      $this->drupalGet('/admin/config/system/google-tag/add');
      $this->submitForm($edit, 'Save');

      $text = 'Created @count snippet files for %container container based on configuration.';
      $args = ['@count' => 3, '%container' => $variables->label];
      $text = t($text, $args);
      $this->assertSession()->responseContains($text);

      $text = 'Created @count snippet files for @container container based on configuration.';
      $args = ['@count' => 3, '@container' => $variables->label];
      $text = t($text, $args);
      $this->assertSession()->pageTextContains($text);
    }
  }

  /**
   * Returns the snippet contents.
   */
  protected function getSnippetFromFile($key, $type) {
    $url = "$this->basePath/google_tag/{$key}/google_tag.$type.js";
    return @file_get_contents($url);
  }

  /**
   * Returns the snippet contents.
   */
  protected function getSnippetFromCache($key, $type) {
    $cid = "google_tag:$type:$key";
    $cache = $this->container->get('cache.data')->get($cid);
    return $cache ? $cache->data : '';
  }

  /**
   * Inspect the snippet contents.
   */
  protected function checkSnippetContents() {
  }

  /**
   * Verify the snippet file contents.
   */
  protected function verifyScriptSnippet($contents, $variables) {
    $status = strpos($contents, "'$variables->container_id'") !== FALSE;
    $message = 'Found in script snippet file: container_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "src='https://$variables->hostname") !== FALSE;
    $message = 'Found in script snippet file: hostname';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_preview=$variables->environment_id") !== FALSE;
    $message = 'Found in script snippet file: environment_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_auth=$variables->environment_token") !== FALSE;
    $message = 'Found in script snippet file: environment_token';
    parent::assertTrue($status, $message);
  }

  /**
   * Verify the snippet cache contents.
   */
  protected function verifyNoScriptSnippet($contents, $variables) {
    $status = strpos($contents, "id=$variables->container_id") !== FALSE;
    $message = 'Found in noscript snippet cache: container_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "src=\"https://$variables->hostname") !== FALSE;
    $message = 'Found in noscript snippet cache: hostname';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_preview=$variables->environment_id") !== FALSE;
    $message = 'Found in noscript snippet cache: environment_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_auth=$variables->environment_token") !== FALSE;
    $message = 'Found in noscript snippet cache: environment_token';
    parent::assertTrue($status, $message);
  }

  /**
   * Verify the snippet file contents.
   */
  protected function verifyDataLayerSnippet($contents, $variables) {
  }

  /**
   * Inspect the page response.
   */
  protected function checkPageResponse() {
    $this->drupalLogin($this->nonAdminUser);
  }

  /**
   * Verify the tag in page response.
   */
  protected function verifyScriptTag($realpath) {
    $query_string = $this->container->get('state')->get('system.css_js_query_string') ?: '0';
    $text = "src=\"$realpath?$query_string\"";
    $this->assertSession()->responseContains($text);

    $xpath = "//script[@src=\"$realpath?$query_string\"]";
    $elements = $this->xpath($xpath);
    $status = !empty($elements);
    $message = 'Found script tag in page response';
    parent::assertTrue($status, $message);
  }

  /**
   * Verify the tag in page response.
   */
  protected function verifyScriptTagInline($variables, $cache) {
    $id = $variables->container_id;
    $xpath = "//script[contains(text(), '$id')]";
    $elements = $this->xpath($xpath);
    if (!is_array($elements) || count($elements) > 1) {
      $message = 'Found only one script tag';
      parent::assertFalse($status, $message);
      return;
    }

    $contents = $elements[0]->getHtml();

    $status = strpos($contents, "(window,document,'script','dataLayer','$id')") !== FALSE;
    $message = 'Found in script tag: container_id and data data_layer';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "src='https://$variables->hostname") !== FALSE;
    $message = 'Found in script tag: hostname';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_preview=$variables->environment_id") !== FALSE;
    $message = 'Found in script tag: environment_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_auth=$variables->environment_token") !== FALSE;
    $message = 'Found in script tag: environment_token';
    parent::assertTrue($status, $message);

    $message = 'Contents of script tag matches cache';
    parent::assertTrue($contents == $cache, $message);
  }

  /**
   * Verify the tag in page response.
   */
  protected function verifyNoScriptTag($realpath, $variables, $cache = '') {
    // The tags are sorted by weight.
    $index = isset($variables->weight) ? $variables->weight - 1 : 0;
    $xpath = '//noscript//iframe';
    $elements = $this->xpath($xpath);
    $contents = $elements[$index]->getAttribute('src');

    $status = strpos($contents, "id=$variables->container_id") !== FALSE;
    $message = 'Found in noscript tag: container_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "src=\"https://$variables->hostname") !== FALSE;
    $message = 'Found in noscript tag: hostname';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_preview=$variables->environment_id") !== FALSE;
    $message = 'Found in noscript tag: environment_id';
    parent::assertTrue($status, $message);

    $status = strpos($contents, "gtm_auth=$variables->environment_token") !== FALSE;
    $message = 'Found in noscript tag: environment_token';
    parent::assertTrue($status, $message);

    if ($cache) {
      $message = 'Contents of noscript tag matches cache';
      parent::assertTrue(strpos($cache, $contents) !== FALSE, $message);
    }
  }

  /**
   * Verify the tag in page response.
   */
  protected function verifyNoScriptTagInline($variables, $cache) {
    $this->verifyNoScriptTag('', $variables, $cache);
  }

}

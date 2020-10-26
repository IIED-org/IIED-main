<?php

namespace Drupal\path_redirect_import\Tests\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Test that redirects are properly imported from CSV file.
 *
 * @group path_redirect_import
 */
class RedirectImportTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'file',
    'redirect',
    'path_redirect_import',
    'language',
  ];

  /**
   * A user with permission to administer nodes.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * An CSV file path for uploading.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $csv;

  /**
   * An array of content for testing purposes.
   *
   * @var string[]
   */
  protected $testdata = [
    'First Page' => 'Page 1',
    'Second Page' => 'Page 2',
    'Third Page' => 'Page 3',
  ];

  /**
   * An array of nodes created for testing purposes.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected $nodes;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->testUser = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'access site reports',
      'administer languages',
      'access administration pages',
      'administer site configuration',
      'administer redirects',
    ]);
    $this->drupalLogin($this->testUser);

    // Add a new language.
    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Make the body field translatable. The title is already translatable by
    // definition.
    $field_storage = FieldStorageConfig::loadByName('node', 'body');
    $field_storage->setTranslatable(TRUE);
    $field_storage->save();

    // Create EN language nodes.
    foreach ($this->testdata as $title => $body) {
      $info = [
        'title' => $title . ' (EN)',
        'body' => [['value' => $body]],
        'type' => 'page',
        'langcode' => 'en',
      ];
      $this->nodes[$title] = $this->drupalCreateNode($info);
    }

    // Create non-EN nodes.
    foreach ($this->testdata as $title => $body) {
      $info = [
        'title' => $title . ' (FR)',
        'body' => [['value' => $body]],
        'type' => 'page',
        'langcode' => 'fr',
      ];
      $this->nodes[$title] = $this->drupalCreateNode($info);
    }

    // Create language-unspecified nodes.
    foreach ($this->testdata as $title => $body) {
      $info = [
        'title' => $title . ' (UND)',
        'body' => [['value' => $body]],
        'type' => 'page',
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ];
      $this->nodes[$title] = $this->drupalCreateNode($info);
    }

  }

  /**
   * Test that various rows in a CSV are imported/ignored as expected.
   */
  public function testRedirectImport() {

    // Copy other test files from simpletest.
    $csv = drupal_get_path('module', 'path_redirect_import') . '/src/Tests/files/' . 'test-redirects.csv';
    $edit = [
      'override' => TRUE,
      'files[csv_file]' => \Drupal::service('file_system')->realpath($csv),
    ];

    $form_path = 'admin/config/search/redirect/import';
    $this->drupalGet($form_path);
    $this->drupalPostForm(NULL, $edit, t('Import'));

    // Assertions.
    $this->assertText('Added redirect from hello-world to node/2', new FormattableMarkup('Add redirect from arbitrary alias without leading slash to existing path', []));
    $this->assertText('Added redirect from with-query?query=alt to node/1', new FormattableMarkup('Add redirect from arbitrary alias with query to existing path', []));
    $this->assertText('Added redirect from forward to node/2', new FormattableMarkup('Add redirect from arbitrary alias with leading slash to existing path', []));
    $this->assertText('Added redirect from test/hello to http://corporaproject.org', new FormattableMarkup('Add redirect to external URL', []));
    $this->assertText('Line 13 contains invalid data; bypassed.', new FormattableMarkup('Bypass row with missing redirect', []));
    $this->assertText('Line 14 contains invalid status code; bypassed.', new FormattableMarkup('Bypass row with invalid status code', []));
    $this->assertText('You cannot create a redirect from the front page.', new FormattableMarkup('Bypass redirect from &lt;front&gt;.', []));
    $this->assertText('You are attempting to redirect "node/2" to itself. Bypassed, as this will result in an infinite loop.', new FormattableMarkup('Bypass infinite loops.', []));
    $this->assertText('The destination path "node/99997" does not exist on the site. Redirect from "blah12345" bypassed.', new FormattableMarkup('Bypass redirects to nonexistent internal paths.', []));
    $this->assertText('The destination path "fellowship" does not exist on the site. Redirect from "node/2" bypassed.', new FormattableMarkup('Bypass redirects to nonexistent URL aliases.', []));
    $this->assertText('Redirects from anchor fragments (i.e., with "#) are not allowed. Bypassing "redirect-with-anchor#anchor".', new FormattableMarkup('Bypass redirects from anchor fragments', []));
  }

}

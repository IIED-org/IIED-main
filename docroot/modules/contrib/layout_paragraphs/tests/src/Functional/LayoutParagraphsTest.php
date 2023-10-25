<?php

namespace Drupal\Tests\layout_paragraphs\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests adding a new layout section to layout paragraphs.
 *
 * @group layout_paragraphs
 */
class LayoutParagraphsTest extends BrowserTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_paragraphs',
    'paragraphs',
    'node',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
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
    $this->addParagraphsType('section');
    $this->addParagraphsType('text');
    $this->addParagraphedContentType('page', 'field_content', 'layout_paragraphs');
    $this->loginWithPermissions([
      'administer site configuration',
      'administer node fields',
      'administer paragraphs types',
    ]);

    // Enable Layout Paragraphs behavior for section paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type/section');
    $this->submitForm([
      'behavior_plugins[layout_paragraphs][enabled]' => TRUE,
      'behavior_plugins[layout_paragraphs][settings][available_layouts][]' => [
        'layout_onecol',
        'layout_twocol',
        'layout_threecol_25_50_25',
        'layout_threecol_33_34_33',
      ],
    ], 'Save');
    $this->assertSession()->pageTextContains('Saved the section Paragraphs type.');

    // Add "section" and "text" paragraph types to the "page" content type.
    $this->drupalGet('admin/structure/types/manage/page/fields/node.page.field_content');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([
      'settings[handler_settings][target_bundles_drag_drop][section][enabled]' => TRUE,
      'settings[handler_settings][target_bundles_drag_drop][text][enabled]' => TRUE,
    ], 'Save settings', 'field-config-edit-form');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalLogout();
  }

  /**
   * Tests configuring a new layout paragraphs field.
   */
  public function testLayoutParagraphsConfiguration() {

    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
    ]);
    $this->drupalGet('node/add/page');
    $this->assertSession()->pageTextContains('field_content');
  }

  /**
   * Tests library alteration.
   */
  public function testLibraries() {
    $this->loginWithPermissions([
      'create page content',
    ]);
    $this->drupalGet('node/add/page');
    $this->assertSession()->responseContains('https://cdnjs.cloudflare.com/ajax/libs/dragula/');
    $this->assertSession()->responseNotContains('/libraries/dragula/');

    $this->container->get('module_installer')->install([
      'test_layout_paragraphs_libraries',
    ]);
    drupal_flush_all_caches();

    $this->drupalGet('node/add/page');
    $this->assertSession()->responseContains('/libraries/dragula/');
    $this->assertSession()->responseNotContains('https://cdnjs.cloudflare.com/ajax/libs/dragula/');
  }

  /**
   * Creates a new user with provided permissions and logs them in.
   *
   * @param array $permissions
   *   An array of permissions.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The user.
   */
  protected function loginWithPermissions(array $permissions) {
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);
    return $user;
  }

}

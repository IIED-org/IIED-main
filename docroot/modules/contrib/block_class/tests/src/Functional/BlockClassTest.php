<?php

namespace Drupal\Tests\block_class\Functional;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore mainpagecontent, useraccountmenu
// Ignore CSS class names used in the tests.
/**
 * Tests the custom CSS classes for blocks.
 *
 * @group block_class
 */
class BlockClassTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_class'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * Tests the custom CSS classes for main and user menu blocks.
   */
  public function testBlockClass() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Define classes used for testing.
    $test_classes_block_main = 'TestClass_content1 TestClass_content2 TestClass_content3';
    $test_classes_block_user_menu = 'TestClass_menu1 TestClass_menu2 TestClass_menu3';

    $admin_user = $this->drupalCreateUser([
      BlockClassConstants::BLOCK_CLASS_PERMISSION,
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);

    // Add a main content block with custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_main_block/' . $this->defaultTheme, ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'content',
      'third_party_settings[block_class][classes]' => $test_classes_block_main,
    ];
    $this->submitForm($edit, 'Save block');

    // Add a user account menu with a custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_menu_block:account/' . $this->defaultTheme, ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'secondary_menu',
      'third_party_settings[block_class][classes]' => $test_classes_block_user_menu,
    ];
    $this->submitForm($edit, 'Save block');

    // Go to the front page of the user.
    $this->drupalGet('<front>');
    // Assert the custom class in the content block.
    if (version_compare(\Drupal::VERSION, '10', '<')) {
      // Support tests for D9.
      $assert->responseContains('<div id="block-mainpagecontent" class="' . $test_classes_block_main . ' block block-system block-system-main-block">');
      // Assert the custom class in user menu.
      $assert->responseContains('<nav  id="block-useraccountmenu" class="' . $test_classes_block_user_menu . ' block block-menu navigation menu--account secondary-nav" aria-labelledby="block-useraccountmenu-menu" role="navigation">');
    }
    else {
      $assert->responseContains('<div id="block-' . $this->defaultTheme . '-mainpagecontent" class="' . $test_classes_block_main . ' block block-system block-system-main-block">');
      // Assert the custom class in user menu.
      $assert->responseContains('<nav  id="block-' . $this->defaultTheme . '-useraccountmenu" class="' . $test_classes_block_user_menu . ' block block-menu navigation menu--account secondary-nav" aria-labelledby="block-' . $this->defaultTheme . '-useraccountmenu-menu" role="navigation">');
    }
  }

}

<?php

namespace Drupal\Tests\video_embed_field\Functional;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Tests\BrowserTestBase;

/**
 * Integration test for the field configuration form.
 *
 * @group video_embed_field
 */
class FieldConfigurationTest extends BrowserTestBase {

  use EntityDisplaySetupTrait;
  use AdminUserTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'node',
    'video_embed_field',
  ];

  /**
   * Test the field configuration form.
   */
  public function testFieldConfiguration() {
    $this->drupalLogin($this->createAdminUser());
    $this->createContentType(['type' => 'page', 'name' => 'Page']);
    drupal_flush_all_caches();
    $this->drupalGet('admin/structure/types/manage/page/fields/add-field');
    $selected_group = [
      'new_storage_type' => 'video_embed_field',
    ];
    $submit = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '10.3', fn() => "Continue", fn() => "Change field group");
    $this->submitForm($selected_group, $submit);
    $edit = [
      'label' => 'Video Embed',
      'field_name' => 'video_embed',
    ];
    $this->submitForm($edit, 'Continue');
    $page = $this->getSession()->getPage();
    $page->fillField('Required field', TRUE);
    $page->fillField('Vimeo', TRUE);
    $page->fillField('YouTube', TRUE);
    $page->fillField('YouTube Playlist', TRUE);
    $page->fillField('Set default value', TRUE);
    $page->fillField('Video Embed', 'http://example.com');
    $page->pressButton('Save settings');
    $this->assertSession()->pageTextContains('Could not find a video provider to handle the given URL.');
    $page->fillField('Video Embed', 'https://www.youtube.com/watch?v=XgYu7-DQjDQ');
    $page->pressButton('Save settings');
    $this->assertSession()->pageTextContains('Saved Video Embed configuration.');
  }

}

<?php

namespace Drupal\Tests\search_api_sorts\Functional;

use Drupal\Core\Url;

/**
 * Tests the default functionality of Search API sorts.
 *
 * @group search_api_sorts
 */
class IntegrationTest extends SortsFunctionalBase {

  /**
   * Tests sorting.
   */
  public function testFramework() {
    $this->drupalLogin($this->adminUser);

    // Add sorting on ID.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts');
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);
    $this->submitForm([
      'sorts[id][status]' => TRUE,
    ], 'Save settings');

    // Check for non-existence of the block first.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->linkNotExists('ID');

    $block_settings = [
      'region' => 'footer',
      'id' => 'sorts_id',
    ];
    $this->drupalPlaceBlock('search_api_sorts_block:' . $this->displayId, $block_settings);

    // Make sure the block is available and the ID link is shown, check that the
    // sorting applied is in alphabetical order.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('ID');
    $this->assertPositions([
      'default | foo bar baz foobaz föö',
      'default | foo test foobuz',
      'default | foo baz',
      'default | bar baz',
    ]);

    // Click on the link and assert that the url now has changed, also check
    // that the sort order is still the same.
    $this->clickLink('ID');
    $this->assertSession()->statusCodeEquals(200);
    $url = Url::fromUserInput('/search-api-sorts-test', ['query' => ['sort' => 'id', 'order' => 'asc']]);
    $this->assertSession()->addressEquals($url);
    $this->assertPositions([
      'default | foo bar baz foobaz föö',
      'default | foo test foobuz',
      'default | foo baz',
      'default | bar baz',
    ]);

    // Click on the link again and assert that the url is now changed again and
    // that the sort order now also has changed.
    $this->clickLink('ID');
    $this->assertSession()->statusCodeEquals(200);
    $url = Url::fromUserInput('/search-api-sorts-test', ['query' => ['sort' => 'id', 'order' => 'desc']]);
    $this->assertSession()->addressEquals($url);
    $this->assertPositions([
      'default | bar baz',
      'default | foo baz',
      'default | foo test foobuz',
      'default | foo bar baz foobaz föö',
    ]);

    // Add sorting on type.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);
    $this->submitForm([
      'sorts[id][status]' => TRUE,
      'sorts[search_api_relevance][status]' => TRUE,
      'sorts[type][status]' => TRUE,
    ], 'Save settings');

    // Make sure the ID and type link are available.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('ID');
    $this->assertSession()->linkExists('Type');

    // Remove the type field from the index.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/fields');
    $this->getSession()->getPage()->find('css', '#edit-fields-type-remove')->click();
    $this->submitForm([], 'Save changes');

    // The type field was removed from the index. Make sure the type field is
    // also removed from the sorts block.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->linkExists('ID');
    $this->assertSession()->linkNotExists('Type');

    // Make sure that the relevance field is not removed. Since this field is
    // hardcoded it's not present in the index so there should be an extra
    // check that this field is not removed when a search_api_index is updated.
    $this->assertSession()->linkExists('Relevance');

    // Make sure the edit link of the search_api_sorts_field redirects to the
    // manage sorts form.
    $this->drupalGet('admin/config/search/search-api/sorts/' . $this->escapedDisplayId . '_id');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);
  }

  /**
   * Tests that only enabled configs are saved.
   */
  public function testSavedConfigs() {
    $this->drupalLogin($this->adminUser);

    // Add sorting on ID, Authored on and Type.
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);
    $this->submitForm([
      'sorts[id][status]' => TRUE,
      'sorts[created][status]' => TRUE,
      'sorts[type][status]' => TRUE,
      'default_sort' => 'id',
    ], 'Save settings');

    $configs_to_be_saved = ['id', 'created', 'type'];
    $configs_not_to_be_saved = ['search_api_relevance',
      'keywords',
      'category',
      'width',
    ];

    // Check if the default_sort radio button is checked.
    $page = $this->getSession()->getPage();
    $id_default_sort_checkbox = $page->find('css', '#edit-sorts .form-item-default-sort input[value="id"]');
    $this->assertEquals(TRUE, $id_default_sort_checkbox->isChecked());

    // Assert that only enabled sorts are saved in the database.
    foreach ($configs_to_be_saved as $config_id) {
      $this->assertNotEmpty($this->container->get('entity_type.manager')->getStorage('search_api_sorts_field')
        ->load($this->escapedDisplayId . '_' . $config_id), t("Config @config_name was not saved as expected", ['@config_name' => $config_id]));
    }
    foreach ($configs_not_to_be_saved as $config_id) {
      $this->assertEmpty($this->container->get('entity_type.manager')->getStorage('search_api_sorts_field')
        ->load($this->escapedDisplayId . '_' . $config_id), t("Config @config_name that should not have been saved was saved unexpectedly", ['@config_name' => $config_id]));
    }
  }

}

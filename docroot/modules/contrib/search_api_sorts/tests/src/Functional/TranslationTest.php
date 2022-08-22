<?php

namespace Drupal\Tests\search_api_sorts\Functional;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests Search API sorts translation.
 *
 * @group search_api_sorts
 */
class TranslationTest extends SortsFunctionalBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_translation', 'language'];

  /**
   * {@inheritdoc}
   */
  protected $adminUserPermissions = [
    'administer search_api',
    'access administration pages',
    'translate configuration',
  ];

  /**
   * The search api sorts field storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $searchApiSortsFieldStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create FR language.
    $entity_type = $this->container->get('entity_type.manager')->getDefinition('configurable_language');
    $this->container->get('entity_type.listener')->onEntityTypeCreate($entity_type);
    ConfigurableLanguage::createFromLangcode('fr')->save();
    // Enable language negotiation using path prefixes.
    $this->config('language.negotiation')
      ->set('url.source', 'path_prefix')
      ->set('url.prefixes', ['en' => 'en', 'fr' => 'fr'])
      ->save();

    $block_settings = [
      'region' => 'footer',
      'id' => 'sorts_id',
    ];
    $this->drupalPlaceBlock('search_api_sorts_block:' . $this->displayId, $block_settings);

    $this->searchApiSortsFieldStorage = $this->container->get('entity_type.manager')->getStorage('search_api_sorts_field');
  }

  /**
   * Test search_api_sorts with one language enabled.
   */
  public function testSingleLanguage() {
    // Remove the FR language so we can test the search api sorts admin screens
    // without multiple languages enabled.
    $language = ConfigurableLanguage::load('fr');
    $language->delete();

    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);

    // Check if the translation warning is not shown.
    $this->assertSession()->pageTextNotContains('You are currently editing the English version of the search api sorts fields.');

    // Check if the translate column is not present.
    $this->assertSession()->elementNotContains('css', 'table#edit-sorts thead th:last-child', 'Translate');

    // Check if translate link is not present.
    $this->assertSession()->linkByHrefNotExists(sprintf('admin/config/search/search-api/sorts/%s/translate', $this->escapedDisplayId . '_' . 'id'));

    $this->submitForm([
      'sorts[id][status]' => TRUE,
      'default_sort' => 'id',
    ], 'Save settings');

    // Check if the config is saved in the default language.
    $search_api_sorts_field = $this->searchApiSortsFieldStorage->load($this->escapedDisplayId . '_' . 'id');
    $this->assertEquals('en', $search_api_sorts_field->language()->getId());

    // Check if translate link is still not present.
    $this->assertSession()->linkByHrefNotExists(sprintf('admin/config/search/search-api/sorts/%s/translate', $this->escapedDisplayId . '_' . 'id'));

    // Visit the EN version of the search_api overview and check if the labels
    // are shown in the default language.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->linkNotExists('Identifiant');
    $this->assertSession()->linkExists('ID');
  }

  /**
   * Test search_api_sorts with multiple languagesenabled.
   */
  public function testMultipleLanguages() {
    $this->drupalGet('admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);

    // Check if the translate warning is not shown.
    $this->assertSession()->pageTextNotContains('You are currently editing the English version of the search api sorts fields.');

    // Check if the translate column is present.
    $this->assertSession()->elementContains('css', 'table#edit-sorts thead th:last-child', 'Translate');

    // Check if translate link is not present.
    $this->assertSession()->linkByHrefNotExists(sprintf('admin/config/search/search-api/sorts/%s/translate', $this->escapedDisplayId . '_' . 'id'));

    $this->submitForm([
      'sorts[id][status]' => TRUE,
      'default_sort' => 'id',
    ], 'Save settings');

    // Check if config is saved in the default language (EN).
    $search_api_sorts_field = $this->searchApiSortsFieldStorage->load($this->escapedDisplayId . '_' . 'id');
    $this->assertEquals('en', $search_api_sorts_field->language()->getId());

    // Check if translate link is present.
    $this->assertSession()->linkByHrefExists(sprintf('admin/config/search/search-api/sorts/%s/translate', $this->escapedDisplayId . '_' . 'id'));

    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->linkNotExists('Identifiant');
    $this->assertSession()->linkExists('ID');

    // Switch to FR version.
    $this->drupalGet('fr/admin/config/search/search-api/index/' . $this->indexId . '/sorts/' . $this->escapedDisplayId);

    // Check if the translate warning is shown.
    $this->assertSession()->pageTextContains('You are currently editing the English version of the search api sorts fields.');

    // Check if the translate column is present.
    $this->assertSession()->elementContains('css', 'table#edit-sorts thead th:last-child', 'Translate');

    $this->submitForm([
      'sorts[created][status]' => TRUE,
    ], 'Save settings');

    // Check if ID field config is still saved in the default language.
    $search_api_sorts_field = $this->searchApiSortsFieldStorage->load($this->escapedDisplayId . '_' . 'id');
    $this->assertEquals('en', $search_api_sorts_field->language()->getId());

    // Check if created config is also saved in the default language.
    $search_api_sorts_field = $this->searchApiSortsFieldStorage->load($this->escapedDisplayId . '_' . 'created');
    $this->assertEquals('en', $search_api_sorts_field->language()->getId());

    // Translate the ID field.
    $this->drupalGet('admin/config/search/search-api/sorts/' . $this->escapedDisplayId . '_' . 'id' . '/translate/fr/add');
    $this->submitForm([
      'translation[config_names][search_api_sorts.search_api_sorts_field.' . $this->escapedDisplayId . '_' . 'id' . '][label]' => 'Identifiant',
    ], 'Save translation');

    // Visit the EN version of the search_api overview and check if the labels
    // are shown in the default language.
    $this->drupalGet('search-api-sorts-test');
    $this->assertSession()->linkNotExists('Identifiant');
    $this->assertSession()->linkExists('ID');
    $this->assertSession()->linkExists('Authored on');

    // Visit the EN version of the search_api overview and check if the labels
    // are shown in the translated language.
    $this->drupalGet('fr/search-api-sorts-test');
    $this->assertSession()->linkExists('Identifiant');
    $this->assertSession()->linkNotExists('ID');
    $this->assertSession()->linkExists('Authored on');
  }

}

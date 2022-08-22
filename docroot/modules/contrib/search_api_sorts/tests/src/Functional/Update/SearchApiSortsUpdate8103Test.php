<?php

namespace Drupal\Tests\search_api_sorts\Functional\Update;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\search_api_sorts\Entity\SearchApiSortsField;

/**
 * Tests the Search api sorts upgrade path for update 8103.
 *
 * @group search_api_sorts
 */
class SearchApiSortsUpdate8103Test extends SearchApiSortsUpdateBase {

  /**
   * {@inheritdoc}
   */
  public static $entityTypes = [
    'configurable_language',
    'language_content_settings',
  ];

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    parent::setDatabaseDumpFiles();
    $this->databaseDumpFiles[] = __DIR__ . '/../../../fixtures/update/search-api-sorts-test-update-8103.php';
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->languageManager = $this->container->get('language_manager');

    ConfigurableLanguage::createFromLangcode('fr')->save();

    // Create a search_api_sorts_field in the default language without
    // translations.
    $this->createSearchApiSortsField('type');

    // Create a search_api_sorts_field in a different language than the
    // default one.
    $this->createSearchApiSortsField('id', [
      'label' => 'Identifiant',
      'langcode' => 'fr',
    ]);

    // Create a search_api_sorts_field in the default language with a
    // translation.
    $this->createSearchApiSortsField('created');
    $field_config = $this->languageManager->getLanguageConfigOverride('fr', 'search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_created');
    $field_config->set('label', 'Créé sur')->save();

    // Create a search_api_sorts_field in a different language with a
    // translation in the default language.
    $this->createSearchApiSortsField('title', [
      'label' => 'Titre',
      'langcode' => 'fr',
    ]);
    $field_config = $this->languageManager->getLanguageConfigOverride('en', 'search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_title');
    $field_config->set('label', 'Title')->save();
  }

  /**
   * Tests that search_api_sorts_field translations are correctly updated.
   *
   * @see search_api_sorts_update_8103()
   */
  public function testUpdate8103() {
    $this->runUpdates();

    // Check the search_api_sorts_field in the default language without
    // translation.
    // Expected output: config exists in the default language with no
    // translation.
    $config = $this->config('search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_type');
    $this->assertEquals('type', $config->get('label'));
    $this->assertEquals('en', $config->get('langcode'));

    $fr_config = $this->languageManager->getLanguageConfigOverride('fr', 'search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_type');
    $this->assertTrue($fr_config->isNew());

    // Check the search_api_sorts_field in a different language without
    // translation.
    // Expected output: Label is replaced to the default language and
    // label is also added as translation.
    $config = $this->config('search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_id');
    $this->assertEquals('Identifiant', $config->get('label'));
    $this->assertEquals('en', $config->get('langcode'));

    $fr_config = $this->languageManager->getLanguageConfigOverride('fr', 'search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_id');
    $this->assertFalse($fr_config->isNew());
    $this->assertEquals('Identifiant', $fr_config->get('label'));

    // Check the search_api_sorts_field search_api_sorts_field in the default
    // language with a translation.
    // Expected output: config was already correct, so this should stay the
    // same.
    $config = $this->config('search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_created');
    $this->assertEquals('created', $config->get('label'));
    $this->assertEquals('en', $config->get('langcode'));

    $fr_config = $this->languageManager->getLanguageConfigOverride('fr', 'search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_created');
    $this->assertFalse($fr_config->isNew());
    $this->assertEquals('Créé sur', $fr_config->get('label'));

    // Check the search_api_sorts_field search_api_sorts_field iin a different
    // language with a translation in the default language.
    // Expected output: English label should be moved to the default config
    // and the French label is added as a translation.
    $config = $this->config('search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_title');
    $this->assertEquals('Title', $config->get('label'));
    $this->assertEquals('en', $config->get('langcode'));

    $fr_config = $this->languageManager->getLanguageConfigOverride('fr', 'search_api_sorts.search_api_sorts_field.views_page---search_api_sorts_test_view__page_1_title');
    $this->assertFalse($fr_config->isNew());
    $this->assertEquals('Titre', $fr_config->get('label'));
  }

  /**
   * Create a search_api_sorts_field with sensible defaults.
   *
   * @param string $field
   *   The field identifier.
   * @param array $values
   *   An array of values that overrides the defaults.
   *
   * @return \Drupal\search_api_sorts\Entity\SearchApiSortsField
   *   The search_api_sorts_field entity.
   */
  protected function createSearchApiSortsField(string $field, array $values = []): SearchApiSortsField {
    $search_api_sorts_field = SearchApiSortsField::create($values + [
      'id' => sprintf('views_page---search_api_sorts_test_view__page_1_%s', $field),
      'display_id' => 'views_page---search_api_sorts_test_view__page_1',
      'field_identifier' => $field,
      'label' => $field,
      'langcode' => 'en',
      'status' => TRUE,
    ]);
    $search_api_sorts_field->save();
    return $search_api_sorts_field;
  }

}

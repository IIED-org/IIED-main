<?php

namespace Drupal\Tests\search404\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;
use Drupal\Tests\search404\Traits\ValidateSettingsMigrationTrait;

/**
 * Tests migration of search404 settings from D7 to config.
 *
 * @group search404
 */
class ValidateD7SettingsMigrationTest extends MigrateDrupal7TestBase {
  use ValidateSettingsMigrationTrait;

  /**
   * The migration this test is testing.
   *
   * @var string
   */
  const MIGRATION_UNDER_TEST = 'd7_search404_settings';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['search404'];

  /**
   * Test that variables are successfully migrated to configuration.
   */
  public function testMigration() {
    // Set up fixtures in the source database.
    $fixtureCustomSearchPath = $this->randomString();
    $this->setUpD7Variable('search404_custom_search_path', $fixtureCustomSearchPath);
    $fixtureDisableErrorMessage = $this->randomBoolean();
    $this->setUpD7Variable('search404_disable_error_message', $fixtureDisableErrorMessage);
    $fixtureDoCustomSearch = $this->randomBoolean();
    $this->setUpD7Variable('search404_do_custom_search', $fixtureDoCustomSearch);
    $fixtureDoGoogleCse = $this->randomBoolean();
    $this->setUpD7Variable('search404_do_google_cse', $fixtureDoGoogleCse);
    $fixtureDoSearchByPage = $this->randomBoolean();
    $this->setUpD7Variable('search404_do_search_by_page', $fixtureDoSearchByPage);
    $fixtureFirst = $this->randomBoolean();
    $this->setUpD7Variable('search404_first', $fixtureFirst);
    $fixtureIgnore = $this->randomSpaceSeparatedWords();
    $this->setUpD7Variable('search404_ignore', $fixtureIgnore);
    $fixtureCustomErrorMessage = $this->randomSpaceSeparatedWords();
    $this->setUpD7Variable('search404_search_message', $fixtureCustomErrorMessage);
    $fixtureFirstOnPaths = $this->randomSpaceSeparatedWords();
    $this->setUpD7Variable('search404_first_on_paths', $fixtureFirstOnPaths);
    $fixtureIgnorePaths = $this->randomSpaceSeparatedWords();
    $this->setUpD7Variable('search404_ignore_paths', $fixtureIgnorePaths);
    $fixtureIgnoreExtensions = $this->randomFileExtensions();
    $this->setUpD7Variable('search404_ignore_extensions', $fixtureIgnoreExtensions);
    $fixtureIgnoreQuery = $this->randomFileExtensions();
    $this->setUpD7Variable('search404_ignore_query', $fixtureIgnoreQuery);
    $fixtureJump = $this->randomBoolean();
    $this->setUpD7Variable('search404_jump', $fixtureJump);
    $fixturePageText = $this->getRandomGenerator()->paragraphs(2);
    $this->setUpD7Variable('search404_page_text', $fixturePageText);
    $fixturePageTitle = $this->randomString();
    $this->setUpD7Variable('search404_page_title', $fixturePageTitle);
    $fixtureRedirect301 = $this->randomBoolean();
    $this->setUpD7Variable('search404_redirect_301', $fixtureRedirect301);
    $fixtureRegex = $this->randomRegex();
    $this->setUpD7Variable('search404_regex', $fixtureRegex);
    $fixtureSkipAutoSearch = $this->randomBoolean();
    $this->setUpD7Variable('search404_skip_auto_search', $fixtureSkipAutoSearch);
    $fixtureUseOr = $this->randomBoolean();
    $this->setUpD7Variable('search404_use_or', $fixtureUseOr);
    $fixtureUseSearchEngine = $this->randomBoolean();
    $this->setUpD7Variable('search404_use_search_engine', $fixtureUseSearchEngine);

    // Run the migration.
    $this->executeMigrations([self::MIGRATION_UNDER_TEST]);

    // Verify the variables with migrations are now present in the destination
    // site.
    $config = $this->config('search404.settings');
    $this->assertSame($fixtureCustomSearchPath, $config->get('search404_custom_search_path'));
    $this->assertSame($fixtureDisableErrorMessage, $config->get('search404_disable_error_message'));
    $this->assertSame($fixtureDoCustomSearch, $config->get('search404_do_custom_search'));
    $this->assertSame($fixtureDoGoogleCse, $config->get('search404_do_google_cse'));
    $this->assertSame($fixtureDoSearchByPage, $config->get('search404_do_search_by_page'));
    $this->assertSame($fixtureFirst, $config->get('search404_first'));
    $this->assertSame($fixtureIgnore, $config->get('search404_ignore'));
    $this->assertSame($fixtureIgnoreExtensions, $config->get('search404_ignore_extensions'));
    $this->assertSame($fixtureIgnoreQuery, $config->get('search404_ignore_query'));
    $this->assertSame($fixtureJump, $config->get('search404_jump'));
    $this->assertSame($fixturePageText, $config->get('search404_page_text'));
    $this->assertSame($fixturePageTitle, $config->get('search404_page_title'));
    $this->assertSame($fixtureRedirect301, $config->get('search404_redirect_301'));
    $this->assertSame($fixtureRegex, $config->get('search404_regex'));
    $this->assertSame($fixtureSkipAutoSearch, $config->get('search404_skip_auto_search'));
    $this->assertSame($fixtureUseOr, $config->get('search404_use_or'));
    $this->assertSame($fixtureUseSearchEngine, $config->get('search404_use_search_engine'));
    $this->assertSame($fixtureCustomErrorMessage, $config->get('search404_custom_error_message'));
    $this->assertSame($fixtureFirstOnPaths, $config->get('search404_first_on_paths'));
    $this->assertSame($fixtureIgnorePaths, $config->get('search404_ignore_paths'));
  }

  /**
   * Set up a D7 variable to be migrated.
   *
   * @param string $name
   *   The name of the variable to be set.
   * @param mixed $value
   *   The value of the variable to be set.
   */
  protected function setUpD7Variable($name, $value) {
    $this->assertIsString($name, 'Name must be a string');

    Database::getConnection('default', 'migrate')
      ->upsert('variable')
      ->key('name')
      ->fields(['name', 'value'])
      ->values([
        'name' => $name,
        'value' => serialize($value),
      ])
      ->execute();
  }

}

<?php

namespace Drupal\Tests\merge_translations\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests merge node translations.
 *
 * @group merge_translations
 */
class MergeTranslationsFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'merge_translations',
    'node',
  ];

  /**
   * A user with permission to merge translations.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * An array of created entities.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $entities;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $this->editorUser = $this->drupalCreateUser(['merge_permissions admin']);

    // Add several languages.
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();
    ConfigurableLanguage::createFromLangcode('it')->save();
    ConfigurableLanguage::createFromLangcode('pl')->save();

    // Add several articles in different languages.
    $this->entities = [];
    foreach (['en', 'de', 'es', 'it', 'pl'] as $langcode) {
      $entity = Node::create([
        'title' => $this->randomMachineName(),
        'type' => 'article',
        'langcode' => $langcode,
      ]);
      $entity->save();

      $this->entities[$langcode] = $entity;
    }

    $this->drupalLogin($this->editorUser);
  }

  /**
   * Tests the merge translations form.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown for failed expectations.
   * @throws \Behat\Mink\Exception\ResponseTextException
   *   Thrown when an expectation on the response text fails.
   */
  public function testMergeTranslations() {
    $english_node = $this->entities['en'];
    $this->drupalGet('node/' . $english_node->id() . '/merge_translations');

    // Tests the title of the form.
    $this->assertSession()->pageTextContains('Merge translations of ' . $english_node->label());

    // Tests access to merge translations form.
    $this->assertSession()->statusCodeEquals('200');
    $this->drupalLogout();
    $this->drupalGet('node/' . $english_node->id() . '/merge_translations');
    $this->assertSession()->statusCodeEquals('403');
  }

}

<?php

namespace Drupal\Tests\layout_paragraphs\FunctionalJavascript;

use Behat\Mink\Exception\ExpectationException;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Entity\ContentLanguageSettings;

/**
 * Tests various translation contexts for Layout Paragraphs.
 *
 * @group layout_paragraphs
 */
class TranslationTest extends BuilderTestBase {


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
    'content_translation',
    'language',
    'layout_paragraphs_translations_test',
  ];

  /**
   * Tests symmetric translations.
   */
  public function testSymmetricTranslations() {
    $this->testContentTranslations(FALSE);
  }

  /**
   * Tests asymmetric translations.
   */
  public function testAsymmetricTranslations() {
    $this->testContentTranslations(TRUE);

    /** @var Drupal\node\Entity\Node $node */
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load(1);

    $paragraphs = $node->field_content->referencedEntities();
    $src_paragraph_ids = array_map(function ($paragraph) {
      return $paragraph->id();
    }, $paragraphs);
    $src_paragraph_languages = array_unique(array_map(function ($paragraph) {
      return $paragraph->language()->getId();
    }, $paragraphs));

    $translation = $node->getTranslation('de');
    $paragraphs = $translation->field_content->referencedEntities();
    $translation_paragraph_ids = array_map(function ($paragraph) {
      return $paragraph->id();
    }, $paragraphs);
    $translation_paragraph_languages = array_unique(array_map(function ($paragraph) {
      return $paragraph->language()->getId();
    }, $paragraphs));

    if (count($src_paragraph_languages) > 1) {
      throw new ExpectationException('There should only be one language in source paragraphs.', $this->getSession()->getDriver());
    }
    if (count($translation_paragraph_languages) > 1) {
      throw new ExpectationException('There should only be one language in translated paragraphs.', $this->getSession()->getDriver());
    }
    if ($translation_paragraph_languages == $src_paragraph_languages) {
      throw new ExpectationException('Translated paragraphs should be in a different language than source paragraphs.', $this->getSession()->getDriver());
    }
    if (count(array_intersect($src_paragraph_ids, $translation_paragraph_ids)) > 0) {
      throw new ExpectationException('Async translations should duplicate paragraphs, not just translate them.', $this->getSession()->getDriver());
    }
  }

  /**
   * Tests switching the host entity language.
   */
  public function testSwitchLanguage() {
    $this->enableTranslations();
    $this->createTestContent();
    $this->drupalGet('node/1/edit');
    $this->submitForm([
      'title[0][value]' => 'Node title (de)',
      'langcode[0][value]' => 'de',
    ], 'Save');
    // Test that all paragraphs have "de" langcode, not "en".
    $this->assertSession()->pageTextContains('Section component language: de');
    $this->assertSession()->pageTextContains('Text component language: de');
    $this->assertSession()->pageTextNotContains('Text component language: en');
    $this->assertSession()->pageTextNotContains('Text component language: en');
  }

  /**
   * Tests adding paragraphs in the correct language.
   */
  public function testAddedInCorrectLanugage() {
    $this->testSwitchLanguage();
    $this->drupalGet('node/1/edit');
    $this->addTextComponent('Language should be "de".', '.layout__region--first .lpb-btn--add.after');
    $this->assertSession()->pageTextNotContains('Text component language: en');
    $this->assertSession()->pageTextContains('Text component language: de');
    $this->htmlOutput($this->getSession()->getPage()->getHtml());
  }

  /**
   * Enables content translations.
   *
   * @param bool $asymmetric
   *   Whether to support asymmetric translations.
   */
  protected function enableTranslations($asymmetric = FALSE) {
    // Add a second language.
    ConfigurableLanguage::create(['id' => 'de'])->save();

    // Configure content translation.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
    ContentLanguageSettings::loadByEntityTypeBundle('node', 'page')
      ->setLanguageAlterable(TRUE)
      ->save();
    if ($asymmetric) {
      // Reference field IS translatable for asymmetrical translations.
      \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page')['field_content']->setTranslatable(TRUE)->save();
      // Disable translation for paragraphs, as they are cloned not translated.
      \Drupal::service('content_translation.manager')->setEnabled('paragraph', 'text', FALSE);
      \Drupal::service('content_translation.manager')->setEnabled('paragraph', 'section', FALSE);
      \Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', 'text')['field_text']->setTranslatable(FALSE)->save();
    }
    else {
      // Reference field IS NOT translatable for symmetrical translations.
      \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'page')['field_content']->setTranslatable(FALSE)->save();
      // Enable translation for paragraphs.
      \Drupal::service('content_translation.manager')->setEnabled('paragraph', 'text', TRUE);
      \Drupal::service('content_translation.manager')->setEnabled('paragraph', 'section', TRUE);
      \Drupal::service('entity_field.manager')->getFieldDefinitions('paragraph', 'text')['field_text']->setTranslatable(TRUE)->save();
    }
  }

  /**
   * Creates some test content.
   */
  protected function createTestContent() {
    $this->loginWithPermissions([
      'create page content',
      'edit own page content',
      'create content translations',
      'translate any entity',
    ]);

    // Create a new page.
    $this->drupalGet('node/add/page');

    // Add a three-column section.
    $this->addSectionComponent(2, '.lpb-btn--add');

    // Add a text component to each section and save the node.
    $this->addTextComponent('First component', '.layout__region--first .lpb-btn--add');
    $this->addTextComponent('Second component', '.layout__region--second .lpb-btn--add');
    $this->addTextComponent('Third component', '.layout__region--third .lpb-btn--add');
    $this->submitForm([
      'title[0][value]' => 'Node title',
    ], 'Save');
  }

  /**
   * Tests symmetric or assymetric translations.
   */
  protected function testContentTranslations($asymmetric = FALSE) {

    $this->enableTranslations($asymmetric);
    $this->createTestContent();

    // Translate the node into German.
    $this->drupalGet('node/1/translations');
    $page = $this->getSession()->getPage();
    $button = $page->find('css', '[hreflang="de"]');
    $button->click();

    if ($asymmetric) {
      // Test that the interface shows the asymmetric translation warning
      // and that delete/duplicate buttons are NOT hidden.
      $this->assertSession()->pageTextContains('You are in translation mode. Changes will only affect the current language.');
      $this->assertSession()->elementExists('css', '.lpb-duplicate');
      $this->assertSession()->elementExists('css', '.lpb-delete');
    }
    else {
      $this->assertSession()->pageTextContains('You are in translation mode. You cannot add or remove items while translating. Reordering items will affect all languages.');
      $this->assertSession()->elementNotExists('css', '.lpb-duplicate');
      $this->assertSession()->elementNotExists('css', '.lpb-delete');
    }

    // Change the text of the German-language components and save the node.
    $this->changeTextComponentText('.layout__region--first .lpb-edit', 'first (de)');
    $this->changeTextComponentText('.layout__region--second .lpb-edit', 'second (de)');
    $this->changeTextComponentText('.layout__region--third .lpb-edit', 'third (de)');
    $this->submitForm([
      'title[0][value]' => 'Node title (de)',
    ], 'Save');

    // Test that the English-language node was not affected.
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextNotContains('first (de)');
    $this->assertSession()->pageTextNotContains('second (de)');
    $this->assertSession()->pageTextNotContains('third (de)');

    // Test that the German-language node was correctly changed.
    $this->drupalGet('de/node/1');
    $this->assertSession()->pageTextContains('first (de)');
    $this->assertSession()->pageTextContains('second (de)');
    $this->assertSession()->pageTextContains('third (de)');

    $this->drupalGet('node/1/edit');
    $button = $page->find('css', '.layout__region--third a.lpb-delete');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains('Delete component');
    $button = $page->find('css', 'button.lpb-btn--confirm-delete');
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextNotContains('third');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('third');
    $this->drupalGet('de/node/1');

    if ($asymmetric) {
      // Test that deleting a component from the primary language node
      // DOES NOT remove the component from the translation.
      $this->assertSession()->pageTextContains('third (de)');
    }
    else {
      // If in symmetric mode, deleting component from the primary language
      // deletes it everyhere.
      $this->assertSession()->pageTextNotContains('third (de)');
    }

  }

  /**
   * Changes the text field for a text component.
   *
   * @param string $css_selector
   *   CSS selector of the edit button to push.
   * @param string $new_text
   *   The new text value.
   */
  protected function changeTextComponentText($css_selector, $new_text) {
    $page = $this->getSession()->getPage();
    $button = $page->find('css', $css_selector);
    $button->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->fillField('field_text[0][value]', $new_text);
    // Force show the hidden submit button so we can click it.
    $this->getSession()->executeScript("jQuery('.lpb-btn--save').attr('style', '');");
    $button = $this->assertSession()->waitForElementVisible('css', ".lpb-btn--save");
    $button->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->pageTextContains($new_text);
  }

}

<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\views\Views;

/**
 * Tests the basic AJAX functionality of BEF exposed forms.
 *
 * @group better_exposed_filters
 */
class BetterExposedFiltersTest extends BetterExposedFiltersTestBase {

  /**
   * Test label hidden setting.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testLabelHidden(): void {
    $view = Views::getView('bef_test');
    $session = $this->assertSession();

    $this->drupalGet('/bef-test-slider-between');
    $session->elementAttributeNotContains('css', '#edit-field-bef-price-value-wrapper--2 legend span', 'class', 'visually-hidden');

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_price_value' => [
          'plugin_id' => 'bef_sliders',
          'advanced' => [
            'hide_label' => TRUE,
          ],
        ],
      ],
    ], 'page_4');

    $this->drupalGet('/bef-test-slider-between');
    $session->elementAttributeContains('css', '#edit-field-bef-price-value-wrapper--2 legend span', 'class', 'visually-hidden');
  }

  /**
   * Tests when remember last selection is used.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testRememberLastSelection(): void {
    $this->drupalLogin($this->createUser());
    $this->drupalGet('bef-test');
    $this->getSession()->getPage()->fillField('field_bef_email_value', 'bef-test2@drupal.org');
    $this->getSession()->getPage()->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->fieldValueEquals('field_bef_email_value', 'bef-test2@drupal.org');

    // Now go back and verify email was remembered.
    $this->drupalGet('bef-test');
    $this->assertSession()->fieldValueEquals('field_bef_email_value', 'bef-test2@drupal.org');

    // Click Reset button.
    $this->getSession()->getPage()->pressButton('Reset');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify field cleared.
    $this->assertSession()->fieldValueEquals('field_bef_email_value', '');
  }

  /**
   * Test filter classes setting.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFieldClasses(): void {
    $view = Views::getView('bef_test');
    $session = $this->assertSession();

    $this->drupalGet('/bef-test');
    $session->elementAttributeNotContains('css', 'input#edit-field-bef-email-value', 'class', 'bef-test-field-class');

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_email_value' => [
          'advanced' => [
            'field_classes' => 'bef-test-field-class',
          ],
        ],
      ],
    ]);

    $this->drupalGet('bef-test');
    $session->elementAttributeContains('css', 'input#edit-field-bef-email-value', 'class', 'bef-test-field-class');
  }

}

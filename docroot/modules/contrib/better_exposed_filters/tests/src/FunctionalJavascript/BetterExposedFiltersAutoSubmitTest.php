<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\views\Views;

/**
 * Tests the auto submit functionality of better exposed filters.
 *
 * @group better_exposed_filters
 */
class BetterExposedFiltersAutoSubmitTest extends BetterExposedFiltersTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a few test nodes.
    $this->createNode([
      'title' => 'Page One',
      'field_bef_price' => '10',
      'field_bef_letters' => 'a',
      'type' => 'bef_test',
      'created' => strtotime('-2 days'),
    ]);
    $this->createNode([
      'title' => 'Page Two',
      'field_bef_price' => '75',
      'field_bef_letters' => 'b',
      'type' => 'bef_test',
      'created' => strtotime('-3 days'),
    ]);
  }

  /**
   * Tests if filtering via auto-submit works with selected breakpoint.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAutoSubmitBreakpoint(): void {
    $view = Views::getView('bef_test');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_exclude_textfield' => FALSE,
        'autosubmit_breakpoint' => 'bef_test:bef_test.test',
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Prepare window.
    $session->resizeWindow(500, 500);

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Enter value in email field.
    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    /* Assert exposed operator field does not have attribute to exclude it from
    auto-submit. */
    $field_bef_exposed_operator_email = $page->find('css', '.form-item-field-bef-email-value-1 input');
    $this->assertFalse($field_bef_exposed_operator_email->hasAttribute('data-bef-auto-submit-exclude'));
    $field_bef_email->setValue('1bef');
    // Verify that auto submit didn't run, due to breakpoint.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Prepare window.
    $session->resizeWindow(1000, 1000);
    $this->assertSession()->assertWaitOnAjaxRequest();

    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('1bef');
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests if filtering via auto-submit works.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAutoSubmitMinLength(): void {
    $view = Views::getView('bef_test');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_exclude_textfield' => FALSE,
        'autosubmit_textfield_minimum_length' => 3,
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Enter value in email field.
    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    /* Assert exposed operator field does not have attribute to exclude it from
    auto-submit. */
    $field_bef_exposed_operator_email = $page->find('css', '.form-item-field-bef-email-value-1 input');
    $this->assertFalse($field_bef_exposed_operator_email->hasAttribute('data-bef-auto-submit-exclude'));
    $field_bef_email->setValue('1');
    // Verify that auto submit didn't run, due to less than 4 characters.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('1bef');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests if filtering via auto-submit works.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAutoSubmit(): void {
    $view = Views::getView('bef_test');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_exclude_textfield' => TRUE,
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Search for "Page One".
    $field_bef_integer = $page->findField('field_bef_integer_value');
    $field_bef_integer->setValue('1');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Enter value in email field.
    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('qwerty@test.com');

    // Enter value in exposed operator email field.
    $field_bef_exposed_operator_email = $page->find('css', '.form-item-field-bef-email-value-1 input');
    $field_bef_exposed_operator_email->setValue('qwerty@test.com');

    // Verify nothing has changed.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Submit form.
    $this->submitForm([], 'Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify no results are visible.
    $html = $page->getHtml();
    $this->assertStringNotContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests if filtering via auto-submit works if exposed form is a block.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testAutoSubmitWithExposedFormBlock() {
    $view = Views::getView('bef_test');
    $this->drupalPlaceBlock('views_exposed_filter_block:bef_test-page_2');

    // Enable auto-submit, but disable for text fields.
    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'autosubmit_exclude_textfield' => TRUE,
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test-with-block');

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // Search for "Page One".
    $field_bef_integer = $page->findField('field_bef_integer_value');
    $field_bef_integer->setValue('1');
    $field_bef_integer->blur();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that only the "Page One" Node is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Enter value in email field.
    $field_bef_email = $page->find('css', '.form-item-field-bef-email-value input');
    $field_bef_email->setValue('qwerty@test.com');

    // Enter value in exposed operator email field.
    $field_bef_exposed_operator_email = $page->find('css', '.form-item-field-bef-email-value-1 input');
    $field_bef_exposed_operator_email->setValue('qwerty@test.com');

    // Verify nothing has changed.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);

    // Submit form.
    $this->submitForm([], 'Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify no results are visible.
    $html = $page->getHtml();
    $this->assertStringNotContainsString('Page One', $html);
    $this->assertStringNotContainsString('Page Two', $html);
  }

  /**
   * Tests auto submit with checkboxes.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testAutoSubmitWithCheckboxes(): void {
    // Visit the bef-test page.
    $this->drupalGet('bef-test-checkboxes');

    $session = $this->getSession();
    $page = $session->getPage();

    $this->assertSession()->pageTextContains('Page One');
    $this->assertSession()->pageTextContains('Page Two');

    $page->checkField('edit-field-bef-letters-value-a');
    $page->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains('Page One');
    $this->assertSession()->pageTextNotContains('Page Two');
  }

  /**
   * Tests auto submit sort only.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function testAutoSubmitSortOnly(): void {
    $view = Views::getView('bef_test');

    $this->setBetterExposedOptions($view, [
      'general' => [
        'autosubmit' => TRUE,
        'auto_submit_sort_only' => TRUE,
        'autosubmit_exclude_textfield' => FALSE,
        'autosubmit_textfield_minimum_length' => 3,
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Ensure that the content we're testing for is present.
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);

    // This should trigger nothing.
    $field_bef_integer = $page->findField('field_bef_integer_value');
    $field_bef_integer->setValue('1');
    $html = $page->getHtml();
    $this->assertStringContainsString('Page One', $html);
    $this->assertStringContainsString('Page Two', $html);
    $field_bef_integer->setValue('All');

    // Change sort.
    $page->selectFieldOption('sort_order', 'ASC');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $cells = $this->xpath('//table/tbody/tr/td[1]');
    $values = array_map(fn($cell) => $cell->getText(), $cells);

    // Now check the expected order.
    $this->assertEquals('Page Two', $values[0]);
    $this->assertEquals('Page One', $values[1]);
  }

}

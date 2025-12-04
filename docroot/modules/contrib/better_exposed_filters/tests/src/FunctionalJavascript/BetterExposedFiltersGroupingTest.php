<?php

namespace Drupal\Tests\better_exposed_filters\FunctionalJavascript;

use Drupal\views\Views;

/**
 * Tests BEF grouping.
 *
 * @group better_exposed_filters
 */
class BetterExposedFiltersGroupingTest extends BetterExposedFiltersTestBase {

  /**
   * Tests grouping with a secondary exposed option.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testSecondaryCollapsibleOptions(): void {
    $page = $this->getSession()->getPage();

    $this->drupalGet('/bef-test-groups');
    $this->click('details#edit-secondary');
    $this->click('details#edit-field-bef-letters-value-collapsible');
    $page->findField('field_bef_letters_value[a]')->check();
    $page->pressButton('Apply');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify fieldsets are open.
    $session = $this->assertSession();
    $session->elementAttributeContains('css', 'details.bef--secondary', 'open', 'open');
    $session->elementAttributeContains('css', 'details.bef--secondary details.form-item', 'open', 'open');
  }

  /**
   * Tests placing exposed filters inside a collapsible field-set.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testSecondaryOptions(): void {
    $view = Views::getView('bef_test');

    $this->setBetterExposedOptions($view, [
      'general' => [
        'allow_secondary' => TRUE,
        'secondary_label' => 'Secondary Options TEST',
        'autosubmit' => FALSE,
      ],
      'sort' => [
        'plugin_id' => 'default',
        'advanced' => [
          'is_secondary' => TRUE,
        ],
      ],
      'pager' => [
        'plugin_id' => 'default',
        'advanced' => [
          'is_secondary' => TRUE,
        ],
      ],
      'filter' => [
        'field_bef_boolean_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'is_secondary' => TRUE,
          ],
        ],
        'field_bef_integer_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'is_secondary' => TRUE,
            'collapsible' => TRUE,
          ],
        ],
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    $session = $this->getSession();
    $page = $session->getPage();

    // Assert our fields are initially hidden inside the collapsible field-set.
    $secondary_options = $page->find('css', '.bef--secondary');
    $this->assertFalse($secondary_options->hasAttribute('open'));
    $secondary_options->hasField('field_bef_boolean_value');
    $this->assertTrue($secondary_options->hasField('field_bef_integer_value'), 'Integer field should be present in secondary options');

    // Submit form and set a value for the boolean field.
    $secondary_options->click();
    $this->submitForm(['field_bef_boolean_value' => 1], 'Apply');
    $session = $this->getSession();
    $page = $session->getPage();

    // Verify our field-set is open and our fields visible.
    $secondary_options = $page->find('css', '.bef--secondary');
    $this->assertTrue($secondary_options->hasAttribute('open'));
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Tests when filter is marked to be collapsed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterCollapsible() {
    $view = Views::getView('bef_test');
    $session = $this->getSession();
    $page = $session->getPage();

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_email_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'collapsible' => TRUE,
            'collapsible_disable_automatic_open' => TRUE,
          ],
        ],
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    // Assert the field is closed by default.
    $details_summary = $page->find('css', '#edit-field-bef-email-value-collapsible summary');
    $this->assertTrue($details_summary->hasAttribute('aria-expanded'));
    $this->assertEquals('false', $details_summary->getAttribute('aria-expanded'));

    // Verify field_bef_email is 2nd in the filter.
    $email_details = $page->find('css', '.views-exposed-form .form-item:nth-child(2)');
    $this->assertEquals('edit-field-bef-email-value-collapsible', $email_details->getAttribute('id'));

    // Assert the field is closed by default.
    $details_summary = $page->find('css', '#edit-field-bef-email-value-collapsible summary');
    $this->assertTrue($details_summary->hasAttribute('aria-expanded'));
    $this->assertEquals('false', $details_summary->getAttribute('aria-expanded'));
  }

  /**
   * Tests when filter is marked to be collapsed but open by default.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testFilterCollapsibleOpenByDefault() {
    $view = Views::getView('bef_test');
    $session = $this->getSession();
    $page = $session->getPage();

    $this->setBetterExposedOptions($view, [
      'filter' => [
        'field_bef_email_value' => [
          'plugin_id' => 'default',
          'advanced' => [
            'collapsible' => TRUE,
            'open_by_default' => TRUE,
          ],
        ],
      ],
    ]);

    // Visit the bef-test page.
    $this->drupalGet('bef-test');

    // Assert the field is opened by default.
    $details_summary = $page->find('css', '#edit-field-bef-email-value-collapsible summary');
    $this->assertTrue($details_summary->hasAttribute('aria-expanded'));
    $this->assertEquals('true', $details_summary->getAttribute('aria-expanded'));
  }

}

<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\Core\Url;

/**
 * Testbase for Ajax-based purge_ui forms.
 */
abstract class AjaxFormTestBase extends FormTestBase {

  /**
   * Determine whether the test should return Ajax properties or not.
   *
   * @return bool
   *   Whether the test should return Ajax properties or not.
   */
  protected function shouldReturnAjaxProperties(): bool {
    return TRUE;
  }

  /**
   * Presses a button in the dialog.
   *
   * Drupal hides the original buttons and places them in a special area of
   * the dialog. This ensures the proper button is clicked and assertions do not
   * error on clicking a hidden button.
   *
   * @param string $locator
   *   The button locator.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function pressDialogButton(string $locator): void {
    $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton($locator);
    $this->assertSession()->waitForElementRemoved('css', '#drupal-modal');
  }

  /**
   * Visits the dashboard to interact with forms.
   */
  protected function visitDashboard(): void {
    $this->drupalGet(Url::fromRoute('purge_ui.dashboard'));
    $queue_details = $this->assertSession()->elementExists('css', 'summary:contains("Queue")');
    $queue_details->click();
  }

  /**
   * Toggles the Drop Button widget.
   *
   * @param string $primary_action
   *
   * @return void
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function toggleDropbutton(string $primary_action): void {
    $link = $this->assertSession()->elementExists('named', ['link', $primary_action]);
    $dropbutton = $link->getParent()->getParent()->getParent();
    self::assertEquals('div', $dropbutton->getTagName());
    self::assertTrue($dropbutton->hasClass('dropbutton-widget'), $dropbutton->getHtml());
    $dropbutton->find('css', 'li.dropbutton-toggle')->click();
  }

}

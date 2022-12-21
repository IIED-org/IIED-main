<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\PurgerAddForm;

/**
 * Tests \Drupal\purge_ui\Form\PurgerAddForm.
 *
 * @group purge
 */
class PurgerAddFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_purger_test'];

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.purger_add_form';

  /**
   * {@inheritdoc}
   */
  protected $formClass = PurgerAddForm::class;

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Which purger would you like to add?';

  /**
   * Tests that the form route is only accessible under the right conditions.
   */
  public function testRouteConditionalAccess(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
    $this->initializePurgersService(['a', 'b', 'c']);
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
    $this->initializePurgersService(
      [
        'a',
        'b',
        'c',
        'withform',
        'good',
      ]
    );
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextContains('The requested page could not be found.');
  }

  /**
   * Tests that the right purgers are listed on the form.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddPresence(): void {
    $this->initializePurgersService(['a', 'withform', 'good']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Add');
    $this->assertSame(TRUE, count($this->purgePurgers->getPluginsEnabled()) === 3);
  }

  /**
   * Tests form submission results in the redirect command.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->initializePurgersService(['a', 'withform', 'good']);
    $this->visitDashboard();
    $this->assertSession()->linkNotExists('Purger C');
    $this->getSession()->getPage()->clickLink('Add purger');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->find("xpath", "//input[@value = 'c']")->selectOption("c");
    $this->pressDialogButton('Add');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementNotExists('css', '#drupal-modal');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->linkExists('Purger C');
    $this->purgePurgers->reload();
    $this->assertSame(TRUE, in_array('c', $this->purgePurgers->getPluginsEnabled()));
  }

  /**
   * Tests that the cancel button is present.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
    $this->getSession()->getPage()->clickLink('Add purger');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertActionExists('edit-cancel', 'Cancel');
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
    $this->getSession()->getPage()->clickLink('Add purger');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->pressDialogButton('Cancel');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementNotExists('css', '#drupal-modal');
  }

  /**
   * Tests the 'plugin_id' form element for listing only available purgers.
   *
   * @see \Drupal\purge_ui\Form\PurgerAddForm::buildForm
   */
  public function testTwoAvailablePurgers(): void {
    $this->initializePurgersService(['c', 'withform']);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->fieldExists('edit-plugin-id-a');
    $this->assertSession()->fieldExists('edit-plugin-id-b');
    $this->assertSession()->fieldExists('edit-plugin-id-good');
    $this->assertSession()->pageTextContains('Purger A');
    $this->assertSession()->pageTextContains('Purger B');
    $this->assertSession()->pageTextContains('Good Purger');
    $this->assertSession()->pageTextNotContains('Configurable purger');
    $this->assertSession()->buttonExists('Cancel');
    $this->assertSession()->buttonExists('Add');
  }

}

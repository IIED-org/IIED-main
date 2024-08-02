<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\QueuerAddForm;

/**
 * Tests \Drupal\purge_ui\Form\QueuerAddForm.
 *
 * @group purge
 */
class QueuerAddFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_queuer_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueuerAddForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queuer_add_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Which queuer would you like to add?';

  /**
   * Tests that the form route is only accessible under the right conditions.
   */
  public function testRouteConditionalAccess(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
    $this->initializeQueuersService(['a', 'b', 'c']);
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
    $this->initializeQueuersService(
      [
        'a',
        'b',
        'c',
        'withform',
        'purge_ui_block_queuer',
        'drush_purge_queue_add',
      ]
    );
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextContains('The requested page could not be found.');
  }

  /**
   * Tests that the right queuers are listed on the form.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->initializeQueuersService(['a', 'b']);
    $this->assertSession()->responseContains('Add');
    $this->assertSession()->responseNotContains('Queuer A');
    $this->assertSession()->responseNotContains('Queuer B');
    $this->assertSession()->responseContains('Queuer C');
    $this->assertSession()->responseContains('Queuer with form');
    $this->assertCount(2, $this->purgeQueuers->getPluginsEnabled());
    $this->assertTrue(in_array('a', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertTrue(in_array('b', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertFalse(in_array('c', $this->purgeQueuers->getPluginsEnabled()));
    $this->assertFalse(in_array('withform', $this->purgeQueuers->getPluginsEnabled()));
  }

  /**
   * Tests that the cancel button is present.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->buttonExists('Cancel');
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
    $this->assertSession()->linkExists('Queuer A');
    $url = $this->getPath();
    $js = <<<JS
    var ajaxSettings = {
      url: '{$url}',
      dialogType: 'modal',
      dialog: { width: 400 },
    };
    var myAjaxObject = Drupal.ajax(ajaxSettings);
    myAjaxObject.execute();
    JS;
    $this->getSession()->executeScript($js);
    $this->assertSession()->waitForElement('css', '.ui-dialog');
    $this->assertSession()->pageTextContains("Which queuer would you like to add?");
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("Cancel");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
    $this->assertSession()->pageTextNotContains("Which queuer would you like to add?");
    $this->assertSession()->linkExists('Queuer A');
  }

  /**
   * Tests form submission results in the redirect command.
   *
   * @see \Drupal\purge_ui\Form\QueuerAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->initializeQueuersService(['a', 'b']);
    $this->visitDashboard();
    $this->assertSession()->linkNotExists('Queuer C');
    $this->getSession()->getPage()->clickLink('Add queuer');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->selectFieldOption('id', 'c');
    $this->pressDialogButton('Add');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $this->assertSession()->linkExists('Queuer C');
    $this->purgeQueuers->reload();
    $this->assertTrue(in_array('c', $this->purgeQueuers->getPluginsEnabled()));
  }

}

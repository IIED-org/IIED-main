<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\ProcessorAddForm;

/**
 * Tests \Drupal\purge_ui\Form\ProcessorAddForm.
 *
 * @group purge
 */
class ProcessorAddFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_processor_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = ProcessorAddForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.processor_add_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Which processor would you like to add?';

  /**
   * Tests that the form route is only accessible under the right conditions.
   */
  public function testRouteConditionalAccess(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
    $this->initializeProcessorsService(['a', 'b', 'c']);
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
    $this->assertSession()->pageTextNotContains('The requested page could not be found.');
    $this->initializeProcessorsService(
      [
        'a',
        'b',
        'c',
        'withform',
        'purge_ui_block_processor',
        'drush_purge_queue_work',
        'drush_purge_invalidate',
      ]
    );
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextContains('The requested page could not be found.');
  }

  /**
   * Tests that the right processors are listed on the form.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->initializeProcessorsService(['a', 'b']);
    $this->assertSession()->responseContains('Add');
    $this->assertSession()->responseNotContains('Processor A');
    $this->assertSession()->responseNotContains('Processor B');
    $this->assertSession()->responseContains('Processor C');
    $this->assertSession()->responseContains('Processor with form');
    $this->assertCount(2, $this->purgeProcessors->getPluginsEnabled());
    $this->assertTrue(in_array('a', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertTrue(in_array('b', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertFalse(in_array('c', $this->purgeProcessors->getPluginsEnabled()));
    $this->assertFalse(in_array('withform', $this->purgeProcessors->getPluginsEnabled()));
  }

  /**
   * Tests form submission results in the redirect command.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::addPurger
   */
  public function testAddSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->initializeProcessorsService(['a', 'b']);
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->visitDashboard();
    $web_assert->linkNotExists('Processor C');
    $page->clickLink('Add processor');
    $web_assert->assertWaitOnAjaxRequest();
    $page->selectFieldOption('id', 'c');
    $this->pressDialogButton('Add');
    $web_assert->pageTextContains('The configuration options have been saved.');
    $web_assert->linkExists('Processor C');
    $this->purgeProcessors->reload();
    $this->assertTrue(in_array('c', $this->purgeProcessors->getPluginsEnabled()));
  }

  /**
   * Tests that the cancel button is present.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->visitDashboard();
    $this->getSession()->getPage()->clickLink('Add processor');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->hasButton('Cancel');
  }

  /**
   * Tests that the cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\ProcessorAddForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testCancelSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
    $this->getSession()->getPage()->clickLink('Add processor');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->pressDialogButton('Cancel');
    $this->assertSession()->elementNotExists('css', '#drupal-modal');
  }

}

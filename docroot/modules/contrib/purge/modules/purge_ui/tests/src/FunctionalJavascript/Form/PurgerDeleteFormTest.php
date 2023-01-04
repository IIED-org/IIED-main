<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\PurgerDeleteForm;

/**
 * Tests \Drupal\purge_ui\Form\PurgerDeleteForm.
 *
 * @group purge
 */
class PurgerDeleteFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_purger_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = PurgerDeleteForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.purger_delete_form';

  /**
   * {@inheritdoc}
   */
  protected $routeParameters = ['id' => 'id0'];

  /**
   * {@inheritdoc}
   */
  protected $routeParametersInvalid = ['id' => 'doesnotexist'];

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Are you sure you want to delete Purger A?';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializePurgersService(['a']);
  }

  /**
   * Tests that the "No" cancel button is present.
   */
  public function testNoPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('No');
  }

  /**
   * Tests "No" cancel button form submit.
   */
  public function testNoSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
    $this->assertSession()->linkExists('Purger A');
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
    $this->assertSession()->pageTextContains("Yes, delete this purger!");
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("No");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
    $this->assertSession()->pageTextNotContains("Yes, delete this purger!");
    $this->assertSession()->linkExists('Purger A');
  }

  /**
   * Tests that 'Yes, delete..', deletes the purger and closes the window.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::deletePurger
   */
  public function testDeletePurger(): void {
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
    $this->assertSession()->linkExists('Purger A');
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
    $this->assertSession()->pageTextContains("Yes, delete this purger!");
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("Yes, delete this purger!");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
    $this->assertSession()->linkNotExists('Purger A');
  }

  /**
   * Assert that deleting a purger that does not exist, passes silently.
   */
  public function testDeletePurgerWhichDoesNotExist(): void {
    $this->markTestSkipped('Unable to test directly anymore \Drupal\purge_ui\Controller\PurgerFormController::deleteForm');
  }

  /**
   * {@inheritdoc}
   */
  public function testRouteAccess(): void {
    $this->drupalGet($this->getPath());
    $this->assertSession()->pageTextContains('You are not authorized to access this page.');
    // This overloaded test exists because the form is always accessible, even
    // under bad input, to allow the form submit handler to emit its Ajax
    // even directly after the purger got deleted.
    //
    // @see \Drupal\purge_ui\Form\PurgerDeleteForm::deletePurger
    $path = $this->getPath($this->routeParametersInvalid);
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($path);
    $this->assertSession()->pageTextNotContains('You are not authorized to access this page.');
  }

}

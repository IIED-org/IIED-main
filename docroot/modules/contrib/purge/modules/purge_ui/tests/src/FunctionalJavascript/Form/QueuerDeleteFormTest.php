<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\QueuerDeleteForm;

/**
 * Tests \Drupal\purge_ui\Form\QueuerDeleteForm.
 *
 * @group purge
 */
class QueuerDeleteFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_queuer_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueuerDeleteForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queuer_delete_form';

  /**
   * {@inheritdoc}
   */
  protected $routeParameters = ['id' => 'a'];

  /**
   * {@inheritdoc}
   */
  protected $routeParametersInvalid = ['id' => 'doesnotexist'];

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Are you sure you want to delete the Queuer A queuer?';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueuersService(['a']);
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
    $this->assertSession()->pageTextContains("Yes, delete this queuer!");
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("No");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
    $this->assertSession()->pageTextNotContains("Yes, delete this queuer!");
    $this->assertSession()->linkExists('Queuer A');
  }

  /**
   * Tests that 'Yes, delete..', deletes the queuer and closes the window.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testDeleteQueuer(): void {
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
    $this->assertSession()->pageTextContains("Yes, delete this queuer!");
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("Yes, delete this queuer!");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
    $this->assertSession()->linkNotExists('Queuer A');
  }

}

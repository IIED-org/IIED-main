<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\QueueChangeForm;

/**
 * Tests \Drupal\purge_ui\Form\QueueChangeForm.
 *
 * @group purge
 */
class QueueChangeFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_queue_test', 'purge_ui'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueueChangeForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queue_change_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Change queue engine';

  /**
   * Tests that the selection form looks as expected.
   *
   * @see \Drupal\purge_ui\Form\QueueDetailForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testChangeForm(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    // Assert some of the page presentation.
    $this->assertSession()->responseContains('Change queue engine');
    $this->assertSession()->responseContains('The queue engine is the underlying plugin which stores');
    $this->assertSession()->responseContains('when you change the queue, it will be emptied as well');
    $this->assertSession()->responseContains('Description');
    $this->assertSession()->buttonExists('Cancel');
    $this->assertSession()->buttonExists( 'Change');
    // Assert that 'memory' is selected queue.
    $this->assertSession()->checkboxChecked('edit-plugin-id-memory');
  }

  /**
   * Tests that changing the form works as expected.
   */
  public function testChangeFormSubmit(): void {
    $this->drupalLogin($this->adminUser);

    $form = $this->formInstance()->buildForm([], $this->getFormStateInstance());
    $submitted = $this->getFormStateInstance();
    $submitted->setValue('plugin_id', 'b');
    $ajax = $this->formInstance()->changeQueue($form, $submitted);
    $this->assertAjaxCommandReloadConfigForm($ajax);
    $this->assertAjaxCommandCloseModalDialog($ajax);
    $this->assertAjaxCommandsTotal($ajax, 2);
    $this->drupalGet($this->getPath());
    $this->assertSession()->responseContains('Change queue engine');
    $this->assertSession()->checkboxChecked('edit-plugin-id-b');

    // Assert that the dialog closes.
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
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("Cancel");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
  }

}

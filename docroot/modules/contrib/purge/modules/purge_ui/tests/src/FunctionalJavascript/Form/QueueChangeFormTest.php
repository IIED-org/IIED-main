<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\Core\Url;
use Drupal\purge_ui\Form\QueueChangeForm;

/**
 * Tests \Drupal\purge_ui\Form\QueueChangeForm.
 *
 * @group purge
 */
class QueueChangeFormTest extends FormTestBase {

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
    $session = $this->assertSession();
    $this->drupalGet('/admin/config/development/performance/purge/queue/change');

    // Assert some of the page presentation.
    $session->pageTextContains('Change queue engine');
    $session->pageTextContains('The queue engine is the underlying plugin which stores');
    $session->pageTextContains('when you change the queue, it will be emptied as well');
    $session->pageTextContains('Description');
    $session->buttonExists('Cancel');
    $session->buttonExists('Change');
    // Assert that 'memory' is selected queue.
    $session->checkboxChecked('edit-plugin-id-memory');
  }

  /**
   * Tests that changing the form works as expected.
   */
  public function testChangeFormSubmit(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute($this->route));
    $this->getSession()->getPage()->selectFieldOption('plugin_id', 'b');
    $this->getSession()->getPage()->pressButton('Change');
    $this->assertSession()->fieldValueEquals('plugin_id', 'b');
    $this->drupalGet(Url::fromRoute($this->route));
    $this->assertSession()->responseContains('Change queue engine');
    $this->assertSession()->checkboxChecked('edit-plugin-id-b');
  }
}

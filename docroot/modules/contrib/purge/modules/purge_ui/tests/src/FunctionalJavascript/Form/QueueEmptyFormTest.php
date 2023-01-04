<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\QueueEmptyForm;

/**
 * Tests \Drupal\purge_ui\Form\QueueEmptyForm.
 *
 * @group purge
 */
class QueueEmptyFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'purge_ui',
    'purge_queuer_test',
    'purge_purger_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $formClass = QueueEmptyForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.queue_empty_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Are you sure you want to empty the queue?';

  /**
   * The queuer plugin.
   *
   * @var \Drupal\purge\Plugin\Purge\Queuer\QueuerInterface
   */
  protected $queuer;

  /**
   * Setup the test.
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializeQueuersService();
    $this->queuer = $this->purgeQueuers->get('a');
  }

  /**
   * Tests basic expectations of the form.
   */
  public function testPresence(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    // `titleEquals` does not work the same in FunctionalJavascript tests.
    $title = $this->assertSession()->elementExists('css', 'title');
    self::assertSame("Are you sure you want to empty the queue? | Drupal", $title->getHtml());
    $this->assertSession()->pageTextContains("This action cannot be undone.");
    $this->assertSession()->buttonExists('Yes, throw everything away!');
  }

  /**
   * Tests that the "No" cancel button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testNo(): void {
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
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("No");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
  }

  /**
   * Tests that the confirm button clears the queue.
   *
   * @see \Drupal\purge_ui\Form\QueuerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testConfirm(): void {
    // Add seven objects to the queue and assert that these get deleted.
    $this->initializeQueueService('database');
    $this->purgeQueue->add($this->queuer, $this->getInvalidations(7));
    // Assert that - after reloading/committing the queue - we still have these.
    $this->assertSame(7, $this->purgeQueue->numberOfItems());
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
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
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("Yes, throw everything away!");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
    $this->assertSession()->pageTextNotContains("This action cannot be undone.");
    $this->purgeQueuers->reload();
    $this->assertSame(0, $this->purgeQueue->numberOfItems());
  }

}

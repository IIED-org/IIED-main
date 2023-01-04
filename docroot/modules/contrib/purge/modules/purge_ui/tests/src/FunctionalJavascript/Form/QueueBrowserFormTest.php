<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\purge_ui\Form\QueueBrowserForm;

/**
 * Tests \Drupal\purge_ui\Form\QueueBrowserForm.
 *
 * @group purge
 */
class QueueBrowserFormTest extends AjaxFormTestBase {

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
  protected $formClass = QueueBrowserForm::class;

  /**
   * The route that renders the form.
   *
   * @var string
   */
  protected $route = 'purge_ui.queue_browser_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Purge queue browser';

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
    self::assertSame("Purge queue browser | Drupal", $title->getHtml());
    $this->assertSession()->pageTextContains("Your queue is empty.");
    $this->assertSession()->fieldNotExists('edit-1');
  }

  /**
   * Tests that the close button closes the dialog.
   *
   * @see \Drupal\purge_ui\Form\QueueBrowserForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testClose(): void {
    $this->drupalLogin($this->adminUser);
    $this->visitDashboard();
    $this->toggleDropbutton('Memory');
    $this->clickLink('Inspect');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->pressDialogButton('Close');
    $this->assertSession()->elementNotExists('css', '#drupal-modal');
  }

  /**
   * Tests that data is shown accordingly.
   *
   * @see \Drupal\purge_ui\Form\QueueBrowserForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::closeDialog
   */
  public function testData(): void {
    $this->drupalLogin($this->adminUser);
    $this->initializeInvalidationFactoryService();
    $this->initializePurgersService(['id' => 'good']);
    $this->initializeQueueService('database');
    // Add 30 tags to the queue and collect the strings we're adding.
    $tags = $needles = [];
    for ($i = 1; $i <= 30; $i++) {
      $needles[$i] = "node:$i";
      $tags[] = $this->purgeInvalidationFactory->get('tag', $needles[$i]);
    }
    $this->purgeQueue->add($this->queuer, $tags);
    // Assert that the pager works and returns our objects.
    $this->assertEquals(15, count($this->purgeQueue->selectPage()));
    $this->assertEquals(50, $this->purgeQueue->selectPageLimit(50));
    $this->assertEquals(30, count($this->purgeQueue->selectPage()));
    $this->purgeQueue->reload();
    // Render the interface and find the first 15 tags, the is on page 2.
    $this->visitDashboard();
    $this->toggleDropbutton('Database');
    $this->clickLink('Inspect');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->assertSession()->pageTextContains("Type");
    $this->assertSession()->pageTextContains("State");
    $this->assertSession()->pageTextContains("Expression");
    $this->assertSession()->pageTextContains("New");
    $this->assertSession()->buttonExists('1');
    $this->assertSession()->buttonExists('2');
    $this->getSession()->getPage()->pressButton('2');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertActionNotExists('edit-3', '3');
    foreach ($needles as $i => $needle) {
      $needle = "<td>$needle</td>";
      if ($i >= 16) {
        $this->assertSession()->responseContains($needle);
      }
      else {
        $this->assertSession()->responseNotContains($needle);
      }
    }
  }

}

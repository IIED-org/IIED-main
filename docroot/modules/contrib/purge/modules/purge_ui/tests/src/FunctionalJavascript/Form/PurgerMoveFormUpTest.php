<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript;

use Drupal\purge_ui\Form\PurgerMoveForm;
use Drupal\Tests\purge_ui\FunctionalJavascript\Form\AjaxFormTestBase;

/**
 * Tests \Drupal\purge_ui\Form\PurgerMoveForm.
 *
 * @group purge
 */
class PurgerMoveFormUpTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui', 'purge_purger_test'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = PurgerMoveForm::class;

  /**
   * {@inheritdoc}
   */
  protected $formId = 'purge_ui.purger_move_form';

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.purger_move_up_form';

  /**
   * {@inheritdoc}
   */
  protected $routeParameters = ['id' => 'id2', 'direction' => 'up'];

  /**
   * {@inheritdoc}
   */
  protected $routeParametersInvalid = ['id' => 'doesnotexist', 'direction' => 'up'];

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Do you want to move Purger C up in the execution order?';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $this->initializePurgersService(['a', 'b', 'c']);
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
   * Tests that 'Yes!', moves the purger in order and closes the window.
   *
   * @see \Drupal\purge_ui\Form\PurgerDeleteForm::buildForm
   * @see \Drupal\purge_ui\Form\CloseDialogTrait::deletePurger
   */
  public function testMoveUp(): void {
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
    $this->getSession()->getPage()->find("css", ".ui-dialog-buttonset")->pressButton("Yes!");
    $this->assertSession()->waitForElementRemoved('css', '.ui-dialog');
    $this->purgePurgers->reload();
    $this->assertEquals(['a', 'c', 'b'], array_values($this->purgePurgers->getPluginsEnabled()));
  }

}

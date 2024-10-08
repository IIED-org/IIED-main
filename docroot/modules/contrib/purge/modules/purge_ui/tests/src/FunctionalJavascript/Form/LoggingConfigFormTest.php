<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\purge_ui\Form\LoggingConfigForm;

/**
 * Tests \Drupal\purge_ui\Form\LoggingConfigForm.
 *
 * @group purge
 */
class LoggingConfigFormTest extends AjaxFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['purge_ui'];

  /**
   * {@inheritdoc}
   */
  protected $formClass = LoggingConfigForm::class;

  /**
   * {@inheritdoc}
   */
  protected $route = 'purge_ui.logging_config_form';

  /**
   * {@inheritdoc}
   */
  protected $routeTitle = 'Configure logging';

  /**
   * {@inheritdoc}
   */
  public function setUp($switch_to_memory_queue = TRUE): void {
    parent::setUp($switch_to_memory_queue);
    $defaults = [
      [
        'id' => 'testchannel',
        'grants' => [2, 4, 1],
      ],
    ];

    // Set a mocked logger as service.
    $this->purgeLogger = $this->createMock('Drupal\purge\Logger\LoggerServiceInterface');
    $this->purgeLogger->method('getChannels')->willReturn($defaults);
    $this->purgeLogger->method('hasChannel')
      ->will($this->returnCallback(function ($subject) {
        return ($subject === 'testchannel');
      }));
    $this->container->set('purge.logger', $this->purgeLogger);
  }

  /**
   * @covers \Drupal\purge_ui\Form\LoggingConfigForm::buildForm
   */
  public function testBuildForm(): void {
    $form = $this->formInstance()->buildForm([], $this->getFormStateInstance());
    // Verify the text description.
    $this->assertTrue(isset($form['msg']['#markup']));
    $this->assertTrue(TRUE, (bool) strpos($form['msg']['#markup']->render(), 'named <i><code>purge'));
    // Verify the structure of the table and that it holds the testchannel.
    $this->assertTrue(isset($form['table']['#header']['id']));
    $this->assertSame('Id', $form['table']['#header']['id']->render());
    $this->assertCount(9, $form['table']['#header']);
    $this->assertSame('checkbox', $form['table']['testchannel'][0]['#type']);
    $this->assertFalse($form['table']['testchannel'][0]['#default_value']);
    $this->assertTrue($form['table']['testchannel'][1]['#default_value']);
    $this->assertTrue($form['table']['testchannel'][2]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][3]['#default_value']);
    $this->assertTrue($form['table']['testchannel'][4]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][5]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][6]['#default_value']);
    $this->assertFalse($form['table']['testchannel'][7]['#default_value']);
    $this->assertCount(3, $form['table']);
    // Verify the action buttons.
    $this->assertSame('submit', $form['actions']['submit']['#type']);
    $this->assertSame('Save', $form['actions']['submit']['#value']->render());
    $this->assertSame('primary', $form['actions']['submit']['#button_type']);
    $this->assertSame('::setChannels', $form['actions']['submit']['#ajax']['callback']);
    $this->assertSame('submit', $form['actions']['cancel']['#type']);
    $this->assertSame('Cancel', $form['actions']['cancel']['#value']->render());
    $this->assertSame('danger', $form['actions']['cancel']['#button_type']);
    $this->assertSame('::closeDialog', $form['actions']['cancel']['#ajax']['callback']);
  }

  /**
   * @covers \Drupal\purge_ui\Form\LoggingConfigForm::setChannels
   */
  public function testSetChannels(): void {
    $form = $this->formInstance()->buildForm([], $this->getFormStateInstance());
    // Assert that empty submits only close the dialog, nothing else.
    $ajax = $this->formInstance()->setChannels($form, $this->getFormStateInstance());
    $this->assertInstanceOf(AjaxResponse::class, $ajax);
    $this->assertSame('closeDialog', $ajax->getCommands()[0]['command']);
    $this->assertCount(1, $ajax->getCommands());
    // Verify that non-existent channels don't lead to saving anything.
    $submitted = $this->getFormStateInstance();
    $submitted->setValue('table', ['fake' => ["1"]]);
    $ajax = $this->formInstance()->setChannels($form, $submitted);
    $this->assertInstanceOf(AjaxResponse::class, $ajax);
    $this->assertSame('closeDialog', $ajax->getCommands()[0]['command']);
    $this->assertCount(1, $ajax->getCommands());
    // Verify that correct data does lead to a write.
    $this->purgeLogger->expects($this->once())
      ->method('setChannel')
      ->with($this->equalTo('testchannel'), $this->equalTo([0, 1]));
    $submitted = $this->getFormStateInstance();
    $submitted->setValue('table', ['testchannel' => ["1", "1", "0", 0]]);
    $ajax = $this->formInstance()->setChannels($form, $submitted);
    $this->assertInstanceOf(AjaxResponse::class, $ajax);
    $this->assertSame('closeDialog', $ajax->getCommands()[0]['command']);
    $this->assertSame('redirect', $ajax->getCommands()[1]['command']);
    $this->assertCount(2, $ajax->getCommands());
  }

  /**
   * @covers \Drupal\purge_ui\Form\LoggingConfigForm::submitForm
   */
  public function testSubmitForm(): void {
    $form = $this->formInstance()->buildForm([], $this->getFormStateInstance());
    // Verify that the returned $has_resulted_in_changes is FALSE without data.
    $this->assertFalse($this->formInstance()->submitForm($form, $this->getFormStateInstance()));
    // Verify that non-existent channels don't lead to saving anything.
    $submitted = $this->getFormStateInstance();
    $submitted->setValue('table', ['fake' => ["1"]]);
    $this->assertFalse($this->formInstance()->submitForm($form, $submitted));
    // Verify that correct data does lead to a write.
    $this->purgeLogger->expects($this->once())
      ->method('setChannel')
      ->with($this->equalTo('testchannel'), $this->equalTo([0, 1]));
    $submitted = $this->getFormStateInstance();
    $submitted->setValue('table', ['testchannel' => ["1", "1", "0", 0]]);
    $this->assertTrue($this->formInstance()->submitForm($form, $submitted));
  }

}

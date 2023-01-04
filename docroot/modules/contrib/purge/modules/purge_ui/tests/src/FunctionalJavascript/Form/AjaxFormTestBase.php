<?php

namespace Drupal\Tests\purge_ui\FunctionalJavascript\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Testbase for Ajax-based purge_ui forms.
 */
abstract class AjaxFormTestBase extends FormTestBase {

  /**
   * Assert that a \Drupal\Core\Ajax\CloseModalDialogCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   * @param string $command
   *   The name of the command to assert.
   * @param string[] $parameters
   *   Expected parameters present in the command array.
   */
  protected function assertAjaxCommand(AjaxResponse $ajax, $command, array $parameters = []): void {
    $commands = $ajax->getCommands();
    $commandsString = var_export($commands, TRUE);
    $match = array_search($command, array_column($commands, 'command'));
    $this->assertSame(
      TRUE,
      is_int($match),
      "Ajax command $command not found:\n " . $commandsString
    );
    $this->assertSame(TRUE, isset($commands[$match]));
    foreach ($parameters as $parameter => $value) {
      $this->assertSame(
        TRUE,
        isset($commands[$match][$parameter]),
        "Ajax parameter '$parameter' = '$value' not found:\n " . $commandsString
      );
      $this->assertSame(
        $commands[$match][$parameter],
        $value,
        "Ajax parameter '$parameter' = '$value' not equal:\n " . $commandsString
      );
    }
  }

  /**
   * Assert that a \Drupal\Core\Ajax\CloseModalDialogCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   * @param int $expected
   *   The total number of expected commands.
   */
  protected function assertAjaxCommandsTotal(AjaxResponse $ajax, int $expected): void {
    $commands = $ajax->getCommands();
    $this->assertSame($expected, count($commands), var_export($commands, TRUE));
  }

  /**
   * Assert that a \Drupal\Core\Ajax\CloseModalDialogCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   */
  protected function assertAjaxCommandCloseModalDialog(AjaxResponse $ajax): void {
    $this->assertAjaxCommand(
      $ajax,
      'closeDialog',
      ['selector' => '#drupal-modal']
    );
  }

  /**
   * Assert that a \Drupal\purge_ui\Form\ReloadConfigFormCommand is issued.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $ajax
   *   The undecoded AjaxResponse object returned by the http_kernel.
   */
  protected function assertAjaxCommandReloadConfigForm(AjaxResponse $ajax): void {
    $this->assertAjaxCommand($ajax, 'redirect');
  }

  /**
   * Assert that the given action exists.
   *
   * For some unknown reason, WebAssert::fieldExists() doesn't work on Ajax
   * modal forms, is doesn't detect form fields while they do exist in the
   * raw HTML response. This temporary assertion aids aims to solve this.
   *
   * @param string $id
   *   The id of the action field.
   * @param string $value
   *   The expected value of the action field.
   */
  protected function assertActionExists($id, $value): void {
    $button = $this->assertSession()
      ->elementExists('css', '.ui-dialog-buttonpane')
      ->findButton($value);
    self::assertNotNull($button);
  }

  /**
   * Assert that the given action does not exist.
   *
   * For some unknown reason, WebAssert::fieldExists() doesn't work on Ajax
   * modal forms, is doesn't detect form fields while they do exist in the
   * raw HTML response. This temporary assertion aids aims to solve this.
   *
   * @param string $id
   *   The id of the action field.
   * @param string $value
   *   The expected value of the action field.
   */
  protected function assertActionNotExists($id, $value): void {
    $button = $this->assertSession()
      ->elementExists('css', '.ui-dialog-buttonpane')
      ->findButton($value);
    self::assertNull($button);
  }

  /**
   * Submits a ajax form through http_kernel.
   *
   * @param array $edit
   *   Field data in an associative array. Changes the current input fields
   *   (where possible) to the values indicated. A checkbox can be set to TRUE
   *   to be checked and should be set to FALSE to be unchecked.
   * @param string $submit
   *   Value of the submit button whose click is to be emulated. For example,
   *   'Save'. The processing of the request depends on this value. For example,
   *   a form may have one button with the value 'Save' and another button with
   *   the value 'Delete', and execute different code depending on which one is
   *   clicked.
   * @param array $route_parameters
   *   (optional) An associative array of route parameter names and values.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The undecoded AjaxResponse object returned by the http_kernel.
   *
   * @see \Drupal\Tests\UiHelperTrait::submitForm()
   */
  protected function postAjaxForm(array $edit, $submit, array $route_parameters = []): AjaxResponse {
    // Get a path appended with ?ajax_form=1&_wrapper_format=drupal_ajax.
    $this->propagateRouteParameters($route_parameters);
    $clean_path = $this->getPath($route_parameters, []);

    $path = $this->getPath($route_parameters, [
      'query' => [
        '_wrapper_format' => 'drupal_modal',
        'ajax_form' => 1,
      ],
    ]);

    $this->drupalGet($clean_path);

    $form_id_input = $this->assertSession()->elementExists(
      'named',
      ['id_or_name', 'form_id']
    );
    $form_token_input = $this->assertSession()->elementExists(
      'named',
      ['id_or_name', 'form_token']
    );

    $edit['form_id'] = $form_id_input->getAttribute('value');
    self::assertNotEmpty($edit['form_id']);
    $edit['form_token'] = $form_token_input->getAttribute('value');
    self::assertNotEmpty($edit['form_token']);
    $edit['op'] = $submit;
    $edit['confirm'] = "1";
    $edit['_drupal_ajax'] = "1";

    $req = Request::create(
      $path,
      'POST',
      $edit,
      $this->getSessionCookies()->toArray()
    );
    $req->headers->set('X-Requested-With', 'XMLHttpRequest');
    $req->headers->set('Accept', 'application/json, text/javascript, */*; q=0.01');
    self::assertTrue($req->request->has('form_id'));
    $req->attributes->add($edit);

    // Fetch the response from http_kernel and assert its sane.
    $response = $this
      ->container
      ->get('http_kernel')
      ->handle($req, HttpKernelInterface::SUB_REQUEST);

    $this->assertSame(200, $response->getStatusCode(), (string) $response->getContent());
    $this->assertInstanceOf(AjaxResponse::class, $response);

    return $response;
  }

  /**
   * Determine whether the test should return Ajax properties or not.
   *
   * @return bool
   *   Whether the test should return Ajax properties or not.
   */
  protected function shouldReturnAjaxProperties(): bool {
    return TRUE;
  }

  /**
   * Tests that forms have the Ajax dialog library loaded.
   */
  public function testAjaxDialog(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->getPath());
    $session = $this->getSession();
    if ($this->shouldReturnAjaxProperties()) {
      $libraries = $session->evaluateScript('drupalSettings.ajaxPageState.libraries');
      $this->assertStringContainsString('core/drupal.dialog.ajax', $libraries);
    }
  }

  /**
   * Presses a button in the dialog.
   *
   * Drupal hides the original buttons and places them in a special area of
   * the dialog. This ensures the proper button is clicked and assertions do not
   * error on clicking a hidden button.
   *
   * @param string $locator
   *   The button locator.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function pressDialogButton(string $locator): void {
    $this->assertSession()->elementExists('css', '.ui-dialog-buttonpane')->pressButton($locator);
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Visits the dashboard to interact with forms.
   */
  protected function visitDashboard(): void {
    $this->drupalGet(Url::fromRoute('purge_ui.dashboard'));
    $queue_details = $this->assertSession()->elementExists('css', 'summary:contains("Queue")');
    $queue_details->click();
  }

  protected function toggleDropbutton(string $primary_action): void {
    $link = $this->assertSession()->elementExists('named', ['link', $primary_action]);
    $dropbutton = $link->getParent()->getParent()->getParent();
    self::assertEquals('div', $dropbutton->getTagName());
    self::assertTrue($dropbutton->hasClass('dropbutton-widget'), $dropbutton->getHtml());
    $dropbutton->find('css', 'li.dropbutton-toggle')->click();
  }

}

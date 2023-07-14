<?php

namespace Drupal\Tests\login_destination\Functional;

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\login_destination\Entity\LoginDestination;
use Drupal\Tests\login_destination\Traits\LoginDestinationCreationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests redirects.
 *
 * @group login_destination
 */
class RedirectTest extends BrowserTestBase {

  use LoginDestinationCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['login_destination', 'node'];

  /**
   * The account logging in or out.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create a node page to redirect to.
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);
    $this->drupalCreateNode();

    // Create an user to test with.
    $this->account = $this->drupalCreateUser(['access content']);
  }

  /**
   * Creates a login destination to /node/1.
   *
   * @param array $triggers
   *   The triggers to activate for the redirect.
   */
  protected function createLoginDestinationToNode1(array $triggers = []) {
    $this->createLoginDestinationRule([
      'triggers' => $triggers,
      'destination_path' => 'internal:/node/1',
    ]);
  }

  /**
   * Overrides UiHelperTrait::drupalLogout() to skip certain checks.
   *
   * These checks confirm a logout by checking if you get on the login page.
   * This won't happen if there is a redirect to a different page is supposed to
   * happen after logging out.
   */
  protected function drupalLogout() {
    // Make a request to the logout page.
    $this->drupalGet('user/logout');

    // @see BrowserTestBase::drupalUserIsLoggedIn()
    unset($this->loggedInUser->sessionId);
    $this->loggedInUser = FALSE;
    \Drupal::currentUser()->setAccount(new AnonymousUserSession());
  }

  /**
   * Registers a new user.
   *
   * @param string $password
   *   (optional) The password for the new user.
   *
   * @return \Drupal\user\Entity\User
   *   The user that was registered.
   */
  protected function register($password = NULL) {
    $name = $this->randomMachineName();
    $mail = $name . '@example.com';

    $edit = [
      'name' => $name,
      'mail' => $mail,
    ];
    if ($password) {
      $edit += [
        'pass[pass1]' => $password,
        'pass[pass2]' => $password,
      ];
    }
    $this->drupalGet('user/register');
    $this->submitForm($edit, 'Create new account');

    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $storage->resetCache();
    $accounts = $storage->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);

    return $new_user;
  }

  /**
   * Tests redirecting after login.
   */
  public function testRedirectAfterLogin() {
    $this->createLoginDestinationToNode1([LoginDestination::TRIGGER_LOGIN]);

    $this->drupalLogin($this->account);

    // Ensure that the redirect happened.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/node/1');
  }

  /**
   * Tests no redirect after logging in without compatible destination rule.
   *
   * When there are login destination rules, but none of them has a trigger on
   * login configured, no redirect caused by a login destination rule should
   * happen on login.
   */
  public function testNoRedirectAfterLogin() {
    $this->createLoginDestinationToNode1([
      LoginDestination::TRIGGER_REGISTRATION,
      LoginDestination::TRIGGER_ONE_TIME_LOGIN,
      LoginDestination::TRIGGER_LOGOUT,
    ]);

    $this->drupalLogin($this->account);

    // Ensure that no login destination redirect happened.
    $this->assertSession()->addressEquals('/user/2');
  }

  /**
   * Tests redirecting after registering without email verification.
   */
  public function testRedirectAfterRegistering() {
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    $this->createLoginDestinationToNode1([LoginDestination::TRIGGER_REGISTRATION]);

    $this->register(\Drupal::service('password_generator')->generate());
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');

    // Ensure that the redirect happened.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/node/1');
  }

  /**
   * Tests no redirect after registering in without compatible destination rule.
   *
   * When there are login destination rules, but none of them has a trigger on
   * registration configured, no redirect caused by a login destination rule
   * should happen after registration.
   */
  public function testNoRedirectAfterRegistering() {
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    $this->createLoginDestinationToNode1([
      LoginDestination::TRIGGER_LOGIN,
      LoginDestination::TRIGGER_ONE_TIME_LOGIN,
      LoginDestination::TRIGGER_LOGOUT,
    ]);

    $this->register(\Drupal::service('password_generator')->generate());
    $this->assertSession()->pageTextContains('Registration successful. You are now logged in.');

    // Ensure that no login destination redirect happened.
    $this->assertSession()->addressEquals('/user/3');
  }

  /**
   * Tests redirecting after one-time login and setting password.
   */
  public function testRedirectAfterOneTimeLoginAndSettingPassword() {
    $this->createLoginDestinationToNode1([LoginDestination::TRIGGER_ONE_TIME_LOGIN]);

    // Generate password reset URL.
    $url = user_pass_reset_url($this->account);
    $this->drupalGet($url);
    // And use the one-time login link.
    $this->submitForm(NULL, 'Log in');
    $this->assertSession()->pageTextContains('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.');
    $this->assertSession()->titleEquals(strtr('@name | @site', [
      '@name' => $this->account->getAccountName(),
      '@site' => $this->config('system.site')->get('name'),
    ]));

    // Set a new password.
    $password = \Drupal::service('password_generator')->generate();
    $edit = [
      'pass[pass1]' => $password,
      'pass[pass2]' => $password,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Assert that the redirect has happened now.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/node/1');
  }

  /**
   * Tests no redirect after one-time login without compatible destination rule.
   *
   * When there are login destination rules, but none of them has a trigger on
   * an one-time login configured, no redirect caused by a login destination
   * rule should happen after one-time login and setting password.
   */
  public function testNoRedirectAfterOneTimeLoginAndSettingPassword() {
    $this->createLoginDestinationToNode1([
      LoginDestination::TRIGGER_LOGIN,
      LoginDestination::TRIGGER_REGISTRATION,
      LoginDestination::TRIGGER_LOGOUT,
    ]);

    // Generate password reset URL.
    $url = user_pass_reset_url($this->account);
    $this->drupalGet($url);
    // And use the one-time login link.
    $this->submitForm(NULL, 'Log in');
    $this->assertSession()->pageTextContains('You have just used your one-time login link. It is no longer necessary to use this link to log in. Please change your password.');
    $this->assertSession()->titleEquals(strtr('@name | @site', [
      '@name' => $this->account->getAccountName(),
      '@site' => $this->config('system.site')->get('name'),
    ]));

    // Set a new password.
    $password = \Drupal::service('password_generator')->generate();
    $edit = [
      'pass[pass1]' => $password,
      'pass[pass2]' => $password,
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Ensure that no login destination redirect happened.
    $this->assertSession()->addressEquals('/user/2/edit');
  }

  /**
   * Tests redirecting immediately after one-time login.
   */
  public function testRedirectImmediatelyAfterOneTimeLogin() {
    $this->config('login_destination.settings')
      ->set('immediate_redirect', TRUE)
      ->save();

    $this->createLoginDestinationToNode1([LoginDestination::TRIGGER_ONE_TIME_LOGIN]);

    // Generate password reset URL.
    $url = user_pass_reset_url($this->account);
    $this->drupalGet($url);
    // And use the one-time login link.
    $this->submitForm(NULL, 'Log in');

    // Assert that the redirect happened immediately.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/node/1');
  }

  /**
   * Tests no redirect after one-time login without compatible destination rule.
   *
   * When there are login destination rules and when the setting "Immediate
   * redirect" is enabled, but none of destination rules has a trigger on an
   * one-time login configured, no redirect caused by a login destination rule
   * should happen immediately after one-time login.
   */
  public function testNoRedirectImmediatelyAfterOneTimeLogin() {
    $this->config('login_destination.settings')
      ->set('immediate_redirect', TRUE)
      ->save();

    $this->createLoginDestinationToNode1([
      LoginDestination::TRIGGER_LOGIN,
      LoginDestination::TRIGGER_REGISTRATION,
      LoginDestination::TRIGGER_LOGOUT,
    ]);

    // Generate password reset URL.
    $url = user_pass_reset_url($this->account);
    $this->drupalGet($url);
    // And use the one-time login link.
    $this->submitForm(NULL, 'Log in');

    // Ensure that no login destination redirect happened.
    $this->assertSession()->addressEquals('/user/2/edit');
  }

  /**
   * Tests no redirect when updating account and not using one-time login link.
   */
  public function testNoRedirectAfterUpdatingAccountWithoutLoginLink() {
    // Create a login destination rule that triggers upon one-time login links.
    $this->createLoginDestinationToNode1([LoginDestination::TRIGGER_ONE_TIME_LOGIN]);

    // Login normally.
    $this->drupalLogin($this->account);

    // Set password on account edit page.
    $password = \Drupal::service('password_generator')->generate();
    $edit = [
      'current_pass' => $this->account->passRaw,
      'pass[pass1]' => $password,
      'pass[pass2]' => $password,
    ];
    $this->drupalGet('user/2/edit');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The changes have been saved.');

    // Assert that the user is still on their account edit page.
    $this->assertSession()->addressEquals('/user/2/edit');
  }

  /**
   * Tests redirecting after logging out.
   */
  public function testRedirectAfterLogout() {
    $this->createLoginDestinationToNode1([LoginDestination::TRIGGER_LOGOUT]);

    $this->drupalLogin($this->account);
    $this->assertSession()->addressEquals('/user/2');
    $this->drupalLogout();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/node/1');
  }

  /**
   * Tests no redirect after logging out without compatible destination rule.
   *
   * When there are login destination rules, but none of them has a trigger on
   * logout configured, no redirect caused by a login destination rule should
   * happen on logout.
   */
  public function testNoRedirectAfterLogout() {
    $this->createLoginDestinationToNode1([
      LoginDestination::TRIGGER_LOGIN,
      LoginDestination::TRIGGER_REGISTRATION,
      LoginDestination::TRIGGER_ONE_TIME_LOGIN,
    ]);

    $this->drupalLogin($this->account);
    $this->drupalGet('/user/2');
    $this->drupalLogout();

    // Ensure that no login destination redirect happened.
    $this->assertSession()->addressEquals('/');
  }

}

<?php

namespace Drupal\Tests\tfa\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\user\Entity\User;

/**
 * Tests for the tfa login process.
 *
 * @group Tfa
 */
class TfaPasswordResetTest extends TfaTestBase {

  use AssertMailTrait {
    getMails as drupalGetMails;
  }

  /**
   * User doing the TFA Validation.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * Administrator to handle configurations.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Super administrator to edit other users TFA.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $superAdmin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();

    $this->webUser = $this->drupalCreateUser(['setup own tfa']);
    $this->adminUser = $this->drupalCreateUser(
      [
        'admin tfa settings',
        'setup own tfa',
      ]
    );
    $this->superAdmin = User::load(1);
    $this->canEnableValidationPlugin('tfa_totp');
  }

  /**
   * Tests the tfa one time login process.
   */
  public function testTfaOneTimeLogin() {
    $assert_session = $this->assertSession();

    // Enable TFA for all authenticated user roles.
    $this->drupalLogin($this->adminUser);
    $edit = [
      'tfa_required_roles[authenticated]' => TRUE,
    ];
    $this->drupalGet('admin/config/people/tfa');
    $this->submitForm($edit, 'Save configuration');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->drupalLogout();

    // Check that tfa is presented while resetting password as an admin user.
    // login via the one time login URL.
    $this->resetPassword($this->adminUser);
    // Change the password.
    $this->changePassword($assert_session);

    // Check that tfa is presented while resetting password as a normal user.
    // login via the one time login URL.
    $this->resetPassword($this->webUser);
    // Change the password.
    $this->changePassword($assert_session);

    // Check that the super admin user can not bypass TFA
    // when resetting the password.
    // Login via the one time login URL.
    $this->resetPassword($this->superAdmin);
    // Change the password.
    $this->changePassword($assert_session, FALSE);

    // Check that the super admin user can bypass TFA
    // when resetting the password,
    // If Admin TFA exemption is true.
    // Enable admin TFA exemption,.
    $this->drupalGet('admin/config/people/tfa');
    $edit = [
      'reset_pass_skip_enabled' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('The configuration options have been saved.');
    $this->drupalLogout();
    // Login via the one time login URL.
    $this->resetPassword($this->superAdmin);
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextNotContains('You are required to setup two-factor authentication. You have');
    // Change the password.
    $password = \Drupal::service('password_generator')->generate();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->submitForm($edit, 'Save');
    $assert_session->pageTextContains('The changes have been saved.');

  }

  /**
   * Retrieves password reset email and extracts the login link.
   */
  public function getResetUrl() {
    // Assume the most recent email.
    $_emails = $this->drupalGetMails();
    $email = end($_emails);
    $urls = [];
    preg_match('#.+user/reset/.+#', $email['body'], $urls);
    $path = parse_url($urls[0], PHP_URL_PATH);
    $reset_path = substr($path, strpos($path, 'user/reset/'));

    return $reset_path;
  }

  /**
   * Reset password login process.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user who need to reset the password.
   */
  public function resetPassword(User $user) {
    $this->drupalGet('user/password');
    $edit = ['name' => $user->getAccountName()];
    $this->submitForm($edit, 'Submit');
    // Get the one time reset URL form the email.
    $resetURL = $this->getResetURL() . '/login';
    // Login via one time login URL
    // and check if the TFA presented.
    $this->drupalGet($resetURL);
  }

  /**
   * Action to change user own password.
   *
   * @param mixed $assert_session
   *   Web assert object.
   * @param bool $logout
   *   If ture, logout the user at the end.
   */
  public function changePassword($assert_session, $logout = TRUE) {
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('You are required to setup two-factor authentication. You have');
    // Change the password.
    $password = \Drupal::service('password_generator')->generate();
    $edit = ['pass[pass1]' => $password, 'pass[pass2]' => $password];
    $this->submitForm($edit, 'Save');
    $assert_session->pageTextContains('The changes have been saved.');
    if ($logout) {
      $this->drupalLogout();
    }
  }

}

<?php

namespace Drupal\Tests\login_redirect_per_role\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests redirects after login and logout.
 *
 * @group login_redirect_per_role
 */
class RedirectTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['login_redirect_per_role_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * A user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user2;

  /**
   * A user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user3;

  /**
   * A user entity.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user4;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $role1 = $this->drupalCreateRole([]);
    $role2 = $this->drupalCreateRole([]);
    $role3 = $this->drupalCreateRole([]);
    $role4 = $this->drupalCreateRole([]);
    $this->user1 = $this->createUser([], NULL, FALSE, ['roles' => $role1]);
    $this->user2 = $this->createUser([], NULL, FALSE, ['roles' => $role2]);
    $this->user3 = $this->createUser([], NULL, FALSE, ['roles' => $role3]);
    $this->user4 = $this->createUser([], NULL, FALSE, ['roles' => $role4]);

    $this->config('login_redirect_per_role.settings')
      ->set("login.$role1", [
        'allow_destination' => TRUE,
        'redirect_url' => '/login-url-role1',
        'weight' => 0,
      ])
      ->set("logout.$role1", [
        'allow_destination' => TRUE,
        'redirect_url' => '/logout-url-role1',
        'weight' => 0,
      ])
      ->set("login.$role2", [
        'allow_destination' => FALSE,
        'redirect_url' => '/login-url-role2',
        'weight' => 0,
      ])
      ->set("logout.$role2", [
        'allow_destination' => FALSE,
        'redirect_url' => '/logout-url-role2',
        'weight' => 0,
      ])
      ->set("login.$role3", [
        'allow_destination' => FALSE,
        'redirect_url' => '[site:valid-path]',
        'weight' => 0,
      ])
      ->set("logout.$role3", [
        'allow_destination' => FALSE,
        'redirect_url' => '[site:invalid-path]',
        'weight' => 0,
      ])
      ->set("login.$role4", [
        'allow_destination' => FALSE,
        'redirect_url' => '<front>',
        'weight' => 0,
      ])
      ->set("logout.$role4", [
        'allow_destination' => FALSE,
        'redirect_url' => '<front>',
        'weight' => 0,
      ])
      ->save();

    $this->state = $this->container->get('state');
  }

  /**
   * Test access to the settings form.
   */
  public function testLoginAndLogoutRedirectUrl() {
    $this->drupalLogin($this->user1);
    $this->assertSession()->addressEquals('login-url-role1');

    $this->drupalGet(Url::fromRoute('user.logout'));
    $this->assertSession()->addressEquals('logout-url-role1');

    $this->drupalLogin($this->user2);
    $this->assertSession()->addressEquals('login-url-role2');

    $this->drupalGet(Url::fromRoute('user.logout'));
    $this->assertSession()->addressEquals('logout-url-role2');

    // Test redirect with token.
    $this->drupalLogin($this->user3);
    $this->assertSession()->addressEquals('valid-path');

    $this->drupalGet(Url::fromRoute('user.logout'));
    $this->assertSession()->addressEquals('invalid-path');

    // Test <front> as destination URL.
    $this->drupalLogin($this->user4);
    // The default front page is /user/login so the user is redirected to it's
    // own user page.
    $this->assertSession()->addressEquals('user/' . $this->user4->id());

    $this->drupalGet(Url::fromRoute('user.logout'));
    $this->assertSession()->addressEquals('');
  }

  /**
   * Test allow_destination option.
   */
  public function testDestinationUrl() {
    // Test redirect with allow_destination enabled.
    $this->drupalGet(Url::fromRoute('user.login', [], ['query' => ['destination' => 'destination-url']]));
    $this->submitForm([
      'name' => $this->user1->getAccountName(),
      'pass' => $this->user1->passRaw,
    ], 'Log in');
    $this->assertSession()->addressEquals('destination-url');

    $this->drupalGet(Url::fromRoute('user.logout', [], ['query' => ['destination' => 'destination-url']]));
    $this->assertSession()->addressEquals('destination-url');

    // Test redirect with allow_destination disabled.
    $this->drupalGet(Url::fromRoute('user.login', [], ['query' => ['destination' => 'destination-url']]));
    $this->submitForm([
      'name' => $this->user2->getAccountName(),
      'pass' => $this->user2->passRaw,
    ], 'Log in');
    $this->assertSession()->addressEquals('login-url-role2');

    $this->drupalGet(Url::fromRoute('user.logout', [], ['query' => ['destination' => 'destination-url']]));
    $this->assertSession()->addressEquals('logout-url-role2');
  }

  /**
   * Test that user_login and logout hook still works with a high weight.
   *
   * This test case checks that hook_user_login and hook_user_logout that are
   * executed after hook_user_login and hook_user_logout defined in
   * login_redirect_per_role still work.
   */
  public function testUserLoginAndUserLogoutHookExecution() {
    $this->state->set('login_redirect_per_role_test.user_login_called', FALSE);
    $this->state->set('login_redirect_per_role_test.user_logout_called', FALSE);

    $this->drupalLogin($this->user1);
    $this->assertEquals(TRUE, $this->state->get('login_redirect_per_role_test.user_login_called'));

    $this->drupalGet(Url::fromRoute('user.logout'));
    $this->assertEquals(TRUE, $this->state->get('login_redirect_per_role_test.user_logout_called'));
  }

}

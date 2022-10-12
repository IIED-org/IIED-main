<?php

namespace Drupal\Tests\redirect_after_login\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;

/**
 * Tests for admin-related functionality.
 *
 * @group redirect_after_login
 */
class AdminTest extends BrowserTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'redirect_after_login',
  ];

  /**
   * Account with admin-level privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Account with editor-level privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * Account with authenticated-level privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $simpleUser;

  /**
   * Role that grants admin-level privileges.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $adminRole;

  /**
   * Role that grants editor-level privileges.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $editorRole;

  /**
   * Tests access control for the admin settings path.
   */
  public function testAdminSettingsPathAccess() {
    $this->checkUserGetsCode($this->simple_user, 403);
    $this->checkUserGetsCode($this->editor_user, 200);
    $this->checkUserGetsCode($this->admin_user, 200);
    $this->checkUserGetsCode($this->simple_user, 403);

    $this->drupalLogin($this->admin_user);
    $this->drupalGet('admin/people/permissions');
    $this->assertText('Administer redirect_after_login settings', 'Permission is available in the permissionsn form.');
  }

  /**
   * Checks that the given user gets a given status code.
   *
   * @param \Drupal\user\UserInterface $user
   *   User to login as, before loading the admin path.
   * @param int $status_code
   *   HTTP status code that is expected, e.g. 200.
   */
  protected function checkUserGetsCode(UserInterface $user, $status_code) {
    $this->drupalLogin($user);
    $this->drupalGet('admin/config/system/redirect');
    $this->assertSession()->statusCodeEquals($status_code);
    $this->drupalLogout();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // TODO: setup tasks here.
    $this->admin_role = Role::create([
      'id'    => 'administrator',
      'label' => 'Administrator',
    ]);
    // This role gets all permissions.
    $this->admin_role->set('is_admin', TRUE)->save();

    $this->editor_role = Role::create([
      'id'    => 'editor',
      'label' => 'Editor',
    ]);
    $this->editor_role
      ->grantPermission('administer redirect_after_login settings')
      ->save();

    $this->admin_user = $this->drupalCreateUser();
    $this->admin_user->setUsername('admin_user');
    $this->admin_user->addRole($this->admin_role->id());
    $this->admin_user->save();

    $this->editor_user = $this->drupalCreateUser();
    $this->editor_user->setUsername('editor_user');
    $this->editor_user->addRole($this->editor_role->id());
    $this->editor_user->save();

    $this->simple_user = $this->drupalCreateUser();
  }

}

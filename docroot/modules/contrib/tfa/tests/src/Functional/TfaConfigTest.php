<?php

namespace Drupal\Tests\tfa\Functional;

/**
 * Tests the Tfa UI.
 *
 * @group Tfa
 */
class TfaConfigTest extends TfaTestBase {
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
   * {@inheritdoc}
   */
  protected static $modules = [
    'tfa_test_plugins',
    'tfa',
    'encrypt',
    'encrypt_test',
    'key',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    // Enable TFA module and the test module.
    parent::setUp();
    $this->webUser = $this->drupalCreateUser(['setup own tfa']);
    $this->adminUser = $this->drupalCreateUser(['admin tfa settings']);
  }

  /**
   * Test the access to the configuration form based on module permissions.
   */
  public function testTfaConfigFormAccess() {
    $assert = $this->assertSession();

    // Check that config form is restricted for users.
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/config/people/tfa');
    $assert->statusCodeEquals(403);

    // Check that config form is accessible to admins.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/tfa');
    $assert->statusCodeEquals(200);
  }

  /**
   * Test to check if configurations are working as desired.
   */
  public function testTfaConfigForm() {
    $this->canEnableValidationPlugin('tfa_test_plugins_validation');
  }

}

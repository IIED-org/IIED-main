<?php

namespace Drupal\Tests\tfa\Functional;

/**
 * Class TfaRecoveryCodeSetupPluginTest.
 *
 * @group tfa
 *
 * @ingroup Tfa
 */
class TfaRecoveryCodePluginTest extends TfaTestBase {

  /**
   * Plugin id for the recovery code validation plugin.
   *
   * @var string
   */
  protected $validationPluginId = 'tfa_recovery_code';

  /**
   * Non-admin user account. Standard tfa user.
   *
   * @var \Drupal\user\Entity\User
   */
  public $userAccount;

  /**
   * Setup plugin manager.
   *
   * @var \Drupal\tfa\TfaSetupPluginManager
   */
  public $tfaSetupManager;

  /**
   * Validation plugin manager.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  public $tfaValidationManager;

  /**
   * Instance of the setup plugin for the $validationPluginId.
   *
   * @var \Drupal\tfa\Plugin\TfaSetup\TfaRecoveryCodeSetup
   */
  public $setupPlugin;

  /**
   * Instance of the validation plugin for the $validationPluginId.
   *
   * @var \Drupal\tfa\Plugin\TfaValidation\TfaRecoveryCode
   */
  public $validationPlugin;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $config = $this->config('tfa.settings');
    $config->set('enabled', TRUE)
      ->set('default_validation_plugin', $this->validationPluginId)
      ->set('allowed_validation_plugins', [$this->validationPluginId => $this->validationPluginId])
      ->set('encryption', $this->encryptionProfile->id())
      ->set('required_roles', ['authenticated' => 'authenticated'])
      ->set('validation_plugin_settings', [
        $this->validationPluginId => [
          'recovery_codes_amount' => 10,
        ],
      ])
      ->save();

    $permissions = ['setup own tfa', 'disable own tfa'];
    $this->userAccount = $this->createUser($permissions);

    $this->tfaSetupManager = \Drupal::service('plugin.manager.tfa.setup');
    $this->setupPlugin = $this->tfaSetupManager->createInstance($this->validationPluginId . '_setup', ['uid' => $this->userAccount->id()]);

    $this->tfaValidationManager = \Drupal::service('plugin.manager.tfa.validation');
    $this->validationPlugin = $this->tfaValidationManager->createInstance($this->validationPluginId, ['uid' => $this->userAccount->id()]);
  }

  /**
   * Test that we can enable the plugin.
   */
  public function testEnableValidationPlugin() {
    $this->canEnableValidationPlugin($this->validationPluginId);
  }

  /**
   * Check that recovery code plugin appear on the user overview page.
   */
  public function testRecoveryCodeOverviewExists() {
    $this->drupalLogin($this->userAccount);
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Recovery Codes');
  }

  /**
   * Check that the user can setup recovery codes.
   */
  public function testRecoveryCodeSetup() {
    $this->drupalLogin($this->userAccount);
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa/' . $this->validationPluginId . '/1');
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->responseContains('Enter your current password');

    // Provide the user's password to continue.
    $edit = ['current_pass' => $this->userAccount->passRaw];
    $this->submitForm($edit, 'Confirm');

    $assert->responseContains('Save codes to account');
    $this->submitForm([], 'Save codes to account');
    $assert->pageTextContains('TFA setup complete.');

    // Make sure codes were saved to the account.
    $codes = $this->validationPlugin->getCodes();
    $this->assertTrue(!empty($codes), 'No codes saved to the account data.');

    // Now the user should be able to see their existing codes. Let's test that.
    $assert->linkExists('Show codes');
    $this->drupalGet('user/' . $this->userAccount->id() . '/security/tfa/' . $this->validationPluginId);

    $edit = ['current_pass' => $this->userAccount->passRaw];
    $this->submitForm($edit, 'Confirm');
    $assert->statusCodeEquals(200);
    // The "save" button should not exists when viewing existing codes.
    $assert->responseNotContains('Save codes to account');
  }

  /**
   * Check that the user can login with recovery codes.
   */
  public function testRecoveryCodeValidation() {
    // Login the user, generate and save some codes, then log back out.
    $this->drupalLogin($this->userAccount);
    $assert = $this->assertSession();

    $codes = $this->validationPlugin->generateCodes();
    $this->validationPlugin->storeCodes($codes);
    $this->drupalLogout();

    // Password form.
    $edit = [
      'name' => $this->userAccount->getAccountName(),
      'pass' => $this->userAccount->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Enter one of your recovery codes');

    // Try an invalid code.
    $edit = ['code' => 'definitely not real'];
    $this->submitForm($edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid recovery code.');

    // Try a valid code.
    $edit['code'] = $codes[0];
    $this->submitForm($edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains($this->userAccount->getDisplayName());
    $this->assertTrue($this->userAccount->isAuthenticated(), 'User is logged in.');

    // Try replay attack with a valid code that has already been used.
    $this->drupalLogout();
    $edit = [
      'name' => $this->userAccount->getAccountName(),
      'pass' => $this->userAccount->passRaw,
    ];
    $this->drupalGet('user/login');
    $this->submitForm($edit, 'Log in');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Enter one of your recovery codes');

    $edit = ['code' => $codes[0]];
    $this->submitForm($edit, 'Verify');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Invalid recovery code.');
  }

}

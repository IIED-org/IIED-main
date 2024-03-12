<?php

namespace Drupal\Tests\tfa\Functional;

use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\key\Entity\Key;
use Drupal\Tests\BrowserTestBase;

/**
 * Base class for testing the Tfa module.
 */
abstract class TfaTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A test key.
   *
   * @var \Drupal\key\Entity\Key
   */
  protected $testKey;

  /**
   * An encryption profile.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile
   */
  protected $encryptionProfile;

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
  protected function setUp(): void {
    parent::setUp();

    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer encrypt',
      'administer keys',
    ]);
    $this->drupalLogin($user);
    $this->generateEncryptionKey();
    $this->generateEncryptionProfile();
  }

  /**
   * Generates an encryption key.
   */
  protected function generateEncryptionKey() {
    $key = Key::create([
      'id' => 'testing_key_128',
      'label' => 'Testing Key 128 bit',
      'key_type' => 'encryption',
      'key_type_settings' => ['key_size' => '128'],
      'key_provider' => 'config',
      'key_provider_settings' => ['key_value' => 'mustbesixteenbit'],
    ]);
    $key->save();
    $this->testKey = $key;
  }

  /**
   * Generates an Encryption profile.
   */
  protected function generateEncryptionProfile() {
    $encryption_profile = EncryptionProfile::create([
      'id' => 'test_encryption_profile',
      'label' => 'Test encryption profile',
      'encryption_method' => 'test_encryption_method',
      'encryption_key' => $this->testKey->id(),
    ]);
    $encryption_profile->save();
    $this->encryptionProfile = $encryption_profile;
  }

  /**
   * Reusable test for enabling a validation plugin on the configuration form.
   *
   * @param string $validation_plugin_id
   *   A validation plugin id.
   */
  protected function canEnableValidationPlugin($validation_plugin_id) {
    $assert = $this->assertSession();
    $adminUser = $this->drupalCreateUser(['admin tfa settings']);
    $this->drupalLogin($adminUser);

    $this->drupalGet('admin/config/people/tfa');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('TFA Settings');

    $edit = [
      'tfa_enabled' => TRUE,
      'tfa_validate' => $validation_plugin_id,
      "tfa_allowed_validation_plugins[{$validation_plugin_id}]" => $validation_plugin_id,
      'encryption_profile' => $this->encryptionProfile->id(),
    ];

    $this->submitForm($edit, 'Save configuration');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('The configuration options have been saved.');
    $select_field_id = 'edit-tfa-validate';
    $option_field = $assert->optionExists($select_field_id, $validation_plugin_id);
    $result = $option_field->hasAttribute('selected');
    $this->assertTrue($result, "Option {$validation_plugin_id} for field {$select_field_id} is selected.");
  }

}

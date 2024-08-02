<?php

namespace Drupal\Tests\tfa\Unit\Plugin\TfaValidation;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tfa\Plugin\TfaValidation\TfaTotpValidation;
use Drupal\user\UserDataInterface;
use Otp\Otp;
use ParagonIE\ConstantTime\Encoding;

/**
 * Unit test for TfaTotpValidation plugin.
 *
 * @group tfa
 *
 * @covers \Drupal\tfa\Plugin\TfaValidation\TfaTotpValidation
 * @covers \Drupal\tfa\Plugin\TfaBasePlugin
 */
class TfaTotpTest extends UnitTestCase {

  /**
   * Mocked user data service.
   *
   * @var \Drupal\user\UserDataInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userData;

  /**
   * Mocked encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $encryptionProfileManager;

  /**
   * The mocked encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $encryptionService;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The mocked TFA settings.
   *
   * @var array
   */
  protected $tfaSettings = [];

  /**
   * A mocked encryption profile.
   *
   * @var \Drupal\encrypt\EncryptionProfileInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $encryptionProfile;

  /**
   * A mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Default configuration for the plugin.
   *
   * @var array
   */
  protected $configuration = [
    'uid' => 3,
  ];

  /**
   * Lock service mock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $lockMock;

  /**
   * Time that will be simulated for the request.
   *
   * @var int
   */
  protected int $requestTime;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Stub out default mocked services.
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->encryptionProfileManager = $this->createMock(EncryptionProfileManagerInterface::class);
    $this->encryptionService = $this->createMock(EncryptServiceInterface::class);
    $this->configFactory = $this->getConfigFactoryStub();
    $this->encryptionProfile = $this->createMock(EncryptionProfileInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);

    $this->lockMock = $this->createMock(LockBackendInterface::class);
    $this->lockMock->method('acquire')->willReturn(TRUE);

    $site_settings['hash_salt'] = $this->randomMachineName();
    new Settings($site_settings);

    $this->requestTime = time();
  }

  /**
   * Helper method to construct the test fixture.
   *
   * @return \Drupal\tfa\Plugin\TfaValidation\TfaTotpValidation
   *   TfaTotpValidation code.
   *
   * @throws \Exception
   */
  protected function getFixture() {

    $time_mock = $this->createMock(TimeInterface::class);
    $time_mock->method('getRequestTime')->willReturnReference($this->requestTime);

    // The plugin calls out to the global \Drupal object, so mock that here.
    $this->configFactory = $this->getConfigFactoryStub(['tfa.settings' => $this->tfaSettings]);
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('datetime.time', $time_mock);
    \Drupal::setContainer($container);

    return new TfaTotpValidation(
      $this->configuration,
      'tfa_totp',
      [],
      $this->userData,
      $this->encryptionProfileManager,
      $this->encryptionService,
      $this->configFactory,
      $time_mock,
      $this->lockMock
    );
  }

  /**
   * Code validation and replay attack prevention.Test for Replay Attacks.
   */
  public function testTotpCodeValidation(): void {
    // No codes, means it isn't ready.
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->ready());

    // Fake some codes for user 3.
    $otp_generator = new Otp();
    $seed = 'AR5BPJL24MYUHGBU';
    $last_accepted_window = 1;

    $user_data_map = [
      ['tfa', 3, 'tfa_totp_seed', ['seed' => base64_encode('foo')]],
      ['tfa', 3, 'tfa_totp_time_window', &$last_accepted_window],
    ];

    $this->userData->method('get')->willReturnMap($user_data_map);

    $this->encryptionService->method('decrypt')->with('foo', $this->anything())->willReturn($seed);

    $this->tfaSettings = [
      'validation_plugin_settings.tfa_recovery_code.recovery_codes_amount' => 10,
      'encryption' => 'foo',
      'default_validation_plugin' => 'bar',
    ];

    $this->encryptionProfileManager->method('getEncryptionProfile')->with('foo')->willReturn($this->encryptionProfile);
    $fixture = $this->getFixture();
    $this->assertTrue($fixture->ready());

    // Test a code that is from the previous time window.
    $test_code = $otp_generator->totp(Encoding::base32DecodeUpper($seed), (int) floor($this->requestTime / 30) - 1);
    $this->assertTrue($fixture->validateRequest((int) $test_code), 'Code from previous window accepted');

    // Test a code that is from the next time window.
    $test_code = $otp_generator->totp(Encoding::base32DecodeUpper($seed), (int) floor($this->requestTime / 30) + 1);
    $this->assertTrue($fixture->validateRequest((int) $test_code), 'Code from next window accepted');

    // Generate a code to be used for the rest of the tests.
    $test_code = $otp_generator->totp(Encoding::base32DecodeUpper($seed), (int) floor(($this->requestTime / 30)));

    // Test a valid token.
    $this->assertTrue($fixture->validateRequest((int) $test_code), 'Valid token accepted');

    // Token is older than current window.
    $last_accepted_window = floor(($this->requestTime / 30)) + 5;
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Token is from the past');

    // Token is in current window.
    $last_accepted_window = floor($this->requestTime / 30);
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Token is from same time as last accepted token');

    // Reset the time window so it does not interfere.
    $last_accepted_window = 1;

    // Test the default sha256 storage.
    $hash = Crypt::hashBase64($test_code);
    $key = 'tfa_accepted_code_' . $hash;
    $user_data_map[] = ['tfa', 3, $key, $this->requestTime];
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->userData->method('get')->willReturnMap($user_data_map);
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Token stored sha256');
    array_pop($user_data_map);

    // Test the older hash_salt existing site storage.
    $hash = Crypt::hashBase64(Settings::getHashSalt() . $test_code);
    $key = 'tfa_accepted_code_' . $hash;
    $user_data_map[] = ['tfa', 3, $key, $this->requestTime];
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->userData->method('get')->willReturnMap($user_data_map);
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Token stored using hash_salt');
    array_pop($user_data_map);

    // Test with a previous_hash_salts record.
    $old_hash = $this->randomMachineName();
    $site_settings['hash_salt'] = $this->randomMachineName();
    $site_settings['tfa.previous_hash_salts'] = [$old_hash];
    new Settings($site_settings);
    $hash = Crypt::hashBase64($old_hash . $test_code);
    $key = 'tfa_accepted_code_' . $hash;
    $user_data_map[] = ['tfa', 3, $key, $this->requestTime];
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->userData->method('get')->willReturnMap($user_data_map);
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Token stored in a historic hash_salt');
    array_pop($user_data_map);

    // Test with an invalid previous_hash_salts records.
    $site_settings['hash_salt'] = $this->randomMachineName();
    $site_settings['tfa.previous_hash_salts'] = 'invalid_record';
    new Settings($site_settings);
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->userData->method('get')->willReturnMap($user_data_map);
    $fixture = $this->getFixture();
    $this->assertTrue($fixture->validateRequest((int) $test_code), 'Invalid previous_hash_salts do not interfere');
    array_pop($user_data_map);

    // Test a code with a leading zero.
    $this->requestTime = 1704740401;
    $test_code = $otp_generator->totp(Encoding::base32DecodeUpper($seed), (int) floor(($this->requestTime / 30)));
    $this->assertSame('014656', $test_code);
    $this->assertTrue($fixture->validateRequest((int) $test_code), 'Code with leading zero accepted');
  }

}

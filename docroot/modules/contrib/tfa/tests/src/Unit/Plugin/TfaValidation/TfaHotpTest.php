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
use Drupal\tfa\Plugin\TfaValidation\TfaHotpValidation;
use Drupal\user\UserDataInterface;
use Otp\Otp;
use ParagonIE\ConstantTime\Encoding;

/**
 * Unit test for TfaHotpValidation plugin.
 *
 * @group tfa
 *
 * @covers \Drupal\tfa\Plugin\TfaValidation\TfaHotpValidation
 * @covers \Drupal\tfa\Plugin\TfaBasePlugin
 */
class TfaHotpTest extends UnitTestCase {

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
  }

  /**
   * Helper method to construct the test fixture.
   *
   * @return \Drupal\tfa\Plugin\TfaValidation\TfaHotpValidation
   *   TfaHotpValidation plugin.
   *
   * @throws \Exception
   */
  protected function getFixture() {

    $time_mock = $this->createMock(TimeInterface::class);
    $time_mock->method('getRequestTime')->willReturn(time());

    // The plugin calls out to the global \Drupal object, so mock that here.
    $this->configFactory = $this->getConfigFactoryStub(['tfa.settings' => $this->tfaSettings]);
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('datetime.time', $time_mock);
    \Drupal::setContainer($container);

    return new TfaHotpValidation(
      $this->configuration,
      'tfa_hotp',
      [],
      $this->userData,
      $this->encryptionProfileManager,
      $this->encryptionService,
      $this->configFactory,
      $this->createMock(TimeInterface::class),
      $this->lockMock
    );
  }

  /**
   * Code validation and replay attack prevention.
   */
  public function testHotpCodeValidation(): void {
    // No codes, means it isn't ready.
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->ready());

    // Fake some codes for user 3.
    $otp_generator = new Otp();
    $seed = 'AR5BPJL24MYUHGBU';
    $last_accepted_counter = 10;

    $user_data_map = [
      ['tfa', 3, 'tfa_hotp_seed', ['seed' => base64_encode('foo')]],
      ['tfa', 3, 'tfa_hotp_counter', &$last_accepted_counter],
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

    // Test a token equal to the current counter.
    $test_code = $otp_generator->hotp(Encoding::base32DecodeUpper($seed), $last_accepted_counter - 1);
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Current counter rejected');

    // Test a token older than the current counter.
    $test_code = $otp_generator->hotp(Encoding::base32DecodeUpper($seed), $last_accepted_counter - 1);
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Token older than current counter');

    // Test a valid token. This code is used for rest of tests.
    $test_code = $otp_generator->hotp(Encoding::base32DecodeUpper($seed), $last_accepted_counter + 1);
    $this->assertTrue($fixture->validateRequest((int) $test_code), 'Valid token accepted');

    // Test the default sha256 storage.
    $hash = Crypt::hashBase64($test_code);
    $key = 'tfa_accepted_code_' . $hash;
    $user_data_map[] = ['tfa', 3, $key, time()];
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->userData->method('get')->willReturnMap($user_data_map);
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->validateRequest((int) $test_code), 'Token stored sha256');
    array_pop($user_data_map);

    // Test the older hash_salt existing site storage.
    $hash = Crypt::hashBase64(Settings::getHashSalt() . $test_code);
    $key = 'tfa_accepted_code_' . $hash;
    $user_data_map[] = ['tfa', 3, $key, time()];
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
    $user_data_map[] = ['tfa', 3, $key, time()];
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
    $last_accepted_counter = 56824679;
    $test_code = $otp_generator->hotp(Encoding::base32DecodeUpper($seed), 56824680);
    $this->assertSame('014656', $test_code);
    $this->assertTrue($fixture->validateRequest((int) $test_code), 'Code with leading zero accepted');
  }

  /**
   * Ensure tfa_hotp_counter is stored correctly.
   */
  public function testTokenCounterStored(): void {

    // Fake some codes for user 3.
    $otp_generator = new Otp();
    $seed = 'AR5BPJL24MYUHGBU';
    $next_expected_counter = 10;

    $user_data_map = [
      ['tfa', 3, 'tfa_hotp_seed', ['seed' => base64_encode('foo')]],
      ['tfa', 3, 'tfa_hotp_counter', &$next_expected_counter],
    ];

    $this->encryptionService->method('decrypt')->with('foo', $this->anything())->willReturn($seed);

    $this->tfaSettings = [
      'validation_plugin_settings.tfa_recovery_code.recovery_codes_amount' => 10,
      'encryption' => 'foo',
      'default_validation_plugin' => 'bar',
    ];

    $this->encryptionProfileManager->method('getEncryptionProfile')->with('foo')->willReturn($this->encryptionProfile);

    // Invalid token should not save a counter.
    $this->userData->method('get')->willReturnMap($user_data_map);
    $this->userData->expects($this->never())->method('set');
    $fixture = $this->getFixture();
    $fixture->validateRequest(12345);

    // Valid token will save a counter.
    $this->userData = $this->createMock(UserDataInterface::class);
    $this->userData->method('get')->willReturnMap($user_data_map);
    $set_callbacks = [];
    $this->userData
      ->expects($this->atLeastOnce())
      ->method('set')
      ->willReturnCallback(
        function (string $module, int $uid, string $name, mixed $value) use (&$set_callbacks) {
          self::assertSame('tfa', $module);
          self::assertSame(3, $uid);
          $set_callbacks[$name] = $value;
        }
      );
    $fixture = $this->getFixture();
    $test_code = $otp_generator->hotp(Encoding::base32DecodeUpper($seed), $next_expected_counter);
    $fixture->validateRequest((int) $test_code);
    $this->assertArrayHasKey('tfa_hotp_counter', $set_callbacks);
    $this->assertSame($set_callbacks['tfa_hotp_counter'], 11);
  }

}

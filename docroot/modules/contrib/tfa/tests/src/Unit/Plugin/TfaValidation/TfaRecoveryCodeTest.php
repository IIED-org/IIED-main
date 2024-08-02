<?php

namespace Drupal\Tests\tfa\Unit\Plugin\TfaValidation;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\encrypt\EncryptionProfileInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tfa\Plugin\TfaValidation\TfaRecoveryCode;
use Drupal\user\UserDataInterface;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\tfa\Plugin\TfaValidation\TfaRecoveryCode
 *
 * @group tfa
 */
class TfaRecoveryCodeTest extends UnitTestCase {
  use ProphecyTrait;

  /**
   * Mocked user data service.
   *
   * @var \Drupal\user\UserDataInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userData;

  /**
   * Mocked encryption profile manager.
   *
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptionProfileManager;

  /**
   * The mocked encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptionService;

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  /**
   * The mocked TFA settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $tfaSettings;

  /**
   * A mocked encryption profile.
   *
   * @var \Drupal\encrypt\EncryptionProfileInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $encryptionProfile;

  /**
   * A mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Stub out default mocked services. These can be overridden prior to
    // calling ::getFixture().
    $this->userData = $this->prophesize(UserDataInterface::class);
    $this->encryptionProfileManager = $this->prophesize(EncryptionProfileManagerInterface::class);
    $this->encryptionService = $this->prophesize(EncryptServiceInterface::class);
    $this->tfaSettings = $this->prophesize(ImmutableConfig::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->encryptionProfile = $this->prophesize(EncryptionProfileInterface::class);
    $this->currentUser = $this->prophesize(AccountProxyInterface::class);
  }

  /**
   * Helper method to construct the test fixture.
   *
   * @return \Drupal\tfa\Plugin\TfaValidation\TfaRecoveryCode
   *   Recovery code.
   *
   * @throws \Exception
   */
  protected function getFixture() {
    // The plugin calls out to the global \Drupal object, so mock that here.
    $this->configFactory->get('tfa.settings')->willReturn($this->tfaSettings->reveal());
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->configFactory->reveal());
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $lock_mock = $this->createMock(LockBackendInterface::class);
    $lock_mock->method('acquire')->willReturn(TRUE);

    return new TfaRecoveryCode(
      $this->configuration,
      'tfa_recovery_code',
      [],
      $this->userData->reveal(),
      $this->encryptionProfileManager->reveal(),
      $this->encryptionService->reveal(),
      $container->get('config.factory'),
      $this->currentUser->reveal(),
      $lock_mock
    );
  }

  /**
   * @covers ::ready
   * @covers ::getCodes
   * @covers ::validate
   */
  public function testReadyCodesValidate() {
    // No codes, means it isn't ready.
    $fixture = $this->getFixture();
    $this->assertFalse($fixture->ready());

    // Fake some codes for user 3.
    $this->userData->get('tfa', 3, 'tfa_recovery_code')
      ->willReturn(['foo', 'bar']);
    $this->encryptionService->decrypt('foo', $this->encryptionProfile->reveal())->willReturn('foo_decrypted');
    $this->encryptionService->decrypt('bar', $this->encryptionProfile->reveal())->willReturn('bar_decrypted');
    $this->tfaSettings->get('validation_plugin_settings.tfa_recovery_code.recovery_codes_amount')->willReturn(10);
    $this->tfaSettings->get('encryption')->willReturn('foo');
    $this->tfaSettings->get('default_validation_plugin')->willReturn('bar');
    $this->encryptionProfileManager->getEncryptionProfile('foo')->willReturn($this->encryptionProfile->reveal());
    $fixture = $this->getFixture();
    $this->assertTrue($fixture->ready());

    $this->assertEquals([
      'foo_decrypted',
      'bar_decrypted',
    ], $fixture->getCodes());

    // Validate with a bad code. Prophecy doesn't support reference returns.
    $this->userData->delete('tfa', 3, 'tfa_recovery_code')->shouldBeCalled();
    $fixture = $this->getFixture();
    $form_state = new FormState();
    $form_state->setValues(['code' => 'bad_code']);
    $this->assertFalse($fixture->validateForm([], $form_state));
    $this->assertCount(1, $fixture->getErrorMessages());

    // Validate with a good code. This will remove the code and re-encrypt the
    // remaining code 'bar_decrypted'.
    $this->encryptionService->encrypt('bar_decrypted', $this->encryptionProfile->reveal())->willReturn('bar');
    $this->userData->set('tfa', 3, 'tfa_recovery_code', [1 => 'bar'])->shouldBeCalled();
    $fixture = $this->getFixture();
    $form_state->setValues(['code' => 'foo_decrypted']);
    $this->assertTrue($fixture->validateForm([], $form_state));
    $this->assertEmpty($fixture->getErrorMessages());
  }

}

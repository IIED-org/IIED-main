<?php

namespace Drupal\Tests\tfa\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\tfa\Commands\TfaTokenManagement;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\Console\Style\SymfonyStyle;

require_once __DIR__ . '/../../../../../../../../vendor/drush/drush/includes/output.inc';

/**
 * Tests the handling of Drush token management commands.
 *
 * @covers \Drupal\tfa\Commands\TfaTokenManagement
 *
 * @group tfa
 */
final class TfaTokenManagementTest extends UnitTestCase {

  /**
   * Mock user.data service.
   *
   * @var \Drupal\user\UserDataInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userDataMock;

  /**
   * Mock logger.channel.tfa service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerChannelMock;

  /**
   * Mock User Storage.
   *
   * @var \Drupal\user\UserStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $userStorageMock;

  /**
   * Mock MailManager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $mailManagerMock;

  /**
   * Mock Drush IO service.
   *
   * @var \Symfony\Component\Console\Style\SymfonyStyle&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $ioMock;

  /**
   * Mock Valid user.
   *
   * @var \Drupal\user\UserInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected $validUserMock;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->mailManagerMock = $this->createMock(MailManagerInterface::class);
    $this->userDataMock = $this->createMock(UserDataInterface::class);
    $this->userStorageMock = $this->createMock(UserStorageInterface::class);
    $this->loggerChannelMock = $this->createMock(LoggerChannelInterface::class);
    $this->ioMock = $this->createMock(SymfonyStyle::class);
    $this->validUserMock = $this->createMock(UserInterface::class);
    $this->validUserMock->method('id')->willReturn('10');
    $this->validUserMock->method('getAccountName')->willReturn('valid_user');
    $this->validUserMock->method('getPreferredLangcode')->willReturn('EN');
  }

  /**
   * Helper method to instantiate the test fixture.
   *
   * @return \Drupal\tfa\Commands\TfaTokenManagement
   *   TFA Drush Token Management service.
   */
  protected function getFixture(): TfaTokenManagement {
    $entity_type_manager_mock = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager_mock->method('getStorage')->with('user')->willReturnReference($this->userStorageMock);
    return new TfaTokenManagement(
      $this->mailManagerMock,
      $this->userDataMock,
      $entity_type_manager_mock,
      $this->loggerChannelMock,
    );
  }

  /**
   * Tests handling of Token Reset commands.
   *
   * @param callable $setup
   *   Callable that will set up mocks with validation. Provided $this as an
   *   argument.
   * @param array{name: string|null, uid: string|null, mail: string|null} $options
   *   Options to be provided to command.
   *
   * @dataProvider providerTestResetUserTfaData
   */
  public function testResetUserTfaData(callable $setup, array $options): void {
    $setup($this);
    $service = $this->getFixture();
    $service->resetUserTfaData($options, $this->ioMock);
  }

  /**
   * Generator for the tesetResetUserTfaData().
   */
  public static function providerTestResetUserTfaData(): \Generator {
    yield 'Successful reset by username' => [
      function (self $context) {
        $context->userStorageMock
          ->expects(self::once())
          ->method('loadByProperties')
          ->with(['name' => 'valid_user'])
          ->willReturn([$context->validUserMock]);
        $context->ioMock
          ->expects(self::once())
          ->method('confirm')
          ->with("Are you sure you want to reset TFA for valid_user (UID: 10)'s data?", FALSE)
          ->willReturn(TRUE);
        $context->ioMock->expects(self::once())->method('writeln')->with('TFA has been disabled.');
        $context->userDataMock->expects(self::once())->method('delete')->with('tfa', 10, NULL);
        $context->loggerChannelMock
          ->expects(self::once())
          ->method('notice')
          ->with(
            "TFA deleted and reset for user @name (UID: @uid).",
            [
              '@name' => 'valid_user',
              '@uid' => '10',
            ]
          );
      },
      ['name' => 'valid_user', 'uid' => NULL, 'mail' => NULL],
    ];

    yield 'Successful reset by uid' => [
      function (self $context) {
        $context->userStorageMock
          ->expects(self::once())
          ->method('load')
          ->with('10')
          ->willReturn($context->validUserMock);
        $context->ioMock
          ->expects(self::once())
          ->method('confirm')
          ->with("Are you sure you want to reset TFA for valid_user (UID: 10)'s data?", FALSE)
          ->willReturn(TRUE);
        $context->ioMock->expects(self::once())->method('writeln')->with('TFA has been disabled.');
        $context->userDataMock->expects(self::once())->method('delete')->with('tfa', 10, NULL);
        $context->loggerChannelMock->expects(self::once())->method('notice');
        $context->mailManagerMock->expects(self::never())->method('mail');
      },
      ['name' => NULL, 'uid' => '10', 'mail' => NULL],
    ];

    yield 'Successful reset by email' => [
      function (self $context) {
        $context->validUserMock->method('getEmail')->willReturn('valid@example.org');
        $context->userStorageMock
          ->expects(self::once())
          ->method('loadByProperties')
          ->with(['mail' => 'valid@example.org'])
          ->willReturn([$context->validUserMock]);
        $context->ioMock
          ->expects(self::once())
          ->method('confirm')
          ->with("Are you sure you want to reset TFA for valid_user (UID: 10)'s data?", FALSE)
          ->willReturn(TRUE);
        $context->ioMock->expects(self::once())->method('writeln')->with('TFA has been disabled.');
        $context->userDataMock->expects(self::once())->method('delete')->with('tfa', 10, NULL);
        $context->loggerChannelMock->expects(self::once())->method('notice');
        $context->mailManagerMock
          ->expects(self::once())
          ->method('mail')
          ->with('tfa', 'tfa_disabled_configuration', 'valid@example.org', 'EN', ['account' => $context->validUserMock]);
      },
      ['name' => NULL, 'uid' => NULL, 'mail' => 'valid@example.org'],
    ];

    yield 'Invalid User provided' => [
      function (self $context) {
        $context->expectException(\Exception::class);
        $context->expectExceptionMessage('Unable to load user by name: InvalidUser');
        $context->userStorageMock
          ->expects(self::once())
          ->method('loadByProperties')
          ->with(['name' => 'InvalidUser'])
          ->willReturn([]);
        $context->userDataMock->expects(self::never())->method('delete');
        $context->mailManagerMock->expects(self::never())->method('mail');
        $context->ioMock->expects(self::never())->method('writeln');
      },
      ['name' => 'InvalidUser', 'uid' => NULL, 'mail' => NULL],
    ];

    yield 'Invalid uid provided' => [
      function (self $context) {
        $context->expectException(\Exception::class);
        $context->expectExceptionMessage('Unable to load user by uid: 32');
        $context->userStorageMock
          ->expects(self::once())
          ->method('load')
          ->with(32)
          ->willReturn(NULL);
        $context->userDataMock->expects(self::never())->method('delete');
        $context->mailManagerMock->expects(self::never())->method('mail');
        $context->ioMock->expects(self::never())->method('writeln');
      },
      ['name' => NULL, 'uid' => 32, 'mail' => NULL],
    ];

    yield 'Invalid mail provided' => [
      function (self $context) {
        $context->expectException(\Exception::class);
        $context->expectExceptionMessage('Unable to load user by mail: invalid_email');
        $context->userStorageMock
          ->expects(self::once())
          ->method('loadByProperties')
          ->with(['mail' => 'invalid_email'])
          ->willReturn([]);
        $context->userDataMock->expects(self::never())->method('delete');
        $context->mailManagerMock->expects(self::never())->method('mail');
        $context->ioMock->expects(self::never())->method('writeln');
      },
      ['name' => NULL, 'uid' => NULL, 'mail' => 'invalid_email'],
    ];

    yield 'Admin answers no to proceed question' => [
      function (self $context) {
        $context->expectException(UserAbortException::class);
        $context->expectExceptionMessage('Command cancelled.');
        $context->userStorageMock
          ->expects(self::once())
          ->method('load')
          ->with('10')
          ->willReturn($context->validUserMock);
        $context->ioMock
          ->expects(self::once())
          ->method('confirm')
          ->with("Are you sure you want to reset TFA for valid_user (UID: 10)'s data?", FALSE)
          ->willReturn(FALSE);
        $context->ioMock->expects(self::never())->method('writeln');
        $context->userDataMock->expects(self::never())->method('delete');
        $context->loggerChannelMock->expects(self::never())->method('notice');
        $context->mailManagerMock->expects(self::never())->method('mail');
      },
      ['name' => NULL, 'uid' => '10', 'mail' => NULL],
    ];
  }

}

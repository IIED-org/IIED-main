<?php

namespace Drupal\Tests\give\Unit;

use Drupal\give\MailHandler;
use Drupal\give\DonationInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\give\MailHandler
 * @group give
 */
class MailHandlerTest extends UnitTestCase {

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface|\PHPUnit\Framework_MockObject_MockObject
   */
  protected $mailManager;

  /**
   * Give mail donations service.
   *
   * @var \Drupal\give\MailHandlerInterface|\PHPUnit\Framework_MockObject_MockObject
   */
  protected $giveMailHandler;

  /**
   * The give form entity.
   *
   * @var \Drupal\give\GiveFormInterface|\PHPUnit\Framework_MockObject_MockObject
   */
  protected $giveForm;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The user storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework_MockObject_MockObject
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->mailManager = $this->createMock('\Drupal\Core\Mail\MailManagerInterface');
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');
    $this->logger = $this->createMock('\Psr\Log\LoggerInterface');
    $this->entityTypeManager = $this->createMock('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->userStorage = $this->createMock('\Drupal\Core\Entity\EntityStorageInterface');
    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    $string_translation = $this->getStringTranslationStub();
    $this->giveMailHandler = new MailHandler($this->mailManager, $this->languageManager, $this->logger, $string_translation, $this->entityTypeManager);
    $language = new Language(['id' => 'en']);

    $this->languageManager->expects($this->any())
      ->method('getDefaultLanguage')
      ->will($this->returnValue($language));

    $this->languageManager->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue($language));
  }

  /**
   * Tests the sendDonationNotices method.
   *
   * @dataProvider getSendMailDonations
   *
   * @covers ::sendDonationNotices
   */
  public function testSendMailDonations(DonationInterface $donation, AccountInterface $donor, $results) {
    $this->logger->expects($this->once())
      ->method('notice');
    $this->mailManager->expects($this->any())
      ->method('mail')
      ->willReturnCallback(
        function ($module, $key, $to, $langcode, $params, $from) use (&$results) {
          $result = array_shift($results);
          // sendDonationNotices sends two emails, one to the admin and one
          // to the user, lets test both.
          if ($to == 'admin@drupal.org') {
            $this->assertEquals($module, $result['module']);
            // @todo Is this key used?
            $this->assertEquals($key, 'form_mail');
            $this->assertEquals($to, "admin@drupal.org");
            $this->assertEquals($langcode, $result['langcode']);
            $this->assertArrayEquals($params, $result['params']);
            // The admin receive the mail from the donor.
            $this->assertEquals($from, $result['to']);
          }
          elseif ($to == 'user@drupal.org') {
            $this->assertEquals($module, $result['module']);
            $this->assertEquals($key, $result['key']);
            $this->assertEquals($to, $result['to']);
            $this->assertEquals($langcode, $result['langcode']);
            $this->assertArrayEquals($params, $result['params']);
            $this->assertEquals($from, $result['from']);
          }
        });
    $this->userStorage->expects($this->any())
      ->method('load')
      ->willReturn(clone $donor);
    $this->giveMailHandler->sendDonationNotices($donation, $donor);
  }

  /**
   * Data provider for ::testSendMailDonations.
   */
  public function getSendMailDonations() {
    $data = [];
    $recipients = ['admin@drupal.org', 'user@drupal.org'];
    $default_result = [
      'module' => 'give',
      'key' => '',
      'to' => implode(', ', $recipients),
      'langcode' => 'en',
      'params' => [],
      'from' => 'anonymous@drupal.org',
    ];
    $results = [];
    $donation = $this->getAnonymousMockDonation($recipients, '');
    $donor = $this->createMockDonor();
    $result = [
      'key' => 'donation_notice',
      'params' => [
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ],
    ];
    $results[] = $result + $default_result;
    $data[] = [$donation, $donor, $results];

    $results = [];
    $donation = $this->getAnonymousMockDonation($recipients, 'reply');
    $donor = $this->createMockDonor();
    $result = [
      'key' => 'donation_notice',
      'params' => [
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ],
    ];
    $results[] = $result + $default_result;
    $result['key'] = 'donation_receipt';
    $result['to'] = 'anonymous@drupal.org';
    $result['from'] = NULL;
    $results[] = $result + $default_result;
    $data[] = [$donation, $donor, $results];

    $results = [];
    $donation = $this->getAnonymousMockDonation($recipients, '', TRUE);
    $donor = $this->createMockDonor();
    $result = [
      'key' => 'donation_notice',
      'params' => [
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ],
    ];
    $results[] = $result + $default_result;
    $data[] = [$donation, $donor, $results];

    $results = [];
    $donation = $this->getAnonymousMockDonation($recipients, 'reply', TRUE);
    $donor = $this->createMockDonor();
    $result = [
      'key' => 'donation_notice',
      'params' => [
        'give_donation' => $donation,
        'donor' => $donor,
        'give_form' => $donation->getGiveForm(),
      ],
    ];
    $results[] = $result + $default_result;
    $result['key'] = 'donation_receipt';
    $result['from'] = NULL;
    $results[] = $result + $default_result;
    $data[] = [$donation, $donor, $results];

    // For authenticated user.
    $results = [];
    $donation = $this->getAuthenticatedMockDonation();
    $donor = $this->createMockDonor(FALSE, 'user@drupal.org');
    $result = [
      'module' => 'give',
      'key' => 'user_mail',
      'to' => 'user2@drupal.org',
      'langcode' => 'en',
      'params' => [
        'give_donation' => $donation,
        'donor' => $donor,
      ],
      'from' => 'user@drupal.org',
    ];
    $results[] = $result;
    $data[] = [$donation, $donor, $results];

    $results = [];
    $donation = $this->getAuthenticatedMockDonation(TRUE);
    $donor = $this->createMockDonor(FALSE, 'user@drupal.org');
    $result = [
      'module' => 'give',
      'key' => 'user_mail',
      'to' => 'user2@drupal.org',
      'langcode' => 'en',
      'params' => [
        'give_donation' => $donation,
        'donor' => $donor,
      ],
      'from' => 'user@drupal.org',
    ];
    $results[] = $result;

    $data[] = [$donation, $donor, $results];

    return $data;
  }

  /**
   * Builds a mock donor on given scenario.
   *
   * @param bool $anonymous
   *   TRUE if the donor is anonymous.
   * @param string $mail_address
   *   The mail address of the user.
   *
   * @return \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework_MockObject_MockObject
   *   Mock donor for testing.
   */
  protected function createMockDonor($anonymous = TRUE, $mail_address = 'anonymous@drupal.org') {
    $donor = $this->createMock('\Drupal\Core\Session\AccountInterface');
    $donor->expects($this->once())
      ->method('isAnonymous')
      ->willReturn($anonymous);
    $donor->expects($this->any())
      ->method('getEmail')
      ->willReturn($mail_address);
    $donor->expects($this->any())
      ->method('getDisplayName')
      ->willReturn('user');
    // User ID 1 has special implications, use 3 instead.
    $donor->expects($this->any())
      ->method('id')
      ->willReturn($anonymous ? 0 : 3);
    if ($anonymous) {
      // Anonymous user values set in params include updated values for name and
      // mail.
      $donor->name = 'Anonymous (not verified)';
      $donor->mail = 'anonymous@drupal.org';
    }
    return $donor;
  }

  /**
   * Builds a mock donation from anonymous user.
   *
   * @param array $recipients
   *   An array of recipient email addresses.
   * @param bool $auto_reply
   *   TRUE if auto reply is enable.
   * @param bool $recurring
   *   TRUE if a donation should recur monthly, FALSE if not.
   *
   * @return \Drupal\give\DonationInterface|\PHPUnit\Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getAnonymousMockDonation(array $recipients, $auto_reply, $recurring = FALSE) {
    $donation = $this->createMock('\Drupal\give\DonationInterface');
    $donation->expects($this->any())
      ->method('getDonorName')
      ->willReturn('Anonymous');
    $donation->expects($this->once())
      ->method('getDonorMail')
      ->willReturn('anonymous@drupal.org');
    $donation->expects($this->once())
      ->method('recurring')
      ->willReturn($recurring);
    $donation->expects($this->any())
      ->method('getGiveForm')
      ->willReturn($this->createMockGiveForm($recipients, $auto_reply));
    return $donation;
  }

  /**
   * Builds a mock donation from authenticated user.
   *
   * @param bool $recurring
   *   TRUE if a donation should recur monthly, FALSE if not.
   *
   * @return \Drupal\give\DonationInterface|\PHPUnit\Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function getAuthenticatedMockDonation($recurring = FALSE) {
    $donation = $this->createMock('\Drupal\give\DonationInterface');
    $donation->expects($this->once())
      ->method('recurring')
      ->willReturn($recurring);
    $recipient = $this->createMock('\Drupal\user\UserInterface');
    $recipient->expects($this->once())
      ->method('getEmail')
      ->willReturn('user2@drupal.org');
    $recipient->expects($this->once())
      ->method('getDisplayName')
      ->willReturn('user2');
    $recipient->expects($this->once())
      ->method('getPreferredLangcode')
      ->willReturn('en');
    $donation->expects($this->any())
      ->method('getGiveForm')
      ->willReturn($this->createMockGiveForm(['user2@drupal.org'], FALSE));
    return $donation;
  }

  /**
   * Builds a mock donation on given scenario.
   *
   * @param array $recipients
   *   An array of recipient email addresses.
   * @param string $reply
   *   A reply receipt to send to the donor.
   *
   * @return \Drupal\give\GiveFormInterface|\PHPUnit\Framework_MockObject_MockObject
   *   Mock donation for testing.
   */
  protected function createMockGiveForm(array $recipients, $reply) {
    $give_form = $this->createMock('\Drupal\give\GiveFormInterface');
    $give_form->expects($this->once())
      ->method('getRecipients')
      ->willReturn($recipients);
    $give_form->expects($this->once())
      ->method('getReply')
      ->willReturn($reply);

    return $give_form;
  }

}

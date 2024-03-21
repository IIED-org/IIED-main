<?php

namespace Drupal\give;

use Drupal\give\Entity\Donation;
use Drupal\give\Plugin\Field\FieldType\PaymentMethod;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides a class for handling assembly and dispatch of give form notices.
 * @todo inject messenger
 */
class MailHandler implements MailHandlerInterface {

  use StringTranslationTrait;

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Mail manager service.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * Constructs a new \Drupal\give\MailHandler object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   Mail manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   String translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, LoggerInterface $logger, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager) {
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
    $this->userStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * {@inheritdoc}
   */
  public function sendDonationNotices(DonationInterface $donation, AccountInterface $donor_account) {
    if ($donor_account->isAnonymous()) {
      // At this point, $donor contains an anonymous user, so we need to take
      // over the submitted form values.
      $donor_account->mail = $donation->mail->value;
      // For the email message, clarify that the donor name is not verified; it
      // could potentially clash with a username on this site.
      $donor_account->name = $this->t('@name (not verified)', ['@name' => $donation->getDonorName()]);
    }

    $this->sendDonationNotice($donation, $donor_account);
    // If configured, send auto-reply receipt to donor, using current language.
    if ($donation->getGiveForm()->get('autoreply')) {
      $this->sendDonationReceipt($donation, $donor_account);
    }

    // Probably doesn't belong here but we have a logger so away we go.
    $this->logger->notice('%donor-name (@donor-from) gave via %give_form.', [
      '%donor-name' => $donor_account->getDisplayName(),
      '@donor-from' => $donor_account->getEmail(),
      '%give_form' => $donation->getGiveForm()->label(),
    ]);
  }

  /**
   * Send donation notice to the form recipient(s), using the site's default language.
   */
  private function sendDonationNotice(DonationInterface $donation, AccountInterface $donor_account) {
    $give_form = $donation->getGiveForm();
    if ($recpients = $give_form->getRecipients()) {
      // Send email to the configured recipient(s) (usually admin users).
      $this->mailManager->mail(
        'give',
        'donation_notice',
        implode(', ', $recpients),
        $this->languageManager->getDefaultLanguage()->getId(),
        [
        'give_donation' => $donation,
        'donor' => $donor_account,
        'give_form' => $give_form
        ],
        $donor_account->getEmail()
      );
    }
  }

  /**
   * Send appropriate donation receipt to donor, using the current language.
   */
  private function sendDonationReceipt(DonationInterface $donation, AccountInterface $donor_cloned) {
    $this->mailManager->mail(
      'give',
      'donation_receipt',
      $donor_cloned->getEmail(),
      $this->languageManager->getCurrentLanguage()->getId(),
      [
        'give_donation' => $donation,
        'donor' => $donor_cloned,
        'give_form' => $donation->getGiveForm(),
      ]
    );
    \Drupal::messenger()->addStatus($this->t("A receipt has been mailed to <em>:mail</em>.", [':mail' => $donation->mail->value]));
  }

  /**
   * Make previews for the donation notice and donation receipts.
   */
  public function makeDonationReceiptPreviews($give_form, $entity_type_manager) {
    $previews = [];

    // DonationInterface.
    $donation = $entity_type_manager
      ->getStorage('give_donation')
      ->create([
        'give_form' => $give_form->id(),
        'recurring' => Donation::NOT_RECURRING,
        'amount' => 12300,
      ]);
    $donation->setMethod(PaymentMethod::GIVE_VIA_OTHER);
    $donation->name->value = 'John Doe';
    $donation->mail->value = 'jondoe@example.com';
    $donation->address_line1->value = '1980 Nebraska Ave';
    $donation->address_city->value = 'Los Angeles';
    $donation->address_state->value = 'CA';
    $donation->address_country->value = 'United States';
    $donation->setCardFunding('credit');
    $donation->setCardBrand('Visa');
    $donation->setCardLast4(9876);
    $donation->setCompleted();

    // Clone the donor, as we make changes to mail and name properties.
    $donor_cloned = clone $this->userStorage->load(0);
    $params = [];
    $current_langcode = $this->languageManager->getCurrentLanguage()->getId();
    $default_langcode = $this->languageManager->getDefaultLanguage()->getId();
    $give_form = $donation->getGiveForm();

    $donor_cloned->name = $donation->getDonorName();
    $donor_cloned->mail = $donation->mail->value;

    // For the email message, clarify that the donor name is not verified; it
    // could potentially clash with a username on this site.
    $donor_cloned->name = $this->t('@name (not verified)', ['@name' => $donation->getDonorName()]);

    // Build email parameters.
    $params['donor'] = $donor_cloned;
    $params['give_form'] = $give_form;
    $params['give_donation'] = $donation;

    // Preview auto-reply receipts to donor, using current language.
    $previews['receipt_card'] = $this->mailManager->doMail(
      'give',
      'donation_receipt',
      $donor_cloned->getEmail(),
      $current_langcode,
      $params,
      NULL,
      FALSE
    );

    $params['give_donation']->set('recurring', 1);
    $params['give_form']->set('frequencies', [
      [
        'interval' => 'month',
        'interval_count' => '1',
        'interval_count' => 'Every month',
      ]
    ]);
    $previews['receipt_card_recurring'] = $this->mailManager->doMail(
      'give',
      'donation_receipt',
      $donor_cloned->getEmail(),
      $current_langcode,
      $params,
      NULL,
      FALSE
    );

    $params['give_donation']->setMethod(PaymentMethod::GIVE_VIA_CHECK);
    $params['give_donation']->set('recurring', Donation::NOT_RECURRING);
    // Unset completed which isn't set for checks.
    $params['give_donation']->set('complete', FALSE);
    $previews['receipt_check'] = $this->mailManager->doMail(
      'give',
      'donation_receipt',
      $donor_cloned->getEmail(),
      $current_langcode,
      $params,
      NULL,
      FALSE
    );

    // Preview email to the configured recipient(s) (usually admin users).
    $previews['admin_notice'] = $this->mailManager->doMail(
      'give',
      'donation_notice',
      implode(', ', $give_form->getRecipients()),
      $default_langcode,
      $params,
      $donor_cloned->getEmail(),
      FALSE
    );

    return $previews;

  }

}

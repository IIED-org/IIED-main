<?php

namespace Drupal\give;

use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for assembly & dispatch of give donation email notices.
 */
interface MailHandlerInterface {

  /**
   * Sends mail donations as appropriate for a given Donation form submission.
   *
   * Can potentially send up to two emails as follows:
   * - To the configured recipient(s); and
   * - Auto-reply receipt to the donor.
   *
   * @param \Drupal\give\DonationInterface $donation
   *   Submitted donation entity.
   * @param \Drupal\Core\Session\AccountInterface $sender
   *   User that submitted the donation entity form.
   */
  public function sendDonationNotices(DonationInterface $donation, AccountInterface $sender);

}

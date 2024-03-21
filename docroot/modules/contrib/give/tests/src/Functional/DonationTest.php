<?php

namespace Drupal\Tests\give\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Test\AssertMailTrait;

/**
 * Tests storing give donations and viewing them through UI.
 *
 * @group give
 */
class DonationTest extends GiveTestBase {

  use AssertMailTrait;

  /**
   * Tests Donation by check as Anonymous User.
   */
  public function testDonationByCheckAsAnonymousUser() {
    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);

    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access give forms']);
    $this->drupalLogout();

    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAnonymousUser('Test_name', $mail, 22);

    // Check to that the user was sent to the step 2.
    $this->assertSession()->addressEquals('/give/test_id/1');
    $this->submitDonateByCheck('12345678', 'Test');
  }

  /**
   * Tests Donation by check as logged in User.
   */
  public function testDonationByCheckAsLoggedInUser() {
    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);
    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAuthenticatedUser(22);

    // Check to that the user was sent to the step 2.
    $this->assertSession()->addressEquals('/give/test_id/1');
    $this->submitDonateByCheck('12345678', 'Test');
    $this->drupalLogout();
  }

  /**
   * Tests Donation by CreditCard as Anonymous User.
   */
  public function testDonationByCreditCardAsAnonymousUser() {
    // Setting fake Stripe credentials.
    $this->setStripeCredentials($this->randomMachineName(), $this->randomMachineName());

    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access give forms']);
    $this->drupalLogout();

    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAnonymousUser('Test_name', $mail, 22);

    // Check to that the user was sent to the step 2.
    $this->assertSession()->addressEquals('/give/test_id/1');
    $this->submitDonateByCreditCard('1234123412341234', '01', '12', '123');
  }

  /**
   * Tests Donation by CreditCard as Logged in User.
   */
  public function testDonationByCreditCardAsLoggedInUser() {
    // Setting fake Stripe credentials.
    $this->setStripeCredentials($this->randomMachineName(), $this->randomMachineName());

    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);
    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAuthenticatedUser(22);

    // Check to that the user was sent to the step 2.
    $this->assertSession()->addressEquals('/give/test_id/1');
    $this->submitDonateByCreditCard('1234123412341234', '01', '12', '123');
    $this->drupalLogout();
  }

  /**
   * Tests Donation by CreditCard as Logged in User with a recurring value.
   */
  public function testDonationByCreditCardWithRecurringValue() {
    // Setting fake Stripe credentials.
    $this->setStripeCredentials($this->randomMachineName(), $this->randomMachineName());

    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access give forms']);
    $this->drupalLogout();

    $this->drupalGet('give/test_id');

    // Setting the recurring value to 3.
    $this->submitDonateStep1AsAnonymousUser('Test_name', $mail, 22, 3);

    // Check to that the user was sent to the step 2.
    $this->assertSession()->addressEquals('/give/test_id/1');
    $this->submitDonateByCreditCard('1234123412341234', '01', '12', '123');
  }

  /**
   * Tests the Donation is displayed correctly in the admin area.
   */
  public function testDonationBeDisplayedOnAdminPage() {
    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);

    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access give forms']);
    $this->drupalLogout();

    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAnonymousUser('Test_name', $mail, 22);

    // Check to that the user was sent to the step 2.
    $this->assertSession()->addressEquals('/give/test_id/1');
    $this->submitDonateByCheck('12345678', 'Test');

    // Login as admin.
    $this->drupalLogin($this->adminUser);

    // Check the donation list overview.
    $this->drupalGet('admin/structure/give/donations');
    $rows = $this->xpath('//tbody/tr');
    // Make sure only 1 donation is available.
    $this->assertEquals(1, count($rows));
    // Some fields should be present.
    $this->assertTrue($this->getSession()->getPage()->hasContent('$22.00'));
    $this->assertTrue($this->getSession()->getPage()->hasContent('Test_name'));
    $this->assertTrue($this->getSession()->getPage()->hasContent('test_label'));

    // Click the view link and make sure name, amount, and email are displayed
    // by default.
    $this->clickLink($this->t('Edit'));

    $display_fields = [
      "Donor name",
      "Donor email address",
      "Amount",
    ];
    foreach ($display_fields as $label) {
      $this->assertSession()->pageTextContains($label);
    }

    // Make sure the stored donation is correct.
    $this->drupalGet('admin/structure/give/donations');
    $this->clickLink($this->t('Edit'));
    $this->assertSession()->fieldValueEquals('edit-name', 'Test_name');
    $this->assertSession()->fieldValueEquals('edit-mail', $mail);
    $this->assertSession()->fieldValueEquals('edit-amount', 22);

    // Submit should redirect back to listing.
    $this->submitForm([], $this->t('Save'));
    $this->assertSession()->addressEquals('admin/structure/give/donations');

    // Delete the donation.
    $this->clickLink($this->t('Delete'));
    $this->submitForm([], $this->t('Delete'));
    // Make sure no donations are available.
    $this->assertSession()->pageTextContains('There are no recorded donations yet.');
  }

  /**
   * Tests Flood setting is working.
   */
  public function testTheFloodFeature() {
    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access give forms']);
    $this->drupalLogout();

    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAnonymousUser('Test_name', $mail, 22);
    // Check to that the user was sent to the step 2.
    $this->submitDonateByCheck('12345678', 'Test');

    // Test the flood feature.
    $flood_limit = 1;
    $this->config('give.settings')
      ->set('flood.limit', $flood_limit)
      ->set('flood.interval', 600)
      ->save();
    // Test the flood interval, the second donation won't work because the user
    // should wait until the flood time has passed.
    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAnonymousUser('Test_name', $mail, 10);
    $this->assertSession()->pageTextContains('You cannot send more than 1 donations in 10 min. Try again later.');
  }

  /**
   * Tests that the autoreply email is working.
   */
  public function testAutoReplyMail() {
    $this->drupalLogin($this->adminUser);
    $mail = 'admin@example.com';
    $this->addGiveForm('test_id', 'test_label', $mail, "Auto reply message", TRUE);
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['access give forms']);
    $this->drupalLogout();

    $this->drupalGet('give/test_id');
    $this->submitDonateStep1AsAnonymousUser('Test_name', $mail, 22);
    // Check to that the user was sent to the step 2.
    $this->submitDonateByCheck('12345678', 'Test');

    $captured_emails = $this->getMails(['id' => 'give_donation_receipt']);

    $this->assertEquals(1, count($captured_emails));
    $this->assertEquals(trim('Auto reply message'), trim($captured_emails[0]['body']));

    // Check that a custom email address overrides the sitewide one.
    $this->setCustomReplyToAddress();
    $this->submitDonateByCheck('12345678', 'Test');
    $this->assertEquals(1, count($captured_emails));
    $this->assertEquals(trim('custom@example.com'), trim($captured_emails[0]['from']));
  }

}

<?php

namespace Drupal\Tests\give\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a base-class for contact-storage tests.
 */
abstract class GiveTestBase extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Admin User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * The theme to install as the default for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'node',
    'text',
    'give',
    'field_ui',
    'give_test',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'access give forms',
      'administer give',
      'create and edit give forms',
      'administer users',
      'administer account settings',
    ]);
  }

  /**
   * Set the stripe credentials.
   *
   * @param string $publishable_key
   *   The stripe publisable key.
   * @param string $secret_key
   *   The stripe secret key.
   */
  public function setStripeCredentials($publishable_key, $secret_key) {
    $config = \Drupal::service('config.factory')->getEditable('give.settings');
    $config->set('stripe_publishable_key', $publishable_key)
      ->set('stripe_secret_key', $secret_key)
      ->save();
  }

  /**
   * Set the custom reply-to/from email address.
   *
   * @param string $mail
   *   The email address to send
   */
  public function setCustomReplyToAddress() {
    $config = \Drupal::service('config.factory')->getEditable('give.settings');
    $config->set('reply_to', 'custom@example.com')
      ->save();
  }

  /**
   * Adds a form.
   *
   * @param string $id
   *   The form machine name.
   * @param string $label
   *   The form label.
   * @param string $recipients
   *   The list of recipient email addresses.
   * @param string $reply
   *   The auto-reply text that is sent to a user upon completing the donation
   *   form.
   * @param bool $selected
   *   A Boolean indicating whether the form should be selected by default.
   */
  public function addGiveForm($id, $label, $recipients, $reply, $selected) {
    $edit = [];
    $edit['label'] = $label;
    $edit['id'] = $id;
    $edit['recipients'] = $recipients;
    $edit['reply'] = $reply;
    $edit['selected'] = ($selected ? TRUE : FALSE);
    $edit['subject'] = $this->randomString();
    $this->drupalGet('admin/structure/give/add');
    $this->submitForm($edit, "edit-submit");
    $this->assertTrue($this->getSession()->getPage()->hasContent('Give form test_label has been added.'));
  }

  /**
   * Submits the contact form.
   *
   * @param string $name
   *   The name of the donor.
   * @param string $mail
   *   The email address of the donor.
   * @param string $amount
   *   The amount of the donation.
   * @param string $id
   *   The form ID of the message.
   */
  public function submitGive($name, $mail, $amount, $id) {
    $edit = [];
    $edit['name'] = $name;
    $edit['mail'] = $mail;
    $edit['amount'] = $amount;
    if ($id == $this->config('give.settings')->get('default_form')) {
      $this->drupalGet('give');
      $this->submitForm($edit, t('Give'));
    }
    else {
      $this->drupalPostForm('give/' . $id, $edit, $this->t('Give'));
    }
  }

  /**
   * Submit the step 1 as an authenticated user.
   *
   * It assumes to the test is already in the correct form.
   *
   * @param int $amount
   *   A Boolean indicating whether the form should be selected by default.
   */
  public function submitDonateStep1AsAuthenticatedUser($amount) {
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your email address"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getEmail()));
    $this->assertTrue($this->getSession()->getPage()->hasContent("Your name"));
    $this->assertTrue($this->getSession()->getPage()->hasContent($this->adminUser->getDisplayName()));
    $this->assertTrue($this->getSession()->getPage()->findField('Amount to give'));
    $this->getSession()->getPage()->fillField('amount', $amount);
    $this->submitForm([], 'edit-submit');
  }

  /**
   * Submit the step 1 as an anonymous user.
   *
   * It assumes to the test is already in the correct form.
   *
   * @param string $name
   *   The user's name.
   * @param string $email
   *   The user's email.
   * @param int $amount
   *   The amount to donate.
   * @param int $recurring
   *   The recurring.
   */
  public function submitDonateStep1AsAnonymousUser($name, $email, $amount, $recurring = 0) {
    $this->assertSession()->fieldExists('Your name');
    $this->assertSession()->fieldExists('Your email address');
    $this->assertTrue($this->getSession()->getPage()->findField('Amount to give'));
    $this->getSession()->getPage()->fillField('Your name', $name);
    $this->getSession()->getPage()->fillField('Your email address', $email);
    $this->getSession()->getPage()->fillField('amount', $amount);
    $this->getSession()->getPage()->selectFieldOption("recurring", $recurring);
    $this->submitForm([], 'edit-submit');
  }

  /**
   * Submit the step 2 selecting the "By check or other" option.
   *
   * It assumes to the test is already in the correct form.
   *
   * @param string $phone
   *   Phone number.
   * @param string $check_or_other_information
   *   The check message.
   */
  public function submitDonateByCheck($phone, $check_or_other_information) {
    // Check that all the fields are present in the second step.
    $this->assertSession()->fieldExists('method');
    $this->assertSession()->fieldExists('Telephone number');
    $this->assertSession()->fieldExists('Further information');

    // Test the "By check or other" donation method.
    $this->getSession()->getPage()->fillField('method', "3");
    $this->getSession()->getPage()->fillField('Telephone number', $phone);
    $this->getSession()->getPage()->fillField('Further information', $check_or_other_information);
    $this->submitForm([], 'Give');
    $this->assertSession()->pageTextContains('Your donation has been received. Thank you!');
  }

  /**
   * Submit the step 2 selecting the "By credit card" option.
   *
   * It assumes to the test is already in the correct form.
   *
   * @param string $card_number
   *   Credit Card.
   * @param string $expiration_month
   *   The credit card expiration month.
   * @param string $expiration_year
   *   The credit card expiration year.
   * @param string $cvc
   *   The credit card $cvc.
   */
  public function submitDonateByCreditCard($card_number, $expiration_month, $expiration_year, $cvc) {
    $this->assertSession()->fieldExists('method');
    // The stripe_token field is hidden so we cannot use findField to check if
    // it exists.
    $this->getSession()->getPage()->hasContent('name="stripe_token"');
    $this->assertSession()->fieldExists('stripe_number');
    $this->assertSession()->fieldExists('stripe_exp_month');
    $this->assertSession()->fieldExists('stripe_exp_year');
    $this->assertSession()->fieldExists('stripe_cvc');

    // Test the "By credit/debit card" donation method.
    $this->getSession()->getPage()->fillField('method', "1");
    $this->getSession()->getPage()->fillField("stripe_number", $card_number);
    $this->getSession()->getPage()->fillField('stripe_exp_month', $expiration_month);
    $this->getSession()->getPage()->fillField('stripe_exp_year', $expiration_year);
    $this->getSession()->getPage()->fillField('stripe_cvc', $cvc);

    // We haven't a real stripe token so we are going to fake one.
    $this->getSession()->getPage()->find('css', 'input[name="stripe_token"]')->setValue($this->randomString());
    $this->submitForm([], 'Give');
    $this->assertTrue($this->getSession()->getPage()->hasContent('Your donation has been received. Thank you!'));
  }

}

<?php

namespace Drupal\Tests\give\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the donation entity class.
 *
 * @group give
 *
 * @see \Drupal\give\Entity\Donation
 * @see \Drupal\give\Entity\GiveForm
 */
class GiveFormAndDonationEntityTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'views',
    'system',
    'give',
    'field',
    'user',
    'give_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['give', 'give_test']);
  }

  /**
   * Test the GiveForm Entity.
   */
  public function testGiveFormMethods() {
    $give_form_storage = $this->container->get('entity.manager')->getStorage('give_form');
    /** @var \Drupal\give\Entity\GiveForm $giveForm */
    $giveForm = $give_form_storage->create();

    // Check for default values first.
    $this->assertEquals([], $giveForm->getRecipients());
    $this->assertEquals('', $giveForm->getSubject());
    $this->assertEquals('', $giveForm->getReply());
    $this->assertEquals('', $giveForm->getCheckOrOtherText());

    // Check for default values.
    $this->assertEquals('/', $giveForm->getRedirectUri());
    $this->assertEquals('Give', $giveForm->getSubmitText());

    // Set some values and check for them afterwards.
    $giveForm->setRecipients(['user@mail.com']);
    $giveForm->setSubject('Mail Subject');
    $giveForm->setReply('reply@mail.com');
    $giveForm->setCheckOrOtherText('Message');
    $giveForm->setRedirectUri("entity:node/10");
    $giveForm->setSubmitText("Donate");

    $this->assertEquals(['user@mail.com'], $giveForm->getRecipients());
    $this->assertEquals('Mail Subject', $giveForm->getSubject());
    $this->assertEquals('reply@mail.com', $giveForm->getReply());
    $this->assertEquals('Message', $giveForm->getCheckOrOtherText());
    $this->assertEquals("entity:node/10", $giveForm->getRedirectUri());
    $this->assertEquals("Donate", $giveForm->getSubmitText());

    $no_access_user = $this->createUser(['uid' => 2]);
    $access_user = $this->createUser(['uid' => 3], ['access give forms']);
    $admin = $this->createUser(['uid' => 4], ['administer give']);
    $user_can_create = $this->createUser(['uid' => 5], ['manage give forms']);

    $this->assertFalse(\Drupal::entityTypeManager()->getAccessControlHandler('give_form')->createAccess(NULL, $no_access_user));
    // Only admin can create EntityForms.
    $this->assertFalse(\Drupal::entityTypeManager()->getAccessControlHandler('give_form')->createAccess(NULL, $access_user));
    $this->assertTrue($giveForm->access('update', $user_can_create));
    $this->assertTrue($giveForm->access('update', $admin));
    $this->assertFalse($giveForm->access('update', $access_user));
  }

  /**
   * Test the Donation Entity.
   */
  public function testDonationMethods() {
    $donation_storage = $this->container->get('entity.manager')->getStorage('give_donation');
    /** @var \Drupal\give\Entity\Donation $donation */
    $donation = $donation_storage->create(['give_form' => 'tzedakah']);

    // Check for empty values first.
    $this->assertEquals(0, $donation->amount->value);
    $this->assertEquals('', $donation->getDonorName());
    $this->assertEquals('', $donation->mail->value());
    $this->assertFalse($donation->isRecurring());

    // Check for default values.
    $this->assertEquals('tzedakah', $donation->getGiveForm()->id());

    // Set some values and check for them afterwards.
    $donation->amount->value = 4200;
    $donation->name->value = 'John Doe';
    $donation->mail->value = 'jondoe@example.com';
    $donation->setRecurrenceIntervalCount(3);

    $this->assertEquals(4200, $donation->amount->value);
    $this->assertEquals('donor_name', $donation->getDonorName());
    $this->assertEquals('donor_mail', $donation->mail->value());
    $this->assertEquals(3, $donation->getRecurrenceIntervalCount());
    $this->assertTrue($donation->isRecurring());

    $no_access_user = $this->createUser(['uid' => 2]);
    $access_user = $this->createUser(['uid' => 3], ['access give forms']);
    $admin = $this->createUser(['uid' => 4], ['administer give']);

    $this->assertFalse(\Drupal::entityTypeManager()->getAccessControlHandler('give_donation')->createAccess(NULL, $no_access_user));
    $this->assertTrue(\Drupal::entityTypeManager()->getAccessControlHandler('give_donation')->createAccess(NULL, $access_user));
    $this->assertTrue($donation->access('edit', $admin));
    $this->assertFalse($donation->access('edit', $access_user));
  }

}

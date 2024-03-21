<?php

namespace Drupal\Tests\give\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Comment notify Base Test class.
 *
 * @group give
 */
class SettingsTest extends BrowserTestBase {

  /**
   * Admin User.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'views',
    'give',
    'field',
    'user',
    'give_test',
  ];

  /**
   * Test that the config page is working.
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and login administrative user.
    $this->adminUser = $this->drupalCreateUser(
      [
        'administer give',
        'manage give forms',
        'access give forms',
      ]
    );
  }

  /**
   * Test the settings page.
   */
  public function testSettingsPage() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/admin/config/services/give');
    $this->getSession()->getPage()->fillField('stripe_secret_key', 'This is my api key');
    $this->getSession()->getPage()->fillField('stripe_publishable_key', 'My publishable API key');
    $this->submitForm([], 'edit-submit');

    $this->drupalGet('/admin/config/services/give');
    $field = $this->getSession()->getPage()->findField('stripe_secret_key');
    $this->assertTrue($field->getValue() == 'This is my api key');
    $field = $this->getSession()->getPage()->findField('stripe_publishable_key');
    $this->assertTrue($field->getValue() == 'My publishable API key');

  }

}

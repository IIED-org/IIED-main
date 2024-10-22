<?php

namespace Drupal\Tests\cloudflare\Functional;

use Drupal\cloudflare_form_tester\Mocks\ComposerDependenciesCheckMock;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests \Drupal\cloudflare\Form\SettingsForm.
 *
 * @group cloudflare
 */
class ComposerDependencyTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cloudflare', 'cloudflare_form_tester', 'ctools'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An admin user that has been setup for the test.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * Route providing the main configuration form of the cloudflare module.
   *
   * @var string|\Drupal\Core\Url
   */
  protected $route = 'cloudflare.admin_settings_form';

  /**
   * Setup the test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer cloudflare']);
    $this->route = Url::fromRoute('cloudflare.admin_settings_form');
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testDependenciesUnmet() {
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(FALSE);
    $this->drupalGet($this->route);
    $this->assertSession()->responseContains('Missing Composer dependencies for CloudFlare');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testDependenciesMet() {
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
    $this->drupalGet($this->route);
    $this->assertSession()->responseNotContains('Missing Composer dependencies for CloudFlare');
  }

}

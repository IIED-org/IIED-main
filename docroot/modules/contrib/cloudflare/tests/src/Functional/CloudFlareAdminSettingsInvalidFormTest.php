<?php

namespace Drupal\Tests\cloudflare\Functional;

use Drupal\cloudflare_form_tester\Mocks\ComposerDependenciesCheckMock;
use Drupal\cloudflare_form_tester\Mocks\ZoneMock;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

/**
 * Tests \Drupal\purge_ui\Form\CloudFlareAdminSettingsForm.
 *
 * @group cloudflare
 */
class CloudFlareAdminSettingsInvalidFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['cloudflare', 'ctools'];

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
   * @var string
   */
  protected $route = 'cloudflare.admin_settings_form';

  /**
   * The form URL.
   *
   * @var \Drupal\Core\Url
   */
  protected Url $formUrl;

  /**
   * Setup the test.
   */
  public function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer cloudflare']);
    $this->formUrl = Url::fromRoute($this->route);
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(TRUE);
  }

  /**
   * Tests that form has critical fields as expected.
   */
  public function testConfigFormDisplay() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->formUrl);
    $this->assertSession()->pageTextContains('This will help suppress log warnings regarding requests bypassing CloudFlare', 'Helper Text');
    $this->assertSession()->fieldExists('auth_using');
    $this->assertSession()->fieldExists('api_token');
    $this->assertSession()->fieldExists('apikey');
    $this->assertSession()->fieldExists('email');
    $this->assertSession()->fieldExists('client_ip_restore_enabled');
    $this->assertSession()->fieldExists('bypass_host');
  }

  /**
   * Test if the form is at its place and has the right permissions.
   */
  public function testFormAccess() {
    // @todo troubleshoot why testing the route as an anonymous user
    // throws a 500 code for travis CI.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->formUrl);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testInvalidCredentials() {
    $mock = new MockHandler([
      new Response(403, [], "This could be a problem."),
    ]);

    $container = \Drupal::getContainer();
    $config_factory = $container->get('config.factory');
    $logger_channel_cloudflare = $container->get('logger.channel.cloudflare');
    $cloudflare_state = $container->get('cloudflare.state');
    $composer_dependencies_check = $container->get('cloudflare.composer_dependency_check');

    $zone_mock = new ZoneMock($config_factory, $logger_channel_cloudflare, $cloudflare_state, $composer_dependencies_check);
    ZoneMock::mockAssertValidCredentials(FALSE);
    $container->set('cloudflare.zone', $zone_mock);

    $this->drupalLogin($this->adminUser);
    $edit = [
      // cspell:disable-next-line
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
    ];
    $this->drupalGet($this->formUrl);
    $this->submitForm($edit, 'Next');
    $this->assertSession()->addressEquals('/admin/config/services/cloudflare');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testUpperCaseInvalidCredentials() {
    ZoneMock::mockAssertValidCredentials(TRUE);
    $edit = [
      // cspell:disable-next-line
      'apikey' => 'fDK5M9sf51x6CEAspHSUYM4vt40m5XC2T6i1K',
      'email' => 'test@test.com',
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->formUrl);
    $this->submitForm($edit, 'Next');
    $this->assertSession()->pageTextContains('Invalid Api Key: Key can only contain lowercase or numerical characters.');
  }

  /**
   * Test invalid key length.
   */
  public function testInvalidKeyLength() {
    ZoneMock::mockAssertValidCredentials(TRUE);
    $edit = [
      // cspell:disable-next-line
      'apikey' => '68ow48650j63zfzx1w9jd29cr367u0ezb6a4g0',
      'email' => 'test@test.com',
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->formUrl);
    $this->submitForm($edit, 'Next');
    $this->assertSession()->pageTextContains('Invalid Api Key: Key should be 37 chars long.');
  }

  /**
   * Test posting an invalid host to the form.
   */
  public function testInvalidKeySpecialChars() {
    ZoneMock::mockAssertValidCredentials(TRUE);
    ComposerDependenciesCheckMock::mockComposerDependenciesMet(FALSE);
    $edit = [
      // cspell:disable-next-line
      'apikey' => '!8ow48650j63zfzx1w9jd29cr367u0ezb6a4g',
      'email' => 'test@test.com',
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($this->formUrl);
    $this->submitForm($edit, 'Next');
    $this->assertSession()->pageTextContains('Invalid Api Key: Key can only contain alphanumeric characters.');
  }

}

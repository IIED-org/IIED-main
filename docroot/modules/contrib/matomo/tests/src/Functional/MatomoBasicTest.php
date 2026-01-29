<?php

declare(strict_types=1);

namespace Drupal\Tests\matomo\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Test basic functionality of Matomo module.
 *
 * @group Matomo
 */
class MatomoBasicTest extends BrowserTestBase {

  /**
   * User without permissions to use snippets.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noSnippetUser;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'matomo',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $permissions = [
      'access administration pages',
      'administer matomo',
    ];

    // User to set up matomo.
    $this->noSnippetUser = $this->drupalCreateUser($permissions);
    $permissions[] = 'add js snippets for matomo';
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if configuration is possible.
   */
  public function testMatomoConfiguration(): void {
    // Check for setting page's presence.
    $this->drupalGet('admin/config/system/matomo');
    $this->assertSession()->responseContains('Matomo site ID');

    // Verify that invalid URLs throw a form error.
    $edit = [];
    $edit['matomo_site_id'] = 1;
    $edit['matomo_url_http'] = 'http://www.example.com/matomo/';
    $edit['matomo_url_https'] = 'https://www.example.com/matomo/';
    $this->drupalGet('admin/config/system/matomo');
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->responseContains('The validation of "http://www.example.com/matomo/matomo.php" failed with an exception');
    $this->assertSession()->responseContains('The validation of "https://www.example.com/matomo/matomo.php" failed with an exception');

    // User should have access to code snippets.
    $this->assertSession()->fieldExists('matomo_codesnippet_before');
    $this->assertSession()->fieldExists('matomo_codesnippet_after');
    $this->assertEmpty($this->xpath("//textarea[@name='matomo_codesnippet_before' and @disabled='disabled']"), '"Code snippet (before)" is enabled.');
    $this->assertEmpty($this->xpath("//textarea[@name='matomo_codesnippet_after' and @disabled='disabled']"), '"Code snippet (after)" is enabled.');

    // Login as user without JS permissions.
    $this->drupalLogin($this->noSnippetUser);
    $this->drupalGet('admin/config/system/matomo');

    // User should *not* have access to snippets, but create fields.
    $this->assertSession()->fieldExists('matomo_codesnippet_before');
    $this->assertSession()->fieldExists('matomo_codesnippet_after');
    $this->assertNotEmpty($this->xpath("//textarea[@name='matomo_codesnippet_before' and @disabled='disabled']"), '"Code snippet (before)" is disabled.');
    $this->assertNotEmpty($this->xpath("//textarea[@name='matomo_codesnippet_after' and @disabled='disabled']"), '"Code snippet (after)" is disabled.');
  }

  /**
   * Tests if page visibility works.
   */
  public function testMatomoPageVisibility(): void {
    $site_id = '1';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Show tracking on "every page except the listed pages".
    $this->config('matomo.settings')->set('visibility.request_path_mode', 0)->save();
    // Disable tracking one "admin*" pages only.
    $this->config('matomo.settings')->set('visibility.request_path_pages', "/admin\n/admin/*")->save();
    // Enable tracking only for authenticated users only.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [AccountInterface::AUTHENTICATED_ROLE => AccountInterface::AUTHENTICATED_ROLE])->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertSession()->responseContains('/js/matomo.js');
    $this->assertSession()->responseContains('u+"matomo.php"');

    // Test whether tracking code is not included on pages to omit.
    $this->drupalGet('admin');
    $this->assertSession()->responseNotContains('u+"matomo.php"');
    $this->drupalGet('admin/config/system/matomo');
    // Checking for tracking URI here, as $site_id is displayed in the form.
    $this->assertSession()->responseNotContains('u+"matomo.php"');

    // Test whether tracking code display is properly flipped.
    $this->config('matomo.settings')->set('visibility.request_path_mode', 1)->save();
    $this->drupalGet('admin');
    $this->assertSession()->responseContains('u+"matomo.php"');
    $this->drupalGet('admin/config/system/matomo');
    // Checking for tracking URI here, as $site_id is displayed in the form.
    $this->assertSession()->responseContains('u+"matomo.php"');
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('u+"matomo.php"');

    // Test whether tracking code is not display for anonymous.
    $this->drupalLogout();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('u+"matomo.php"');

    // Switch back to every page except the listed pages.
    $this->config('matomo.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    // Test whether 403 forbidden tracking code is shown if user has no access.
    $this->drupalGet('admin');
    $this->assertSession()->responseContains('"403/URL = "');

    // Test whether 404 not found tracking code is shown on non-existent pages.
    $this->drupalGet($this->randomMachineName(64));
    $this->assertSession()->responseContains('"404/URL = "');
  }

  /**
   * Tests if tracking code is properly added to the page.
   */
  public function testMatomoTrackingCode(): void {
    $site_id = '2';
    $this->config('matomo.settings')->set('site_id', $site_id)->save();
    $this->config('matomo.settings')->set('url_http', 'http://www.example.com/matomo/')->save();
    $this->config('matomo.settings')->set('url_https', 'https://www.example.com/matomo/')->save();

    // Show tracking code on every page except the listed pages.
    $this->config('matomo.settings')->set('visibility.request_path_mode', 0)->save();
    // Enable tracking code for all user roles.
    $this->config('matomo.settings')->set('visibility.user_role_roles', [])->save();

    // @codingStandardsIgnoreStart
    /* Sample JS code as added to page:
    <script type="text/javascript">
    var _paq = _paq || [];
    (function(){
        var u=(("https:" == document.location.protocol) ? "https://{$MATOMO_URL}" : "http://{$MATOMO_URL}");
        _paq.push(['setSiteId', {$IDSITE}]);
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        _paq.push(['trackPageView']);
        var d=document,
            g=d.createElement('script'),
            s=d.getElementsByTagName('script')[0];
            g.type='text/javascript';
            g.defer=true;
            g.async=true;
            g.src=u+'matomo.js';
            s.parentNode.insertBefore(g,s);
    })();
    </script>
     */
    // @codingStandardsIgnoreEnd

    // Test whether tracking code uses latest JS.
    $this->config('matomo.settings')->set('cache', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('u+"matomo.php"');

    // Test if tracking of User ID is enabled.
    $this->config('matomo.settings')->set('track.userid', 1)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('_paq.push(["setUserId", ');

    // Test if tracking of User ID is disabled.
    $this->config('matomo.settings')->set('track.userid', 0)->save();
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('_paq.push(["setUserId", ');

    // Test whether single domain tracking is active.
    $this->drupalGet('');
    $this->assertSession()->responseNotContains('_paq.push(["setCookieDomain"');

    // Enable "One domain with multiple subdomains".
    $this->config('matomo.settings')->set('domain_mode', 1)->save();
    $this->drupalGet('');

    // Test may run on localhost, an ipaddress or real domain name.
    $cookie_domain = '.' . \parse_url($this->getUrl(), \PHP_URL_HOST);
    if (\count(\explode('.', $cookie_domain)) > 2 && !\is_numeric(\str_replace('.', '', $cookie_domain))) {
      $this->assertSession()->responseContains('_paq.push(["setCookieDomain"');
    }
    else {
      // Special cases, Localhost and IP addresses don't show 'setCookieDomain'.
      $this->assertSession()->responseNotContains('_paq.push(["setCookieDomain"');
    }

    // Test whether the BEFORE and AFTER code is added to the tracker.
    $this->config('matomo.settings')->set('codesnippet.before', '_paq.push(["setLinkTrackingTimer", 250]);')->save();
    $this->config('matomo.settings')->set('codesnippet.after', '_paq.push(["t2.setSiteId", 2]);if(1 == 1 && 2 < 3 && 2 > 1){console.log("Matomo: Custom condition works.");}_gaq.push(["t2.trackPageView"]);')->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('setLinkTrackingTimer');
    $this->assertSession()->responseContains('t2.trackPageView');
    $this->assertSession()->responseContains('if(1 == 1 && 2 < 3 && 2 > 1){console.log("Matomo: Custom condition works.");}');

    // Test disable cookie setting.
    $this->assertSession()->responseNotContains('_paq.push(["disableCookies"]);');
    $this->config('matomo.settings')->set('privacy.disablecookies', TRUE)->save();
    $this->drupalGet('');
    $this->assertSession()->responseContains('_paq.push(["disableCookies"]);');
  }

}

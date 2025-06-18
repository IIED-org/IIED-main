<?php

namespace Drupal\Tests\r4032login\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the option "Redirect user to the page they tried to access after login".
 *
 * @group r4032login
 */
class RedirectToDestinationTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['r4032login'];

  /**
   * Tests the behavior of the redirect_to_destination option.
   *
   * @param string $user_login_path
   *   The login path.
   * @param bool $redirect_to_destination
   *   The option value for "redirect_to_destination".
   *
   * @dataProvider redirectToDestinationDataProvider
   */
  public function testRedirectToDestination($user_login_path, $redirect_to_destination) {
    $config = $this->config('r4032login.settings');
    $config->set('user_login_path', $user_login_path);
    $config->set('redirect_to_destination', $redirect_to_destination);
    $config->save();

    $this->drupalGet('admin/config');

    $currentUrl = $this->getSession()->getCurrentUrl();
    $expectedUrl = $user_login_path == '<front>'
      ? Url::fromRoute($user_login_path)->toString()
      : Url::fromUserInput($user_login_path)->toString();
    if ($redirect_to_destination) {
      $expectedUrl .= '?destination=' . Url::fromUserInput('/admin/config')->toString();
    }
    $expectedUrl = $this->getAbsoluteUrl($expectedUrl);

    $this->assertEquals($expectedUrl, $currentUrl);
  }

  /**
   * Data provider for testRedirectToDestination.
   */
  public static function redirectToDestinationDataProvider() {
    return [
      [
        'user_login_path' => '/user/login',
        'redirect_to_destination' => TRUE,
      ],
      [
        'user_login_path' => '/user/login',
        'redirect_to_destination' => FALSE,
      ],
      [
        'user_login_path' => '<front>',
        'redirect_to_destination' => TRUE,
      ],
      [
        'user_login_path' => '<front>',
        'redirect_to_destination' => FALSE,
      ],
    ];
  }

}

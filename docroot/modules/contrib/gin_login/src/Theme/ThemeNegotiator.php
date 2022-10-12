<?php

namespace Drupal\gin_login\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Contains \Drupal\gin_login\Theme\ThemeNegotiator
 * Credit to jimconte https://jimconte.com/blog/web/dynamic-theme-switching-in-drupal-8.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /** @var ConfigFactoryInterface */
  protected $configFactory;

  public function __construct(
    ConfigFactoryInterface $configFactory
  ) {
    $this->configFactory = $configFactory;
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @return bool
   */
  public function applies(RouteMatchInterface $route_match) {
    return $this->negotiateRoute($route_match) ? TRUE : FALSE;
  }

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @return null|string
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->negotiateRoute($route_match) ?: NULL;
  }

  /**
   * Function that does all of the work in selecting a theme.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return bool|string
   */
  private function negotiateRoute(RouteMatchInterface $route_match) {
    if (
        $route_match->getRouteName() == 'user.login' ||
        $route_match->getRouteName() == 'user.pass' ||
        $route_match->getRouteName() == 'user.register'
      ) {
      return $this->configFactory->get('system.theme')->get('admin');
    }

    return FALSE;
  }

}

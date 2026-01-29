<?php

namespace Drupal\login_destination;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Security\TrustedCallbackInterface;

class LoginDestination implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRender'];
  }

  /**
   * #pre_render callback: Sets caching on a link, if necessary.
   */
  public static function preRender(array $build): array {
    $cache = CacheableMetadata::createFromRenderArray($build);

    $routes = [
      'user.login',
      'user.logout',
    ];

    if (empty($build['#url'])) {
      return $build;
    }

    /** @var \Drupal\Core\Url $url */
    $url = $build['#url'];
    if ($url->isRouted() && in_array($url->getRouteName(), $routes)) {
      $cache->addCacheContexts(['url.path']);
    }

    $cache->applyTo($build);

    return $build;
  }
}

<?php

namespace Drupal\acquia_purge\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\purge\EventSubscriber\CacheableResponseSubscriber as PurgeCacheableResponseSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Add cache tags headers on cacheable responses, for external caching systems.
 *
 * Overrides the Cacheable Response Subscriber in Purge.
 */
class CacheableResponseSubscriber extends PurgeCacheableResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onRespond', -1000];
    return $events;
  }

  /**
   * Add cache tags headers on cacheable responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    // Only set any headers when this is a cacheable response.
    $response = $event->getResponse();

    // Cache tags should be injected only when the response is cacheable. It is
    // cacheable when dynamic_page_cache module (if enabled) says so.
    // Alternatively, if dynamic_page_cache module is uninstalled, then we
    // fallback on testing that at least 'no-cache' cache directive is not
    // present in the response headers.
    if ($response instanceof CacheableResponseInterface && $response->isCacheable()) {
      // Iterate all tagsheader plugins and add a header for each plugin.
      $tags = $response->getCacheableMetadata()->getCacheTags();
      foreach ($this->purgeTagsHeaders as $header) {
        if ($header->isEnabled()) {
          // Retrieve the header name and perform a few simple sanity checks.
          $name = $header->getHeaderName();
          // Workaround for Purge Issue #2976480: inject Tags for Acquia Cloud.
          if (!$response->headers->get($name)) {
            if ((!is_string($name)) || empty(trim($name))) {
              $pluginId = $header->getPluginId();
              throw new \LogicException("Header plugin '$pluginId' should return a non-empty string on ::getHeaderName()!");
            }
            $response->headers->set($name, $header->getValue($tags));
          }
        }
      }
    }
  }

}

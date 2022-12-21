<?PHP

namespace Drupal\acquia_purge_geoip\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds the X-Geo-Country header to Drupal's Vary response header.
 */
class SetVaryHeader implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function onRespond(ResponseEvent $event) {
    $response = $event->getResponse();
    $response->headers->set('Vary', 'X-Geo-Country');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}

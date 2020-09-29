<?php

namespace Drupal\acquia_connector\EventSubscriber;

use Drupal\acquia_connector\Subscription;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\update\Controller\UpdateController;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class InitSubscriber.
 *
 * Init (i.e., hook_init()) subscriber that displays a message asking you to
 * connect to Acquia if you haven't already.
 *
 * @package Drupal\acquia_connector\EventSubscriber
 */
class InitSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The state factory.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * InitSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   State.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state, CacheBackendInterface $cache) {
    $this->configFactory = $config_factory;
    $this->state = $state;
    $this->cache = $cache;
  }

  /**
   * Display a message asking the user to connect to Acquia.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Event.
   */
  public function onKernelRequest(GetResponseEvent $event) {

    acquia_connector_auto_connect();

    acquia_connector_show_free_tier_promo();

  }

  /**
   * Refresh subscription information.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
   *   Event.
   */
  public function onKernelController(FilterControllerEvent $event) {
    if ($event->getRequest()->attributes->get('_route') != 'update.manual_status') {
      return;
    }

    $controller = $event->getController();
    /*
     * $controller passed can be either a class or a Closure.
     * This is not usual in Symfony but it may happen.
     * If it is a class, it comes in array format
     */
    if (!is_array($controller)) {
      return;
    }

    if ($controller[0] instanceof UpdateController) {
      // Refresh subscription information, so we are sure about our update
      // status. We send a heartbeat here so that all of our status information
      // gets updated locally via the return data.
      $subscription = new Subscription();
      $subscription->update();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['onKernelRequest'];
    $events[KernelEvents::CONTROLLER][] = ['onKernelController'];
    return $events;
  }

}

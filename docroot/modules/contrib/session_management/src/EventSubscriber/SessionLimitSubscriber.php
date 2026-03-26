<?php

namespace Drupal\session_management\EventSubscriber;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\session_management\Browser;
use Drupal\session_management\SessionMonitorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\Url;

/**
 * Check the user session limit.
 */
class SessionLimitSubscriber implements EventSubscriberInterface {

  /**
   * The Config Factory.
   *
   * @var \Drupal\Core\Config\ImmutableConfig|\Drupal\Core\Config\Config
   */
  protected ImmutableConfig $configFactory;

  /**
   * The messenger class.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The session Monitor service.
   *
   * @var \Drupal\session_management\SessionMonitorInterface
   */
  protected SessionMonitorInterface $sessionMonitor;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * Construct the SessionLimitSubscriber Object.
   */
  public function __construct(ConfigFactory $configFactory, MessengerInterface $messenger, SessionMonitorInterface $sessionMonitor, AccountProxy $currentUser) {
    $this->configFactory = $configFactory->get('session_management.settings');
    $this->messenger = $messenger;
    $this->sessionMonitor = $sessionMonitor;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST] = 'onKernelEvent';
    return $events;
  }

  /**
   * Check the user session limit.
   */
  public function onKernelEvent(RequestEvent $event): void {

      $request = $event->getRequest();
      $current_route = \Drupal::routeMatch();
      $route_name = $current_route->getRouteName();

      if($route_name === 'user.login'){
          \Drupal::service('page_cache_kill_switch')->trigger();
          $queryLogout = $request->query->get('logout');
          //IP login restriction error message
          if (!empty($queryLogout) && $queryLogout === 'ip_restriction') {
              $this->messenger->addMessage(t($this->configFactory->get('ip_message')??''), 'error', TRUE);
              $event->setResponse(new RedirectResponse(Url::fromRoute('user.login')->toString()));
          }
      }

    // Show the logout warning to the user.
    if (isset($_SESSION['mo_message'])) {
      $session_message = $_SESSION['mo_message'];
      foreach ($session_message as $severity => $message) {
        \Drupal::messenger()->addMessage(t($message), $severity);
      }
      unset($_SESSION['mo_message']);
    }

    // Check if current user session limit exceeds.
    if ($this->currentUser->isAuthenticated() && $this->isSessionLimitExceed()) {

      // Delete the oldest user session.
      $user_active_sessions = $this->sessionMonitor->getSessions($this->currentUser);

      $user_session_to_delete = $user_active_sessions[0] ?? '';

      if(!empty($user_session_to_delete)){

        $userAgent = $this->sessionMonitor->getStoredSessionData($user_session_to_delete['session'])['_sf2_attributes']['mo_browser'] ?? '';

        $session_limit = $this->configFactory->get('session_limit_count');
        $message = "The maximum number of simultaneous sessions ($session_limit) for your account has been reached. Someone else is logged on to your account using your credentials. This may indicate that your account has been compromised or that account sharing is limited on this site. Please contact the site administrator if you suspect your account has been compromised.";
        $serialized_message = 'mo_message|' . serialize([
            'warning' => $message,
          ]);

        \Drupal::database()->update('sessions')
          ->fields([
            'uid' => 0,
            'session' => $serialized_message,
          ])
          ->condition('sid', $user_session_to_delete['sid'], '=')
          ->execute();

        $browser = new Browser($userAgent);
        $para = [
          '%browser' => $browser->getBrowser($userAgent),
          '%device' => $browser->getPlatform(),
          '%session_limit' => $session_limit,
        ];

        \Drupal::messenger()->addWarning(t('Your previous session on browser %browser (%device) has been terminated because you have reached the maximum session limit of %session_limit.', $para));

      }
    }

  }

  /**
   * Check if user session limit exceed.
   */
  public function isSessionLimitExceed(): bool {

    $enable_session_limiter = $this->configFactory->get('enable_session_limiter');
    $session_limit_count = $this->configFactory->get('session_limit_count');

    return $this->currentUser->id() && $enable_session_limiter && count($this->sessionMonitor->getSessions($this->currentUser)) > $session_limit_count;
  }

}

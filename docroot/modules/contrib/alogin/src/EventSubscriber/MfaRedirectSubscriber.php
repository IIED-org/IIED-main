<?php

namespace Drupal\alogin\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
// use Symfony\Contracts\EventDispatcher\Event;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\alogin\AuthenticatorService;
use Drupal\Core\Url;

/**
 * Class for redirecting event.
 */
class MfaRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Session\AccountInterface definition.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Drupal\alogin\Services\AuthenticatorService definition.
   *
   * @var \Drupal\alogin\Services\AuthenticatorService
   */
  protected $alogin;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $enityTypeManager;

  /**
   * Constructs a new MfaRedirectSubscriber object.
   */
  public function __construct(AccountInterface $current_user, AuthenticatorService $alogin, CurrentRouteMatch $routeMatch, ConfigFactory $configFactory, MessengerInterface $messenger, EntityTypeManager $entityTypeManager) {
    $this->currentUser       = $current_user;
    $this->alogin            = $alogin;
    $this->routeMatch        = $routeMatch;
    $this->configFactory     = $configFactory;
    $this->messenger         = $messenger;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['check2fa'];
    return $events;
  }

  /**
   * This method is called when the check2fa is dispatched.
   *
   * @param object $event
   *   The event object containing context for the event.
   *   In Drupal 9 this will be a \Symfony\Component\EventDispatcher\Event,
   *   In Drupal 10 this will be a \Symfony\Contracts\EventDispatcher\Event.
   */
  public function check2fa(object $event) {
    // dump($this->routeMatch->getRouteName());
    if ($this->currentUser->isAuthenticated()) {
      $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $config = $this->configFactory->get('alogin.config');
      $bypass_routes = [
        'entity.user.edit_form',
        'user.pass',
        'user.logout',
        'alogin.settings_form',
      ];
      if (
        !$account->hasPermission('alogin bypass enforced redirect') &&
        !$config->get('allow_enable_disable') &&
        !$this->alogin->exists($this->currentUser->id()) &&
        !in_array($this->routeMatch->getRouteName(), $bypass_routes) &&
        $config->get('redirect')
      ) {
        $this->messenger->addMessage($config->get('redirect_message'), $config->get('message_type'), TRUE);
        $event->setResponse(
          new RedirectResponse(Url::fromRoute(
            'alogin.settings_form',
            ['user' => $this->currentUser->id()]
          )->toString())
        );
      }
    }
  }

}

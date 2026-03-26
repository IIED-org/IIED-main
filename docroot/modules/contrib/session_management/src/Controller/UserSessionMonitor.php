<?php

namespace Drupal\session_management\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\session_management\SessionMonitorInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for custom access and other functionality.
 */
class UserSessionMonitor extends ControllerBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Session Monitor service.
   *
   * @var \Drupal\session_management\SessionMonitorInterface
   */
  protected SessionMonitorInterface $sessionMonitor;

  /**
   * The data formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  public function __construct(ConfigFactoryInterface $configFactory, SessionMonitorInterface $sessionMonitor, DateFormatterInterface $dateFormatter) {
    $this->configFactory = $configFactory->getEditable('session_management.settings');
    $this->sessionMonitor = $sessionMonitor;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
      $container->get('mo_session_monitor'),
      $container->get('date.formatter'),
    );
  }

  /**
   * Custom access for the Session tab.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user of which the session monitor table is accessing.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account of which the session monitor table belong.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   access result allowed or forbidden
   */
  public function access(UserInterface $user, AccountInterface $account): AccessResult {
    $enable_session_monitor = $this->configFactory->get('enable_session_monitor');
    if ($enable_session_monitor && $user->id() === $account->id()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Clear the current user session
   * @return AjaxResponse
   */
  public function logout() {
    $user = \Drupal::currentUser();

    \Drupal::logger('session_management')->info(
      'Session automatically terminated for %name by Sessesion Management autologout.',
      ['%name' => $user->getAccountName()]
    );

    // Destroy the current session.
    user_logout();

    $this->sessionMonitor->clear();

    $response = new AjaxResponse();
    $response->setStatusCode(200);

    return $response;
  }



}

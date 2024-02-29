<?php

namespace Drupal\alogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AuthenticatorController extends ControllerBase {
  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @param \Drupal\Core\Routing\RouteMatchInterface $account
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match')
    );
  }

  public function access(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAuthenticated() && $this->routeMatch->getParameter('user')->id() === $account->id());
  }
}

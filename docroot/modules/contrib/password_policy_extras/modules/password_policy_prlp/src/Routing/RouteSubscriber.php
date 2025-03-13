<?php

namespace Drupal\password_policy_prlp\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for password policy prlp routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Override controller for password reset form.
    if ($route = $collection->get('user.reset.form')) {
      $route->setDefault('_controller', '\Drupal\password_policy_prlp\Controller\PasswordPolicyPrlpController::getResetPassForm');
    }
  }

}

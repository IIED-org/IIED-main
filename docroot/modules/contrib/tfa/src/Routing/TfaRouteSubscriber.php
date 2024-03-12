<?php

namespace Drupal\tfa\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\user\Controller\UserController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 *
 * Class TfaRouteSubscriber.
 *
 * @package Drupal\tfa\Routing
 */
class TfaRouteSubscriber extends RouteSubscriberBase {

  /**
   * Overrides user.login route with our custom login form.
   *
   * @param \Symfony\Component\Routing\RouteCollection $collection
   *   Route to be altered.
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change path of user login to our overridden TFA login form.
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\tfa\Form\TfaLoginForm');
    }
    // Change path of user.login.http to our protected controller.
    if ($route = $collection->get('user.login.http')) {
      $route->setDefault('_controller', '\Drupal\tfa\Controller\TfaUserAuthenticationController::login');
    }
    // Change path to user pass reset to our overridden TFA user controller.
    if ($route = $collection->get('user.reset.login')) {

      $reflected_parent = new \ReflectionClass(UserController::class);
      $parameter_count = $reflected_parent->getMethod('resetPassLogin')->getNumberOfParameters();
      if ($parameter_count == 3) {
        $route->setDefault('_controller', '\Drupal\tfa\Controller\TfaUserControllerDeprecated::resetPassLogin');
      }
      else {
        $route->setDefault('_controller', '\Drupal\tfa\Controller\TfaUserController::resetPassLogin');
      }
    }
  }

}

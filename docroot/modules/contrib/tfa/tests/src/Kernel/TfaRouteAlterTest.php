<?php

declare(strict_types=1);

namespace Drupal\Tests\tfa\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\KernelTests\KernelTestBase;
use Drupal\tfa\Routing\TfaRouteSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Tests the TfaRouteSubscriber alters.
 *
 * @group tfa
 *
 * @covers \Drupal\tfa\Routing\TfaRouteSubscriber
 */
#[Group('tfa')]
#[CoversClass(TfaRouteSubscriber::class)]
final class TfaRouteAlterTest extends KernelTestBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'tfa',
    'user',
    'encrypt',
    'encrypt_test',
    'key',
    'system',
  ];

  /**
   * Register this class as an event subscriber.
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);
    $container
      ->register('tfa.test.TfaRouteAlterTest', self::class)
      ->addTag('event_subscriber');
    $container->set('tfa.test.TfaRouteAlterTest', $this);
  }

  /**
   * Test TFA override other high priority alters.
   */
  public function testRouteAlterPriority(): void {
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');

    $route_user_login = $route_provider->getRouteByName('user.login');
    $this->assertSame('\Drupal\tfa\Form\TfaLoginForm', $route_user_login->getDefault('_form'));

    $route_user_login_http = $route_provider->getRouteByName('user.login.http');
    $this->assertSame('\Drupal\tfa\Controller\TfaUserAuthenticationController::login', $route_user_login_http->getDefault('_controller'));

    $route_user_reset_login = $route_provider->getRouteByName('user.reset.login');
    if (version_compare(\Drupal::VERSION, '9.3.0', '>=')) {
      $this->assertSame('\Drupal\tfa\Controller\TfaUserController::resetPassLogin', $route_user_reset_login->getDefault('_controller'));
    }
    else {
      $this->assertSame('\Drupal\tfa\Controller\TfaUserControllerDeprecated::resetPassLogin', $route_user_reset_login->getDefault('_controller'));
    }

    $route_user_reset = $route_provider->getRouteByName('user.reset');
    $this->assertSame('\Drupal\user\Controller\UserController::resetPass', $route_user_reset->getDefault('_controller'));
  }

  /**
   * Ensures that _tfa_route_validation() is called in tfa_requirements().
   */
  public function testRouteWarningStatusCalled(): void {
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    /** @var \Drupal\Core\Routing\RouteProviderInterface $route_provider */
    $route_provider = \Drupal::service('router.route_provider');
    // Force an invalid route so that _tfa_route_validation() has an error to
    // report.
    $route_provider->getRouteByName('user.login')->setDefault('_form', '\Invalid\Class');
    $requirements = \Drupal::service('system.manager')->listRequirements();
    $this->assertArrayHasKey('tfa.route.user.login', $requirements);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // We want to be the second to last subscriber.
      RoutingEvents::ALTER => ['alterRoutes', PHP_INT_MIN + 1],
    ];
  }

  /**
   * Simulate modules overriding routes.
   */
  public function alterRoutes(RouteBuildEvent $event): void {
    $collection = $event->getRouteCollection();
    // Change path of user login to our overridden TFA login form.
    if ($route = $collection->get('user.login')) {
      $route->setDefault('_form', '\Drupal\user\Form\UserLoginForm');
    }
    // Change path of user.login.http to our protected controller.
    if ($route = $collection->get('user.login.http')) {
      $route->setDefault('_controller', '\Drupal\user\Controller\UserAuthenticationController::login');
    }
    // Change path to user pass reset to our overridden TFA user controller.
    if ($route = $collection->get('user.reset.login')) {

      $route->setDefault('_controller', '\Drupal\user\Controller\UserController::resetPassLogin');
    }
    if ($route = $collection->get('user.reset')) {
      $route->setDefault('_controller', '\Drupal\user\Controller\UserController::resetPass');
    }
  }

}

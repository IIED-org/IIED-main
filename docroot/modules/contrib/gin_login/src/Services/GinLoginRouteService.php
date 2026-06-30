<?php

namespace Drupal\gin_login\Services;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for get the routes of login.
 */
class GinLoginRouteService implements ContainerInjectionInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a GinLoginRouteService object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * Instantiates a new instance of this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Get route definitions.
   *
   * @return array
   *   The routes of login.
   */
  public function getLoginRouteDefinitions() {
    $route_definitions = [
      'user.login' => [
        'page' => 'page__user__login',
        'template' => 'page--user--login',
        'preprocess functions' => ['gin_login_preprocess_ginlogin'],
      ],
      'user.pass' => [
        'page' => 'page__user__password',
        'template' => 'page--user--password',
        'preprocess functions' => ['gin_login_preprocess_ginlogin'],
      ],
      'user.register' => [
        'page' => 'page__user__register',
        'template' => 'page--user--register',
        'preprocess functions' => ['gin_login_preprocess_ginlogin'],
      ],
      'user.reset.form' => [
        'page' => 'page__user__password',
        'template' => 'page--user--password',
        'preprocess functions' => ['gin_login_preprocess_ginlogin'],
      ],
      'user.logout.confirm' => [
        'page' => 'page__user__logout_confirm',
        'template' => 'page--user--logout-confirm',
        'preprocess functions' => ['gin_login_preprocess_ginlogin'],
      ],
    ];

    $this->moduleHandler->alter('gin_login_route_definitions', $route_definitions);

    return $route_definitions;
  }

}

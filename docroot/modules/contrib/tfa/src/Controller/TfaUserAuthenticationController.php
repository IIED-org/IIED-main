<?php

namespace Drupal\tfa\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\tfa\TfaLoginContextTrait;
use Drupal\user\Controller\UserAuthenticationController;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Protect the user.login.http route from use TFA enabled accounts.
 *
 * @todo Consider not extending UserAuthenticationController::class
 *
 * @internal
 */
final class TfaUserAuthenticationController extends UserAuthenticationController {

  use TfaLoginContextTrait;

  /**
   * Constructs a new UserAuthenticationController object.
   *
   * @param \Drupal\Core\Flood\FloodInterface $user_flood_control
   *   The flood control service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token
   *   The CSRF token generator.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user.data service.
   */
  public function __construct(FloodInterface $user_flood_control, UserStorageInterface $user_storage, CsrfTokenGenerator $csrf_token, UserAuthInterface $user_auth, RouteProviderInterface $route_provider, Serializer $serializer, array $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, UserDataInterface $user_data) {
    parent::__construct($user_flood_control, $user_storage, $csrf_token, $user_auth, $route_provider, $serializer, $serializer_formats, $logger);
    $this->tfaSettings = $config_factory->get('tfa.settings');
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    try {
      $flood_service = $container->get('user.flood_control');
    }
    catch (ServiceNotFoundException $e) {
      $flood_service = $container->get('flood');
    }

    if ($container->hasParameter('serializer.formats') && $container->has('serializer')) {
      $serializer = $container->get('serializer');
      $formats = $container->getParameter('serializer.formats');
    }
    else {
      $formats = ['json'];
      $encoders = [new JsonEncoder()];
      $serializer = new Serializer([], $encoders);
    }

    return new static(
      $flood_service,
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('csrf_token'),
      $container->get('user.auth'),
      $container->get('router.route_provider'),
      $serializer,
      $formats,
      $container->get('logger.factory')->get('user'),
      $container->get('config.factory'),
      $container->get('user.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function login(Request $request) {

    $format = $this->getRequestFormat($request);
    $content = $request->getContent();
    $credentials = $this->serializer->decode($content, $format);

    if (!isset($credentials['name'])) {
      throw new BadRequestHttpException('Missing credentials.name.');
    }

    /** @var \Drupal\user\UserInterface[] $users */
    $users = $this->userStorage->loadByProperties(['name' => $credentials['name']]);

    if (count($users) !== 1) {
      throw new BadRequestHttpException('Sorry, unrecognized username or password.');
    }

    $this->setUser(reset($users));

    // TFA Disabled globally or not enabled for user
    // we allow the core controller to process.
    if ($this->isTfaDisabled()) {
      return parent::login($request);
    }

    // Reject the request if TFA is enabled for the user, even if it is not
    // yet fully configured. We use the not activated message to avoid leaking
    // information about TFA status.
    throw new AccessDeniedHttpException('The user has not been activated or is blocked.');

  }

}

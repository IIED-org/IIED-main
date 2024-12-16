<?php

namespace Drupal\services_tfa\Plugin\ServiceDefinition;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\services\ServiceDefinitionBase;
use Drupal\tfa\TfaValidationPluginManager;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * TFA web service.
 *
 * @ServiceDefinition(
 *   id = "tfa_login",
 *   methods = {
 *     "POST"
 *   },
 *   translatable = true,
 *   title = @Translation("TFA Login"),
 *   description = @Translation("Allows user to login through TFA authentication."),
 *   category = @Translation("Security"),
 *   path = "auth/tfa"
 * )
 *
 * @deprecated in tfa:8.x-1.4 and is removed from tfa:2.0.0-alpha3. No replacement is provided.
 * @see https://www.drupal.org/node/3395756
 */
class GenericValidation extends ServiceDefinitionBase implements ContainerFactoryPluginInterface {
  use DependencySerializationTrait;
  /**
   * Validation plugin manager.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  protected $tfaValidationManager;

  /**
   * The validation plugin object.
   *
   * @var \Drupal\tfa\Plugin\TfaValidationInterface
   */
  protected $validationPlugin;

  /**
   * The validation plugin object.
   *
   * @var \Drupal\tfa\Plugin\TfaValidationInterface
   */
  protected $userData;

  /**
   * The Lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * TFA Web Services constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data service.
   * @param \Drupal\tfa\TfaValidationPluginManager $tfa_validation_manager
   *   Validation plugin manager.
   * @param \Drupal\Core\Lock\LockBackendInterface|null $lock
   *   The lock service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $config_factory
   *   Config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, TfaValidationPluginManager $tfa_validation_manager, $lock = NULL, $config_factory = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userData = $user_data;
    $this->tfaValidationManager = $tfa_validation_manager;
    if (!$lock) {
      @trigger_error('Constructing ' . __CLASS__ . ' without the lock service parameter is deprecated in tfa:8.x-1.3 and will be required before tfa:2.0.0. See https://www.drupal.org/node/3396512', E_USER_DEPRECATED);
      $lock = \Drupal::service('lock');
    }
    $this->lock = $lock;
    if (!$config_factory) {
      $config_factory = \Drupal::service('config.factory');
    }
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.data'),
      $container->get('plugin.manager.tfa.validation'),
      $container->get('lock'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  /*
  public function processRoute(Route $route) {
  // @todo Figure out why this results in 403.
  $route->setRequirement('_user_is_logged_in', 'FALSE');
  }
   */

  /**
   * {@inheritdoc}
   */
  public function processRequest(Request $request, RouteMatchInterface $route_match, SerializerInterface $serializer) {
    $uid = $request->get('id');
    $code = $request->get('code');
    $plugin_id = $request->get('plugin_id');

    if ($uid && $code && $plugin_id) {
      $allowed_validation_plugins = $this->configFactory->get('tfa.settings')->get('allowed_validation_plugins');
      if (!array_key_exists($plugin_id, $allowed_validation_plugins)) {
        throw new AccessDeniedHttpException('Invalid plugin_id.');
      }

      $this->validationPlugin = $this->tfaValidationManager->createInstance($plugin_id, ['uid' => $uid]);
      $validation_lock_id = 'tfa_validate_' . $uid;
      while (!$this->lock->acquire($validation_lock_id)) {
        $this->lock->wait($validation_lock_id);
      }
      // @todo validateRequest is not part of TfaValidationInterface.
      $valid = $this->validationPlugin->validateRequest($code);
      $this->lock->release($validation_lock_id);
      if ($this->validationPlugin->isAlreadyAccepted()) {
        throw new AccessDeniedHttpException('Invalid code, it was recently used for a login. Please try a new code.');
      }
      elseif (!$valid) {
        throw new AccessDeniedHttpException('Invalid application code. Please try again.');
      }
      else {
        return 1;
      }
    }
    else {
      throw new AccessDeniedHttpException('Required parameters missing.');
    }
  }

}

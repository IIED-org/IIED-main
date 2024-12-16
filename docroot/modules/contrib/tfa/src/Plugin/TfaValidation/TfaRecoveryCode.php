<?php

namespace Drupal\tfa\Plugin\TfaValidation;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\tfa\Plugin\TfaBasePlugin;
use Drupal\tfa\Plugin\TfaValidationInterface;
use Drupal\tfa\TfaRandomTrait;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Recovery validation class for performing recovery codes validation.
 *
 * @TfaValidation(
 *   id = "tfa_recovery_code",
 *   label = @Translation("TFA Recovery Code"),
 *   description = @Translation("TFA Recovery Code Validation Plugin"),
 *   setupPluginId = "tfa_recovery_code_setup",
 * )
 */
class TfaRecoveryCode extends TfaBasePlugin implements TfaValidationInterface, ContainerFactoryPluginInterface {

  use TfaRandomTrait;

  use StringTranslationTrait;

  /**
   * The number of recovery codes to generate.
   *
   * @var int
   */
  protected $codeLimit = 10;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The lock service.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  protected $lock;

  /**
   * Constructs a new Tfa plugin object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data object to store user specific information.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   Encryption profile manager.
   * @param \Drupal\encrypt\EncryptServiceInterface $encrypt_service
   *   Encryption service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Lock\LockBackendInterface|null $lock
   *   The lock service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserDataInterface $user_data, EncryptionProfileManagerInterface $encryption_profile_manager, EncryptServiceInterface $encrypt_service, ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, $lock = NULL) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $user_data, $encryption_profile_manager, $encrypt_service);

    $codes_amount = $config_factory->get('tfa.settings')->get('validation_plugin_settings.tfa_recovery_code.recovery_codes_amount');
    if (!empty($codes_amount)) {
      $this->codeLimit = $codes_amount;
    }
    $this->currentUser = $current_user;

    if (!$lock) {
      @trigger_error('Constructing ' . __CLASS__ . ' without the lock service parameter is deprecated in tfa:8.x-1.3 and will be required before tfa:2.0.0. See https://www.drupal.org/node/3396512', E_USER_DEPRECATED);
      $lock = \Drupal::service('lock');
    }
    $this->lock = $lock;

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
      $container->get('encrypt.encryption_profile.manager'),
      $container->get('encryption'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('lock')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ready() {
    $codes = $this->getCodes();
    return !empty($codes);
  }

  /**
   * Check if account has access to the user plugin configuration.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The route to be checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account requesting access.
   *
   * @return bool
   *   Returns true if the access is allowed.
   */
  public function allowUserSetupAccess(RouteMatchInterface $route, AccountInterface $account) {
    // Only allow user setup access to the 'show codes' if user is self.
    return (($route->getRouteName() !== 'tfa.validation.setup') || ($this->uid === $account->id()));
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(array $form, FormStateInterface $form_state) {
    $form['code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter one of your recovery codes'),
      '#required' => TRUE,
      '#description' => $this->t('Recovery codes were generated when you first set up TFA. Format: XXX XXX XXX'),
      '#attributes' => ['autocomplete' => 'off'],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['login'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Verify'),
    ];
    return $form;
  }

  /**
   * Configuration form for the recovery code plugin.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Config object for tfa settings.
   * @param array $state
   *   Form state array determines if this form should be shown.
   *
   * @return array
   *   Form array specific for this validation plugin.
   */
  public function buildConfigurationForm(Config $config, array $state = []) {
    $settings_form['recovery_codes_amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Recovery Codes Amount'),
      '#default_value' => $this->codeLimit,
      '#description' => $this->t('Number of Recovery Codes To Generate.'),
      '#min' => 1,
      '#size' => 2,
      '#states' => $state,
      '#required' => TRUE,
    ];

    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    return $this->validate($values['code']);
  }

  /**
   * Simple validate for web services.
   *
   * @param int $code
   *   OTP Code.
   *
   * @return bool
   *   True if validation was successful otherwise false.
   */
  public function validateRequest($code) {
    if ($this->validate($code)) {
      $this->storeAcceptedCode($code);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Generate an array of secure recovery codes.
   *
   * @return array
   *   An array of randomly generated codes.
   *
   * @throws \Exception
   */
  public function generateCodes() {
    $codes = [];

    for ($i = 0; $i < $this->codeLimit; $i++) {
      $codes[] = $this->randomCharacters(9, '1234567890');
    }

    return $codes;
  }

  /**
   * Get unused recovery codes.
   *
   * @todo consider returning used codes so validate() can error with
   * appropriate message
   *
   * @return array
   *   Array of codes indexed by ID.
   *
   * @throws \Drupal\encrypt\Exception\EncryptionMethodCanNotDecryptException
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function getCodes() {
    $codes = $this->getUserData('tfa', 'tfa_recovery_code', $this->uid, $this->userData) ?: [];
    array_walk($codes, function (&$v, $k) {
      $v = $this->decrypt($v);
    });
    return $codes;
  }

  /**
   * Save recovery codes for current account.
   *
   * @param array $codes
   *   Recovery codes for current account.
   *
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  public function storeCodes(array $codes) {
    $this->deleteCodes();

    // Encrypt code for storage.
    array_walk($codes, function (&$v, $k) {
      $v = $this->encrypt($v);
    });
    $data = ['tfa_recovery_code' => $codes];

    $this->setUserData('tfa', $data, $this->uid, $this->userData);
  }

  /**
   * Delete existing codes.
   */
  protected function deleteCodes() {
    // Delete any existing codes.
    $this->deleteUserData('tfa', 'tfa_recovery_code', $this->uid, $this->userData);
  }

  /**
   * {@inheritdoc}
   */
  protected function validate($code) {
    $this->isValid = FALSE;
    $recovery_validation_lock_id = 'tfa_validation_recovery_codes_' . $this->uid;
    while (!$this->lock->acquire($recovery_validation_lock_id)) {
      $this->lock->wait($recovery_validation_lock_id);
    }
    // Get codes and compare.
    $codes = $this->getCodes();
    if (empty($codes)) {
      $this->lock->release($recovery_validation_lock_id);
      $this->errorMessages['recovery_code'] = $this->t('You have no unused codes available.');
      return FALSE;
    }
    // Remove empty spaces.
    $code = str_replace(' ', '', $code);
    foreach ($codes as $id => $stored) {
      // Remove spaces from stored code.
      if (hash_equals(trim(str_replace(' ', '', $stored)), $code)) {
        $this->isValid = TRUE;
        unset($codes[$id]);
        $this->storeCodes($codes);
        $this->lock->release($recovery_validation_lock_id);
        return $this->isValid;
      }
    }
    $this->lock->release($recovery_validation_lock_id);
    $this->errorMessages['recovery_code'] = $this->t('Invalid recovery code.');
    return $this->isValid;
  }

}

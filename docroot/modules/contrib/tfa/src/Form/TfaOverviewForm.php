<?php

namespace Drupal\tfa\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\tfa\TfaUserDataTrait;
use Drupal\tfa\TfaLoginPluginManager;
use Drupal\tfa\TfaSendPluginManager;
use Drupal\tfa\TfaSetupPluginManager;
use Drupal\tfa\TfaValidationPluginManager;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TFA account setup overview page.
 */
class TfaOverviewForm extends FormBase {
  use TfaUserDataTrait;

  /**
   * The setup plugin manager to fetch setup information.
   *
   * @var \Drupal\tfa\TfaLoginPluginManager
   */
  protected $tfaSetup;

  /**
   * Validation plugin manager.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  protected $tfaValidation;

  /**
   * Login plugin manager.
   *
   * @var \Drupal\tfa\TfaLoginPluginManager
   */
  protected $tfaLogin;

  /**
   * Send plugin manager.
   *
   * @var \Drupal\tfa\TfaSendPluginManager
   */
  protected $tfaSend;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * TFA Overview form constructor.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\tfa\TfaSetupPluginManager $tfa_setup_manager
   *   The setup plugin manager.
   * @param \Drupal\tfa\TfaValidationPluginManager $tfa_validation_manager
   *   The validation plugin manager.
   * @param \Drupal\tfa\TfaLoginPluginManager $tfa_login_manager
   *   The login plugin manager.
   * @param \Drupal\tfa\TfaSendPluginManager $tfa_send_manager
   *   The send plugin manager.
   */
  public function __construct(UserDataInterface $user_data, DateFormatterInterface $date_formatter, TfaSetupPluginManager $tfa_setup_manager, TfaValidationPluginManager $tfa_validation_manager, TfaLoginPluginManager $tfa_login_manager, TfaSendPluginManager $tfa_send_manager) {
    $this->userData = $user_data;
    $this->dateFormatter = $date_formatter;
    $this->tfaSetup = $tfa_setup_manager;
    $this->tfaValidation = $tfa_validation_manager;
    $this->tfaLogin = $tfa_login_manager;
    $this->tfaSend = $tfa_send_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.data'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.tfa.setup'),
      $container->get('plugin.manager.tfa.validation'),
      $container->get('plugin.manager.tfa.login'),
      $container->get('plugin.manager.tfa.send')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfa_base_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL) {
    $output['info'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Two-factor authentication (TFA) provides
      additional security for your account. With TFA enabled, you log in to
      the site with a verification code in addition to your username and
      password.') . '</p>',
    ];
    // $form_state['storage']['account'] = $user;.
    $config = $this->config('tfa.settings');
    $user_tfa = $this->tfaGetTfaData($user->id(), $this->userData);
    $enabled = isset($user_tfa['status']) && $user_tfa['status'];

    if (!empty($user_tfa)) {
      if ($enabled && !empty($user_tfa['data']['plugins'])) {
        if ($this->currentUser()->hasPermission('disable own tfa') || $this->currentUser()->hasPermission('administer tfa for other users')) {
          $status_text = $this->t('Status: <strong>TFA enabled</strong>, set
          @time. <a href=":url">Disable TFA</a>', [
            '@time' => $this->dateFormatter->format($user_tfa['saved']),
            ':url' => Url::fromRoute('tfa.disable', ['user' => $user->id()])->toString(),
          ]);
        }
        else {
          $status_text = $this->t('Status: <strong>TFA enabled</strong>, set @time.', [
            '@time' => $this->dateFormatter->format($user_tfa['saved']),
          ]);
        }
      }
      else {
        $status_text = $this->t('Status: <strong>TFA disabled</strong>, set @time.', [
          '@time' => $this->dateFormatter->format($user_tfa['saved']),
        ]);
      }
      $output['status'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $status_text . '</p>',
      ];
    }

    if ($config->get('enabled')) {
      $enabled = isset($user_tfa['status'], $user_tfa['data']) && !empty($user_tfa['data']['plugins']) && $user_tfa['status'];
      $enabled_plugins = $user_tfa['data']['plugins'] ?? [];

      $validation_plugins = $this->tfaValidation->getDefinitions();
      if ($validation_plugins) {
        $output['validation'] = [
          '#type' => 'details',
          '#title' => $this->t('Validation plugins'),
          '#open' => TRUE,
        ];

        foreach ($validation_plugins as $plugin_id => $plugin) {
          if (!empty($config->get('allowed_validation_plugins')[$plugin_id])) {
            $output['validation'][$plugin_id] = $this->tfaPluginSetupFormOverview($plugin, $user, !empty($enabled_plugins[$plugin_id]));
          }
        }
      }

      if ($enabled) {
        $login_plugins = $this->tfaLogin->getDefinitions();
        if ($login_plugins) {
          $output['login'] = [
            '#type' => 'details',
            '#title' => $this->t('Login plugins'),
            '#open' => TRUE,
            '#access' => FALSE,
          ];

          foreach ($login_plugins as $plugin_id => $plugin) {
            if (!empty($config->get('login_plugins')[$plugin_id])) {
              $output['login'][$plugin_id] = $this->tfaPluginSetupFormOverview($plugin, $user, TRUE);
              $output['login']['#access'] = TRUE;
            }
          }
        }

        $send_plugins = $this->tfaSend->getDefinitions();
        if ($send_plugins) {
          $output['send'] = [
            '#type' => 'details',
            '#title' => $this->t('Send plugins'),
            '#open' => TRUE,
          ];

          foreach ($send_plugins as $plugin_id => $plugin) {
            if (!empty($config->get('send_plugins')[$plugin_id])) {
              $output['send'][$plugin_id] = $this->tfaPluginSetupFormOverview($plugin, $user, TRUE);
            }
          }
        }
      }

      $output['validation_skip_status'] = [
        '#type'   => 'markup',
        '#markup' => '<p>' . $this->t('Number of times validation skipped: @skipped of @limit', [
          '@skipped' => $user_tfa['validation_skipped'] ?? 0,
          '@limit' => $config->get('validation_skip'),
        ]) . '</p>',
      ];
    }
    else {
      $output['disabled'] = [
        '#type' => 'markup',
        '#markup' => '<b>Currently there are no enabled plugins.</b>',
      ];
    }

    if ($this->canPerformReset($user)) {
      $output['actions'] = ['#type' => 'actions'];
      $output['actions']['reset_skip_attempts'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset skip validation attempts'),
        '#submit' => ['::resetSkipValidationAttempts'],
      ];
      $output['account'] = [
        '#type' => 'value',
        '#value' => $user,
      ];
    }

    return $output;
  }

  /**
   * Get TFA plugin setup action links for use on overview page.
   *
   * @param array $plugin
   *   Plugin definition.
   * @param object $account
   *   Current user account.
   * @param bool $enabled
   *   Tfa data for current user.
   *
   * @return array
   *   Render array
   */
  protected function tfaPluginSetupFormOverview(array $plugin, $account, $enabled) {
    $params = [
      'enabled' => $enabled,
      'account' => $account,
      'plugin_id' => $plugin['id'],
    ];
    try {
      return $this->tfaSetup
        ->createInstance($plugin['setupPluginId'], ['uid' => $account->id()])
        ->getOverview($params);
    }
    catch (\Exception $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Resets TFA attempts for the given user account.
   *
   * @param array $form
   *   The form definition.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function resetSkipValidationAttempts(array $form, FormStateInterface $form_state) {
    $account = $form_state->getValue('account');
    $tfa_data = $this->tfaGetTfaData($account->id(), $this->userData);
    $tfa_data['validation_skipped'] = 0;
    $this->tfaSaveTfaData($account->id(), $this->userData, $tfa_data);
    $this->messenger()->addMessage($this->t('Validation attempts have been reset for user @name.', [
      '@name' => $account->getDisplayName(),
    ]));
    $this->logger('tfa')->notice('Validation attempts reset for @account by @current_user.', [
      '@account' => $account->getAccountName(),
      '@current_user' => $this->currentUser()->getAccountName(),
    ]);
  }

  /**
   * Determine if the current user can perform a TFA attempt reset.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account that TFA is for.
   *
   * @return bool
   *   Whether the user can perform a TFA reset.
   */
  protected function canPerformReset(UserInterface $account) {
    $current_user = $this->currentUser();
    return $current_user->hasPermission('administer tfa for other users')
      // Disallow users from resetting their own.
      // @todo Make this configurable.
      && $current_user->id() != $account->id();
  }

}

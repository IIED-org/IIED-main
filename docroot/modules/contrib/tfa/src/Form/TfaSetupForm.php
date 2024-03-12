<?php

namespace Drupal\tfa\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\tfa\TfaUserDataTrait;
use Drupal\tfa\TfaLoginPluginManager;
use Drupal\tfa\TfaSendPluginManager;
use Drupal\tfa\TfaSetupPluginManager;
use Drupal\tfa\TfaSetup;
use Drupal\tfa\TfaValidationPluginManager;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * TFA setup form router.
 */
class TfaSetupForm extends FormBase {
  use TfaUserDataTrait;
  use StringTranslationTrait;

  /**
   * The setup plugin manager.
   *
   * @var \Drupal\tfa\TfaSetupPluginManager
   */
  protected $tfaSetup;

  /**
   * The validation plugin manager.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  protected $tfaValidation;

  /**
   * The login plugin manager.
   *
   * @var \Drupal\tfa\TfaLoginPluginManager
   */
  protected $tfaLogin;

  /**
   * The send plugin manager.
   *
   * @var \Drupal\tfa\TfaSendPluginManager
   */
  protected $tfaSend;

  /**
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected $passwordChecker;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * TFA Setup form constructor.
   *
   * @param \Drupal\tfa\TfaSetupPluginManager $tfa_setup_manager
   *   The setup plugin manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data object to store user information.
   * @param \Drupal\tfa\TfaValidationPluginManager $tfa_validation_manager
   *   The validation plugin manager.
   * @param \Drupal\tfa\TfaLoginPluginManager $tfa_login_manager
   *   The login plugin manager.
   * @param \Drupal\tfa\TfaSendPluginManager $tfa_send_manager
   *   The send plugin manager.
   * @param \Drupal\Core\Password\PasswordInterface $password_checker
   *   The password service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(TfaSetupPluginManager $tfa_setup_manager, UserDataInterface $user_data, TfaValidationPluginManager $tfa_validation_manager, TfaLoginPluginManager $tfa_login_manager, TfaSendPluginManager $tfa_send_manager, PasswordInterface $password_checker, MailManagerInterface $mail_manager, UserStorageInterface $user_storage) {
    $this->tfaSetup = $tfa_setup_manager;
    $this->userData = $user_data;
    $this->tfaValidation = $tfa_validation_manager;
    $this->tfaLogin = $tfa_login_manager;
    $this->tfaSend = $tfa_send_manager;
    $this->passwordChecker = $password_checker;
    $this->mailManager = $mail_manager;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.tfa.setup'),
      $container->get('user.data'),
      $container->get('plugin.manager.tfa.validation'),
      $container->get('plugin.manager.tfa.login'),
      $container->get('plugin.manager.tfa.send'),
      $container->get('password'),
      $container->get('plugin.manager.mail'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfa_setup';
  }

  /**
   * Find the correct plugin that is being setup.
   *
   * @param string $plugin_id
   *   Plugin ID.
   *
   * @return array|null
   *   Plugin definitions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function findPlugin($plugin_id) {
    $plugin = $this->tfaValidation->getDefinition($plugin_id, FALSE);
    if (empty($plugin)) {
      $plugin = $this->tfaLogin->getDefinition($plugin_id, FALSE);
    }
    if (empty($plugin)) {
      $plugin = $this->tfaSend->getDefinition($plugin_id, FALSE);
    }

    if (empty($plugin)) {
      throw new PluginNotFoundException($plugin_id, sprintf('The "%s" plugin does not exist.', $plugin_id));
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL, $method = 'tfa_totp', $reset = 0) {
    if (!$this->tfaSetup->hasDefinition($method . '_setup')) {
      throw new NotFoundHttpException($this->t('Plugin @plugin not found.', ['@plugin' => $method]));
    }
    /** @var \Drupal\user\Entity\User $account */
    $account = $this->userStorage->load($this->currentUser()->id());

    $form['account'] = [
      '#type' => 'value',
      '#value' => $user,
    ];
    $tfa_data = $this->tfaGetTfaData($user->id(), $this->userData);
    $enabled = isset($tfa_data['status'], $tfa_data['data']) && !empty($tfa_data['data']['plugins']) && $tfa_data['status'];

    $storage = $form_state->getStorage();
    // Always require a password on the first time through.
    if (empty($storage)) {
      // Allow administrators to change TFA settings for another account.
      if ($account->id() == $user->id() && $account->hasPermission('administer tfa for other users')) {
        $current_pass_description = $this->t('Enter your current password to
        alter TFA settings for account %name.', ['%name' => $user->getAccountName()]);
      }
      else {
        $current_pass_description = $this->t('Enter your current password to continue.');
      }

      $form['current_pass'] = [
        '#type' => 'password',
        '#title' => $this->t('Current password'),
        '#size' => 25,
        '#required' => TRUE,
        '#description' => $current_pass_description,
        '#attributes' => ['autocomplete' => 'off'],
      ];

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#button_type' => 'primary',
        '#value' => $this->t('Confirm'),
      ];

      $form['actions']['cancel'] = [
        '#type' => 'submit',
        '#value' => $this->t('Cancel'),
        '#limit_validation_errors' => [],
        '#submit' => ['::cancelForm'],
      ];
    }
    else {
      if (!$enabled && empty($storage['steps'])) {
        $storage['full_setup'] = TRUE;
        $steps = $this->tfaFullSetupSteps();
        $storage['steps_left'] = $steps;
        $storage['steps_skipped'] = [];
      }

      if (isset($storage['step_method'])) {
        $method = $storage['step_method'];
      }

      // Record methods progressed.
      $storage['steps'][] = $method;
      $plugin = $this->findPlugin($method);
      $setup_plugin = $this->tfaSetup->createInstance($plugin['setupPluginId'], ['uid' => $user->id()]);
      $tfa_setup = new TfaSetup($setup_plugin);
      $form = $tfa_setup->getForm($form, $form_state, $reset);
      $storage[$method] = $tfa_setup;

      $form['actions']['#type'] = 'actions';
      if (isset($storage['full_setup']) && count($storage['steps']) > 1) {
        $count = count($storage['steps_left']);
        $form['actions']['skip'] = [
          '#type' => 'submit',
          '#value' => $count > 0 ? $this->t('Skip') : $this->t('Skip and finish'),
          '#limit_validation_errors' => [],
          '#submit' => ['::cancelForm'],
        ];
      }
      // Provide cancel button on first step or single steps.
      else {
        $form['actions']['cancel'] = [
          '#type' => 'submit',
          '#value' => $this->t('Cancel'),
          '#limit_validation_errors' => [],
          '#submit' => ['::cancelForm'],
        ];
      }
      // Record the method in progress regardless of whether in full setup.
      $storage['step_method'] = $method;
    }
    $form_state->setStorage($storage);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\user\Entity\User $user */
    $user = $this->userStorage->load($this->currentUser()->id());
    $storage = $form_state->getStorage();
    $values = $form_state->getValues();
    $account = $form['account']['#value'];
    if (isset($values['current_pass'])) {
      // Allow administrators to change TFA settings for another account using
      // their own password.
      if ($account->id() != $user->id()) {
        if ($user->hasPermission('administer tfa for other users')) {
          $account = $user;
        }
        // If current user lacks admin permissions, kick them out.
        else {
          throw new NotFoundHttpException();
        }
      }
      $current_pass = $this->passwordChecker->check(trim($form_state->getValue('current_pass')), $account->getPassword());
      if (!$current_pass) {
        $form_state->setErrorByName('current_pass', $this->t("Incorrect password."));
      }
      return;
    }
    elseif (!empty($storage['step_method'])) {
      $method = $storage['step_method'];
      $tfa_setup = $storage[$method];
      // Validate plugin form.
      if (!$tfa_setup->validateForm($form, $form_state)) {
        $messages = $tfa_setup->getErrorMessages();
        if (!empty($messages)) {
          foreach ($messages as $element => $message) {
            $form_state->setErrorByName($element, $message);
          }
        }
      }
    }
  }

  /**
   * Form cancel handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $account = $form['account']['#value'];
    $this->messenger()->addWarning($this->t('TFA setup canceled.'));
    $form_state->setRedirect('tfa.overview', ['user' => $account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $form['account']['#value'];
    $storage = $form_state->getStorage();
    $values = $form_state->getValues();

    // Password validation.
    if (isset($values['current_pass'])) {
      $storage['pass_confirmed'] = TRUE;
      $form_state->setRebuild();
      $form_state->setStorage($storage);
      return;
    }
    elseif (!empty($storage['step_method'])) {
      $method = $storage['step_method'];
      $skipped_method = FALSE;

      // Support skipping optional steps when in full setup.
      if (isset($values['skip']) && $values['op'] === $values['skip']) {
        $skipped_method = $method;
        $storage['steps_skipped'][] = $method;
        unset($storage[$method]);
      }

      if (!empty($storage[$method])) {
        // Trigger multi-step if in full setup.
        if (!empty($storage['full_setup'])) {
          $this->tfaNextSetupStep($form_state, $method, $storage[$method], $skipped_method);
        }

        // Plugin form submit.
        $setup_class = $storage[$method];
        if (!$setup_class->submitForm($form, $form_state)) {
          $this->messenger()->addError($this->t('There was an error during TFA setup. Your settings have not been saved.'));
          $form_state->setRedirect('tfa.overview', ['user' => $account->id()]);
          return;
        }
      }

      // Return if multi-step.
      if ($form_state->getRebuildInfo()) {
        return;
      }
      // Else, setup complete and return to overview page.
      $this->messenger()->addStatus($this->t('TFA setup complete.'));
      $form_state->setRedirect('tfa.overview', ['user' => $account->id()]);

      // Log and notify if this was full setup.
      if (!empty($storage['step_method'])) {
        $data = ['plugins' => $storage['step_method']];
        $this->tfaSaveTfaData($account->id(), $this->userData, $data);
        $this->logger('tfa')->info('TFA enabled for user @name UID @uid', [
          '@name' => $account->getAccountName(),
          '@uid' => $account->id(),
        ]);

        $params = ['account' => $account];
        $this->mailManager->mail('tfa', 'tfa_enabled_configuration', $account->getEmail(), $account->getPreferredLangcode(), $params);
      }
    }
  }

  /**
   * Steps eligible for TFA setup.
   */
  private function tfaFullSetupSteps() {
    $config = $this->config('tfa.settings');
    $steps = [
      $config->get('default_validation_plugin'),
    ];

    $login_plugins = $config->get('login_plugins');

    foreach ($login_plugins as $login_plugin) {
      $steps[] = $login_plugin;
    }

    // @todo Add send plugins.
    return $steps;
  }

  /**
   * Set form rebuild, next step, and message if any plugin steps left.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string $this_step
   *   The current setup step.
   * @param \Drupal\tfa\TfaSetup $step_class
   *   The setup instance of the current step.
   * @param bool $skipped_step
   *   Whether the step was skipped.
   */
  private function tfaNextSetupStep(FormStateInterface &$form_state, $this_step, TfaSetup $step_class, $skipped_step = FALSE) {
    $storage = $form_state->getStorage();
    // Remove this step from steps left.
    $storage['steps_left'] = array_diff($storage['steps_left'], [$this_step]);
    if (!empty($storage['steps_left'])) {
      // Contextual reporting.
      if ($output = $step_class->getSetupMessages()) {
        $output = $skipped_step ? $output['skipped'] : $output['saved'];
      }
      $count = count($storage['steps_left']);
      $output .= ' ' . $this->formatPlural($count, 'One setup step remaining.', '@count TFA setup steps remain.', ['@count' => $count]);
      if ($output) {
        $this->messenger()->addStatus($output);
      }

      // Set next step and mark form for rebuild.
      $next_step = array_shift($storage['steps_left']);
      $storage['step_method'] = $next_step;
      $form_state->setRebuild();
    }
    $form_state->setStorage($storage);
  }

}

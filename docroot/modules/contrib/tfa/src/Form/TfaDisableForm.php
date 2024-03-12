<?php

namespace Drupal\tfa\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\tfa\TfaUserDataTrait;
use Drupal\tfa\TfaValidationPluginManager;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TFA disable form router.
 */
class TfaDisableForm extends FormBase {
  use TfaUserDataTrait;

  /**
   * The validation plugin manager.
   *
   * @var \Drupal\tfa\TfaValidationPluginManager
   */
  protected $manager;

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
   * TFA disable form constructor.
   *
   * @param \Drupal\tfa\TfaValidationPluginManager $manager
   *   The validation plugin manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data object to store user information.
   * @param \Drupal\Core\Password\PasswordInterface $password_checker
   *   The password service.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(TfaValidationPluginManager $manager, UserDataInterface $user_data, PasswordInterface $password_checker, MailManagerInterface $mail_manager, UserStorageInterface $user_storage) {
    $this->manager = $manager;
    $this->userData = $user_data;
    $this->passwordChecker = $password_checker;
    $this->mailManager = $mail_manager;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.tfa.validation'),
      $container->get('user.data'),
      $container->get('password'),
      $container->get('plugin.manager.mail'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tfa_disable';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, User $user = NULL) {
    /** @var \Drupal\user\Entity\User $account */
    $account = $this->userStorage->load($this->currentUser()->id());

    $storage = $form_state->getStorage();
    $storage['account'] = $user;

    // @todo Check require permissions and give warning about being locked out.
    if ($account->id() != $user->id() && $account->hasPermission('administer tfa for other users')) {
      $preamble_desc = $this->t('Are you sure you want to disable TFA for user %name?', ['%name' => $user->getDisplayName()]);
      $notice_desc = $this->t('TFA settings and data will be lost. %name can re-enable TFA again from their profile.', ['%name' => $user->getDisplayName()]);
    }
    else {
      $preamble_desc = $this->t('Are you sure you want to disable your two-factor authentication setup?');
      $notice_desc = $this->t("Your settings and data will be lost. You can re-enable two-factor authentication again from your profile.");
    }
    $form['preamble'] = [
      '#prefix' => '<p class="preamble">',
      '#suffix' => '</p>',
      '#markup' => $preamble_desc,
    ];
    $form['notice'] = [
      '#prefix' => '<p class="preamble">',
      '#suffix' => '</p>',
      '#markup' => $notice_desc,
    ];

    $form['account']['current_pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Confirm your current password'),
      '#description_display' => 'before',
      '#size' => 25,
      '#weight' => -5,
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
    ];
    $form['account']['mail'] = [
      '#type' => 'value',
      '#value' => $user->getEmail(),
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Disable'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelForm'],
    ];

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
    $account = $storage['account'];
    // Allow administrators to disable TFA for another account.
    if ($account->id() != $user->id() && $user->hasPermission('administer tfa for other users')) {
      $account = $user;
    }
    // Check password.
    $current_pass = $this->passwordChecker->check(trim($form_state->getValue('current_pass')), $account->getPassword());
    if (!$current_pass) {
      $form_state->setErrorByName('current_pass', $this->t("Incorrect password."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage();
    $values = $form_state->getValues();
    $account = $storage['account'];
    if ($values['op'] === $values['cancel']) {
      $this->messenger()->addStatus($this->t('TFA disable cancelled.'));
      $form_state->setRedirect('tfa.overview', ['user' => $account->id()]);
      return;
    }

    // Delete all user data.
    $this->deleteUserData('tfa', NULL, $account->id(), $this->userData);

    $this->logger('tfa')->notice('TFA disabled for user @name UID @uid', [
      '@name' => $account->getAccountName(),
      '@uid' => $account->id(),
    ]);

    // E-mail account to inform user that it has been disabled.
    $params = ['account' => $account];
    $this->mailManager->mail('tfa', 'tfa_disabled_configuration', $account->getEmail(), $account->getPreferredLangcode(), $params);

    $this->messenger()->addStatus($this->t('TFA has been disabled.'));
    $form_state->setRedirect('tfa.overview', ['user' => $account->id()]);
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
    $this->messenger()->addWarning($this->t('TFA Disable cancelled.'));
    $form_state->setRedirect('tfa.overview', ['user' => $this->currentUser()->id()]);
  }

}

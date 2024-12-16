<?php

declare(strict_types=1);

namespace Drupal\tfa\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\tfa\TfaUserDataTrait;
use Drupal\user\UserDataInterface;
use Drush\Exceptions\UserAbortException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Provides management of tokens via Drush.
 *
 * @internal
 */
final class TfaTokenManagement {
  use TfaUserDataTrait;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The user data object to store user information.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service for {tfa} channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * TFA token management class constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data object to store user information.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager .
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.channel.tfa service.
   */
  public function __construct(MailManagerInterface $mail_manager, UserDataInterface $user_data, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->mailManager = $mail_manager;
    $this->userData = $user_data;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Resets single user's TFA Data.
   *
   * @param array $options
   *   Options passed from the Drush CLI.
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The Drush I/O system.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function resetUserTfaData(array $options, SymfonyStyle $io): void {

    $account = NULL;
    $accounts = [];
    if (!is_null($options['name']) && !$accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $options['name']])) {
      throw new \Exception(dt('Unable to load user by name: !name', ['!name' => $options['name']]));
    }
    if (!is_null($options['uid']) && !$account = $this->entityTypeManager->getStorage('user')->load($options['uid'])) {
      throw new \Exception(dt('Unable to load user by uid: !uid', ['!uid' => $options['uid']]));
    }
    if (!is_null($options['mail']) && !$accounts = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $options['mail']])) {
      throw new \Exception(dt('Unable to load user by mail: !mail', ['!mail' => $options['mail']]));
    }

    if ($accounts) {
      $account = reset($accounts);
    }

    if (empty($account)) {
      throw new \Exception(dt('Invalid user account details provided.'));
    }

    $do_run_if_no_input = FALSE;
    /** @var \Drupal\user\UserInterface $account */
    $uid = (int) $account->id();
    $name = $account->getAccountName();
    $email = $account->getEmail() ?? '';
    $answer = $io->confirm(
      dt(
        "Are you sure you want to reset TFA for @name (UID: @uid)'s data?",
        [
          '@name' => $name,
          '@uid' => $uid,
        ]
      ),
      $do_run_if_no_input
    );

    if (!$answer) {
      throw new UserAbortException("Command cancelled.");
    }

    // Delete all user data.
    $this->deleteUserData('tfa', NULL, $uid, $this->userData);

    $this->logger->notice(
      "TFA deleted and reset for user @name (UID: @uid).",
      [
        '@name' => $name,
        '@uid' => $uid,
      ]
    );

    // E-mail account to inform user that it has been disabled.
    $params = ['account' => $account];
    if (!empty($email)) {
      $this->mailManager->mail('tfa', 'tfa_disabled_configuration', $email, $account->getPreferredLangcode(), $params);
    }

    $io->writeln('TFA has been disabled.');
  }

}

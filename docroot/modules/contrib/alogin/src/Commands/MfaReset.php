<?php

namespace Drupal\alogin\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Connection;

class MfaReset extends DrushCommands {
  /**
  * The database connection.
  *
  * @var \Drupal\Core\Database\Connection
  */
  protected $database;
  /**
   * MfaResetCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    parent::__construct();
    $this->database = $database;
  }
  /**
   * Drush command to reset Authenticator MFA on a user.
   *
   * @command mfa-reset
   * @param $uid of the user whose Authenticator MFA to be reset.
   * @aliases mfar
   */
  public function reset($uid) {
    if ($this->database->schema()->tableExists('alogin_user_settings')) {
      $found = $this->database->select('alogin_user_settings', 'aus')
            ->fields('aus', [])
            ->condition('uid', $uid)
            ->execute()
            ->fetchAssoc();
      if ($found) {
        $this->database->delete("alogin_user_settings")
             ->condition('uid', $uid)
             ->execute();
        $this->output()->writeln("The Authenticator MFA for user $uid reset successfully.");
      } else {
        $this->output()->writeln("The Authenticator MFA for user $uid is already disabled.");
      }
    }
  }
}

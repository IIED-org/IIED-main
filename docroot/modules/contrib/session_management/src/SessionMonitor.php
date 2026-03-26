<?php

namespace Drupal\session_management;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;

/**
 * Class to read/update/delete the user session data.
 */
class SessionMonitor implements SessionMonitorInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The session Manager service.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  public function __construct(Connection $database, SessionManagerInterface $sessionManager) {
    $this->database = $database;
    $this->sessionManager = $sessionManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSession($sid): array {

    $query = $this->database->select('sessions', 's');
    $query->fields('s', ['uid', 'sid', 'hostname', 'timestamp', 'session']);
    $query->condition('s.sid', $sid);

    return $query->execute()->fetch();
  }

  /**
   * {@inheritdoc}
   */
  public function getSessions(AccountInterface $account): array {

    $query = $this->database->select('sessions', 's');
    $query->fields('s', ['uid', 'sid', 'hostname', 'timestamp', 'session']);
    $query->condition('s.uid', $account->id())->orderBy('timestamp');


    $results = $query->execute()->fetchAll();

    $all_sessions = [];

    foreach ($results as $id => $result) {

      $all_sessions[$id] = [
        'uid' => $result->uid,
        'sid' => $result->sid,
        'hostname' => $result->hostname,
        'timestamp' => $result->timestamp,
        'session' => $result->session,
      ];

    }

    return $all_sessions;
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentActiveSession(string $session_id): bool {
    return Crypt::hashBase64($this->sessionManager->getId()) === $session_id;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSession($sid): void {

    $txn = $this->database->startTransaction();

    try {
      $query = $this->database->delete('sessions',);
      $query->condition('sid', $sid);
      $query->execute();
    }
    catch (\Exception $e) {
      $txn->rollBack();
      \Drupal::logger('session_management')->error($e->getMessage());
    }

  }

  /**
   * Clear the current user session
   * @return void
   *
   */
  public function clear(){
    $this->sessionManager->clear();
  }

  /**
   * Return the user session data.
   *
   * @param string $session
   *   The session value.
   *
   * @return array [
   *   '_sf2_attributes' =>  [],
   *   '_sf2_meta' => [
   *   'u' => int
   *   'c' => int
   *   'l' => int
   *   's' => string
   *   ]
   *   ]
   */
  public function getStoredSessionData(string $session): array {

    $result = [];

    $result['_sf2_attributes'] = @unserialize(explode('_sf2_attributes|', $session)[1] ?? '');
    $result['_sf2_meta'] = @unserialize(explode('_sf2_meta|', $session)[1] ?? '');

    return $result;
  }

}

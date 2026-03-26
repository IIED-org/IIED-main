<?php

namespace Drupal\session_management;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for user session RUD operation.
 */
interface SessionMonitorInterface {

  /**
   * Get user session from session ID.
   *
   * @param string $sid
   *   The session ID of the user.
   *
   * @return array
   *   The corresponding user session for provided $sid
   */
  public function getSession($sid): array;

  /**
   * List all the active sessions of the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account object.
   *
   * @return array
   *   All active user session.
   */
  public function getSessions(AccountInterface $account): array;

  /**
   * Check if provided $session_id is of current user or not.
   *
   * @param string $session_id
   *   Session id.
   *
   * @return bool
   *   True if given session_id is currently active session, else false
   */
  public function isCurrentActiveSession(string $session_id): bool;

  /**
   * Delete the provided session ID from session table.
   *
   * @param string $sid
   *   Session ID to be deleted.
   */
  public function deleteSession(string $sid): void;

  /**
   * Return the user session data.
   * 
   * @param string $session_id
   *   The session ID.
   *
   * @return array
   *   The user session data.
   */
  public function getStoredSessionData(string $session_id): array;

}

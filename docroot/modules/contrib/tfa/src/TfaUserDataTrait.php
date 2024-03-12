<?php

namespace Drupal\tfa;

use Drupal\user\UserDataInterface;

/**
 * Provides methods to save tfa user settings.
 */
trait TfaUserDataTrait {

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Store user specific information.
   *
   * @param string $module
   *   The name of the module the data is associated with.
   * @param array $data
   *   The value to store. Non-scalar values are serialized automatically.
   * @param int $uid
   *   The user id.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data object to store user specific information.
   */
  protected function setUserData($module, array $data, $uid, UserDataInterface $user_data) {
    $user_data->set(
      $module,
      $uid,
      key($data),
      current($data)
    );
  }

  /**
   * Returns data stored for the current validated user account.
   *
   * @param string $module
   *   The name of the module the data is associated with.
   * @param string $key
   *   The name of the data key.
   * @param int $uid
   *   The user id.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data object to store user specific information.
   *
   * @return mixed|array
   *   The stored value is returned, or NULL if no value was found.
   */
  protected function getUserData($module, $key, $uid, UserDataInterface $user_data) {
    return $user_data->get($module, $uid, $key);
  }

  /**
   * Deletes data stored for the current validated user account.
   *
   * @param string $module
   *   The name of the module the data is associated with.
   * @param string $key
   *   The name of the data key.
   * @param int $uid
   *   The user id.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data object to store user specific information.
   */
  protected function deleteUserData($module, $key, $uid, UserDataInterface $user_data) {
    $user_data->delete($module, $uid, $key);
  }

  /**
   * Save TFA data for an account.
   *
   * The data like status of tfa, timestamp of last activation
   * or deactivation etc. is stored here.
   *
   * @param int $uid
   *   The user id.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data.
   * @param array $data
   *   Data to be saved.
   */
  public function tfaSaveTfaData($uid, UserDataInterface $user_data, array $data = []) {
    // Check if existing data and update.
    $existing = $this->tfaGetTfaData($uid, $user_data);

    if (isset($existing['validation_skipped']) && !isset($data['validation_skipped'])) {
      $validation_skipped = $existing['validation_skipped'];
    }
    else {
      $validation_skipped = $data['validation_skipped'] ?? 0;
    }

    if (!empty($existing['data'])) {
      $tfa_data = $existing['data'];
    }
    else {
      $tfa_data = [
        'plugins' => [],
        'sms' => FALSE,
      ];
    }
    if (isset($data['plugins'])) {
      $tfa_data['plugins'][$data['plugins']] = $data['plugins'];
    }
    if (isset($data['sms'])) {
      $tfa_data['sms'] = $data['sms'];
    }

    $status = 1;
    if (isset($data['status']) && $data['status'] === FALSE) {
      $tfa_data = [];
      $status = 0;
    }

    $record = [
      'tfa_user_settings' => [
        'saved' => \Drupal::time()->getRequestTime(),
        'status' => $status,
        'data' => $tfa_data,
        'validation_skipped' => $validation_skipped,
      ],
    ];

    $this->setUserData('tfa', $record, $uid, $user_data);
  }

  /**
   * Get TFA data for an account.
   *
   * @param int $uid
   *   User account id.
   * @param \Drupal\user\UserDataInterface $user_data
   *   User data object to store user specific information.
   *
   * @return array
   *   TFA data.
   */
  protected function tfaGetTfaData($uid, UserDataInterface $user_data) {
    $result = $this->getUserData('tfa', 'tfa_user_settings', $uid, $user_data);

    if (!empty($result)) {
      return [
        'status' => $result['status'] == '1',
        'saved' => $result['saved'],
        'data' => $result['data'],
        'validation_skipped' => $result['validation_skipped'],
      ];
    }
    return [];
  }

}

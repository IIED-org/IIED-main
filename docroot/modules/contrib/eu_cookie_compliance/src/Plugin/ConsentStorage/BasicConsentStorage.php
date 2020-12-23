<?php

namespace Drupal\eu_cookie_compliance\Plugin\ConsentStorage;

use Drupal\eu_cookie_compliance\Plugin\ConsentStorageBase;

/**
 * Provides a database storage for cookie consents.
 *
 * @ConsentStorage(
 *   id = "basic",
 *   name = @Translation("Basic storage"),
 *   description = @Translation("Basic storage")
 * )
 */
class BasicConsentStorage extends ConsentStorageBase {

  /**
   * {@inheritdoc}
   */
  public function registerConsent($consent_type) {
    $revision_id = $this->getCurrentPolicyNodeRevision();
    $timestamp = time();
    $ip_address = \Drupal::request()->getClientIp();
    $uid = \Drupal::currentUser()->id();

    \Drupal::database()->insert('eu_cookie_compliance_basic_consent')->fields(
      [
        'uid' => $uid,
        'ip_address' => $ip_address,
        'timestamp' => $timestamp,
        'revision_id' => $revision_id ? $revision_id : 0 ,
        'consent_type' => $consent_type,
      ]
    )->execute();
    return TRUE;
  }

}

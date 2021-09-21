<?php

namespace Drupal\acquia_search;

/**
 * Class CryptConnector.
 *
 * @package Drupal\acquia_search
 */
class AcquiaCryptConnector {

  /**
   * Derive a key for the solr hmac using a salt, id and key.
   *
   * @param string $salt
   *   Salt.
   * @param string $id
   *   Acquia Search Core ID.
   * @param string $key
   *   Acquia Subscription Key.
   *
   * @return string
   *   Derived Key.
   */
  public static function createDerivedKey($salt, $id, $key) {
    $derivation_string = $id . 'solr' . $salt;
    return hash_hmac('sha1', str_pad($derivation_string, 80, $derivation_string), $key);
  }

}

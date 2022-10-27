<?php

namespace Drupal\acquia_connector\Client;

use Drupal\acquia_connector\CryptConnector;
use Drupal\Component\Utility\Crypt;

/**
 * Helper Class to sign requests going to Acquia Services.
 */
class AcquiaConnectorAuth {

  /**
   * Build authenticator to sign requests to Acquia.
   *
   * @param string $key
   *   Secret key to use for signing the request.
   * @param int $request_time
   *   Such as from \Drupal::time()->getRequestTime().
   * @param array $params
   *   Optional parameters to include.
   *   'identifier' - Network Identifier.
   *
   * @return array
   *   Authenticator array.
   */
  public function __construct($key, int $request_time, array $params = []) {
    $authenticator = [];
    if (isset($params['identifier'])) {
      // Put Subscription ID in authenticator but do not use in hash.
      $authenticator['identifier'] = $params['identifier'];
      unset($params['identifier']);
    }
    $nonce = $this->getNonce();
    $authenticator['time'] = $request_time;
    $authenticator['hash'] = $this->hash($key, $request_time, $nonce);
    $authenticator['nonce'] = $nonce;

    return $authenticator;
  }

  /**
   * Calculates a HMAC-SHA1 according to RFC2104.
   *
   * @param string $key
   *   Key.
   * @param int $time
   *   Timestamp.
   * @param string $nonce
   *   Nonce.
   *
   * @return string
   *   HMAC-SHA1 hash.
   *
   * @see http://www.ietf.org/rfc/rfc2104.txt
   */
  protected function hash($key, $time, $nonce) {
    $string = $time . ':' . $nonce;
    return CryptConnector::acquiaHash($key, $string);
  }

  /**
   * Get a random base 64 encoded string.
   *
   * @return string
   *   Random base 64 encoded string.
   */
  protected function getNonce() {
    return Crypt::hashBase64(uniqid(mt_rand(), TRUE) . random_bytes(55));
  }

}

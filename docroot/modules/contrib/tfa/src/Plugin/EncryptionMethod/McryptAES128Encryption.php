<?php

namespace Drupal\tfa\Plugin\EncryptionMethod;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;

/**
 * Deprecated Mcrypt AES 128 encryption plugin.
 *
 * @package Drupal\encrypt\Plugin\EncryptionMethod
 *
 * @EncryptionMethod(
 *   id = "mcrypt_aes_128",
 *   title = @Translation("Mcrypt AES 128"),
 *   description = "This uses PHP OpenSSL or Mcrypt extensions and <a href='http://en.wikipedia.org/wiki/Advanced_Encryption_Standard'>AES-128</a>.",
 *   key_type = {"encryption"},
 *   can_decrypt = TRUE,
 *   deprecated = TRUE
 * )
 *
 * phpcs:disable PHPCompatibility
 */
class McryptAES128Encryption extends EncryptionMethodBase implements EncryptionMethodInterface {
  use StringTranslationTrait;

  const CRYPT_VERSION = 1;

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key) {
    // Backwards compatibility with Mcrypt.
    if (!extension_loaded('openssl') && extension_loaded('mcrypt')) {
      return $this->encryptWithMcrypt($text, $key);
    }

    // Encrypt using OpenSSL.
    $iv = random_bytes(16);
    $ciphertext = openssl_encrypt($text, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    $crypto_data = [
      'version' => self::CRYPT_VERSION,
      'iv_base64' => base64_encode($iv),
      'ciphertext_base64' => base64_encode($ciphertext),
    ];
    return Json::encode($crypto_data);
  }

  /**
   * Encrypt using the deprecated Mcrypt extension.
   *
   * @param string $text
   *   The text to be encrypted.
   * @param string $key
   *   The key to encrypt the text with.
   *
   * @return string
   *   The encrypted text.
   *
   * @noinspection PhpDeprecationInspection
   */
  private function encryptWithMcrypt($text, $key) {
    // Key cannot be too long for this encryption.
    $key = mb_substr($key, 0, 32);

    // Define iv cipher.
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

    $processed_text = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_ECB, $iv);
    $processed_text = base64_encode($processed_text);

    return $processed_text;
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key) {
    $crypto_data = Json::decode($text);
    if (empty($crypto_data['version']) || empty($crypto_data['iv_base64']) || empty($crypto_data['ciphertext_base64'])) {
      // Backwards compatibility with the old Mcrypt scheme.
      return extension_loaded('mcrypt') ? $this->decryptLegacyDataWithMcrypt($text, $key) : $this->decryptLegacyDataWithOpenSsl($text, $key);
    }
    else {
      $iv = base64_decode($crypto_data['iv_base64']);
      $ciphertext = base64_decode($crypto_data['ciphertext_base64']);
      return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, TRUE, $iv);
    }
  }

  /**
   * Use OpenSSL to decrypt data that was originally encrypted with Mcrypt.
   *
   * @param string $text
   *   The text to be decrypted.
   * @param string $key
   *   The key to decrypt the text with.
   *
   * @return string|bool
   *   The decrypted text, or FALSE on failure.
   */
  private function decryptLegacyDataWithOpenSsl($text, $key) {
    $key = mb_substr($key, 0, 32);
    $text = base64_decode($text);

    return openssl_decrypt($text, 'aes-128-cbc', $key, OPENSSL_NO_PADDING);
  }

  /**
   * Decrypt using the deprecated Mcrypt extension.
   *
   * @param string $text
   *   The text to be decrypted.
   * @param string $key
   *   The key to decrypt the text with.
   *
   * @return string
   *   The decrypted text
   *
   * @noinspection PhpDeprecationInspection
   */
  private function decryptLegacyDataWithMcrypt($text, $key) {
    // Key cannot be too long for this encryption.
    $key = mb_substr($key, 0, 32);

    // Define iv cipher.
    $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
    $text = base64_decode($text);

    // Decrypt text.
    return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $text, MCRYPT_MODE_ECB, $iv));
  }

  /**
   * Check dependencies for the encryption method.
   *
   * @param string $text
   *   The text to be checked.
   * @param string $key
   *   The key to be checked.
   *
   * @return array
   *   An array of error messages, providing info on missing dependencies.
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];

    if (!extension_loaded('openssl') && !extension_loaded('mcrypt')) {
      $errors[] = $this->t('OpenSSL and Mcrypt extensions are not installed.');
    }

    // Check if we have a 128 bit key.
    if (strlen($key) != 16) {
      $errors[] = $this->t('This encryption method requires a 128 bit key.');
    }

    return $errors;
  }

}

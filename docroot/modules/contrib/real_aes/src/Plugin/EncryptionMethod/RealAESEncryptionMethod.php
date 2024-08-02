<?php

namespace Drupal\real_aes\Plugin\EncryptionMethod;

use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Exception\EncryptException;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Encoding;
use Defuse\Crypto\Exception\CryptoException;

/**
 * Provides an encryption method for using AES encryption.
 *
 * @EncryptionMethod(
 *   id = "real_aes",
 *   title = @Translation("Authenticated AES (Real AES)"),
 *   description = "Authenticated encryption based on AES-256 in CBC mode.",
 *   key_type_group = {"encryption"},
 *   can_decrypt = TRUE
 * )
 */
class RealAESEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface {

  /**
   * {@inheritdoc}
   */
  public function checkDependencies($text = NULL, $key = NULL) {
    $errors = [];

    if (!class_exists('\Defuse\Crypto\Crypto')) {
      $errors[] = $this->t('Defuse PHP Encryption library is not correctly installed.');
    }

    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt($text, $key, $options = []) {
    try {
      // Defuse PHP-Encryption requires a key object instead of a string.
      $key = Encoding::saveBytesToChecksummedAsciiSafeString(Key::KEY_CURRENT_VERSION, $key);
      $key = Key::loadFromAsciiSafeString($key);
      return Crypto::encrypt((string) $text, $key);
    }
    catch (CryptoException $ex) {
      throw new EncryptException($ex->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt($text, $key, $options = []) {
    try {
      // Defuse PHP-Encryption requires a key object instead of a string.
      $key = Encoding::saveBytesToChecksummedAsciiSafeString(Key::KEY_CURRENT_VERSION, $key);
      $key = Key::loadFromAsciiSafeString($key);
      return Crypto::decrypt((string) $text, $key);
    }
    catch (CryptoException $ex) {
      throw new EncryptException($ex->getMessage());
    }
  }

}

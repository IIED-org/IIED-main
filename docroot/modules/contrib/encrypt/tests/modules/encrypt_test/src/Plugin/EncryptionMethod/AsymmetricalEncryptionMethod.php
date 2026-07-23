<?php

namespace Drupal\encrypt_test\Plugin\EncryptionMethod;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\encrypt\Attribute\EncryptionMethod;
use Drupal\encrypt\EncryptionMethodInterface;
use Drupal\encrypt\Plugin\EncryptionMethod\EncryptionMethodBase;
use Drupal\encrypt_test\Exception\AsymmetricalEncryptionMethodCanNotDecryptException;

/**
 * Encryption-only encryption method, it can NOT decrypt.
 */
#[EncryptionMethod(
  id: 'asymmetrical_encryption_method',
  title: new TranslatableMarkup('Asymmetrical Encryption method'),
  description: new TranslatableMarkup('A method which can only encrypt but not decrypt.'),
  key_type: ['encryption'],
  can_decrypt: FALSE,
)]
class AsymmetricalEncryptionMethod extends EncryptionMethodBase implements EncryptionMethodInterface {

  /**
   * {@inheritdoc}
   */
  public function checkDependencies(#[\SensitiveParameter] $text = NULL, #[\SensitiveParameter] $key = NULL) {
    $errors = [];
    return $errors;
  }

  /**
   * {@inheritdoc}
   */
  public function encrypt(#[\SensitiveParameter] $text, #[\SensitiveParameter] $key, $options = []) {
    return '###encrypted###';
  }

  /**
   * {@inheritdoc}
   */
  public function decrypt(#[\SensitiveParameter] $text, #[\SensitiveParameter] $key, $options = []) {
    // This method should throw EncryptionMethodCanNotDecryptException, however
    // if we do it here from the test we won't be able to understand if the
    // exception is thrown by the 'encryption' service or by this method. In a
    // normal scenario method with 'can_decrypt' FALSE can and should throw
    // EncryptionMethodCanNotDecryptException.
    throw new AsymmetricalEncryptionMethodCanNotDecryptException();
  }

}

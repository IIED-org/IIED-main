<?php

namespace Drupal\encrypt\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an EncryptionMethod attribute.
 *
 * @ingroup encrypt
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class EncryptionMethod extends Plugin {

  /**
   * Constructs an EncryptionMethod.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The human-readable name of the encryption method.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The description shown to users.
   * @param array<string> $key_type
   *   (optional) Define the key type(s) to restrict this encryption method to.
   *
   *   Return an array of KeyType plugin IDs that restrict the allowed key types
   *   that can be used with this encryption method.
   * @param bool $can_decrypt
   *   (optional) Define whether the encryption method can also decrypt.
   *
   *   In some scenarios, the key linked to the encryption method may not be
   *   able to decrypt (i.e., asymmetrical encryption methods in which the key
   *   is a public key).
   * @param bool $deprecated
   *   (optional) Define whether the encryption method is deprecated.
   *
   *   As time passes, some encryption methods become obsolete, and it is
   *   necessary to stop them from being used to create new encryption profiles.
   *   Encryption methods marked deprecated can only be used with existing
   *   profiles, and the user will be alerted to change to a better method.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $key_type = [],
    public readonly bool $can_decrypt = TRUE,
    public readonly bool $deprecated = FALSE,
  ) {}

}

# Encrypt

Encrypt is a Drupal module that provides an application programming interface
(API) for performing symmetric or asymmetric encryption. It allows integrating
modules to encrypt and decrypt data in a standardized manner throughout the
Drupal ecosystem. It does not provide any user-facing features of its own,
aside from administration pages to manage and test encryption profiles.


## Architecture

### High-level overview

Encrypt uses encryption profiles, which require an encryption method and a key.
Based on the encryption method(s) you want to use, generate one or more keys,
and then create encryption profiles.

### Developer overview

The Encrypt module leverages the Drupal Plugin API to define an
`EncryptionMethod` plugin type, which other modules implement.

The encryption service (`EncryptService`) is the public entry point
(`encrypt()` and `decrypt()`).

An encryption profile is a config entity (provided by this module) that
combines an encryption method plugin (provided by another module; see
[Encryption methods](#encryption-methods)) with a key config entity (provided
by the Key module).

Because profiles are config entities, they are deployable via config management,
and the sensitive key value stays with the Key provider, keeping it securely out
of config.


## Requirements

This module requires the following modules:

- [Key](https://www.drupal.org/project/key)
- At least one module that provides an encryption method plugin (recommended
  default: [Real AES](https://www.drupal.org/project/real_aes))


## Installation

Install as you would normally install a contributed Drupal module. See
[Installing Drupal modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules)
for further information.


## Configuration

1. Enable the Encrypt module and at least one encryption method module.
2. Create one or more keys at Administration » Configuration » System » Keys
   (`/admin/config/system/keys`). Keys are managed by the Key module. The key
   should be the required type for whatever encryption you want to use.
3. Create an encryption profile at Administration » Configuration » System »
   Encryption profiles (`/admin/config/system/encryption/profiles`). A profile
   links one encryption method plugin and one key.
4. Optionally, test a profile from its "Test" tab, and adjust module settings at
   `/admin/config/system/encryption/profiles/settings`:
    - **Show the validation status of encryption profiles** in the profile list
      (`check_profile_status`).
    - **Allow the use of deprecated plugins** when selecting an encryption
      method (`allow_deprecated_plugins`, disabled by default).

Access is controlled by the **Administer encryption settings** permission
(`administer encrypt`). Grant it only to trusted roles: in addition to changing
settings, it allows decrypting arbitrary text with any encryption profile
through the profile test form.


## Best practices

In order to provide real security, it is highly recommended to follow these
best practices:

### Encryption methods

Encrypt does not include any encryption methods; you must install at least one
module that provides one. The following modules provide encryption methods,
most-used first:

| Module | Type | Description |
| --- | --- | --- |
| [Real AES](https://www.drupal.org/project/real_aes) | Symmetric | Authenticated encryption via the Defuse PHP-encryption library. The recommended default. |
| [Sodium](https://www.drupal.org/project/sodium) | Symmetric | Encryption using the Halite and Sodium (libsodium) libraries. |
| [Encrypt KMS](https://www.drupal.org/project/encrypt_kms) | Symmetric | Encryption using [Amazon KMS](https://aws.amazon.com/kms/). |
| [Encrypt RSA](https://www.drupal.org/project/encrypt_rsa) | Asymmetric | Public-key encryption using the RSA algorithm. |
| [Pubkey Encrypt](https://www.drupal.org/project/pubkey_encrypt) | Asymmetric | Public-key encryption. |

The Encrypt maintainers recommend Real AES for most sites; read the
documentation the Real AES module provides for detailed security guidance and
background.

#### Asymmetric encryption

Asymmetric methods such as the method provided by the Encrypt RSA module can
encrypt but not decrypt data within Drupal. This is useful when you want Drupal
to encrypt data and then decrypt the data only in a separate, more secure
environment.

### Keys

- Use a key value with an appropriately secure size (at least 128 bits) and
  decent quality (i.e. proper randomness).
- Store keys in a secure place. Keep your keys out of the database and out of
  the web root. Store them on a different server, if possible.
- The "Configuration" key provider (defined by the Key module) should only be
  used for testing purposes. Never use this key provider in a production
  environment or any other environment where security is required.


## Using the service

After configuration, the service provides the ability to encrypt and
decrypt using an encryption profile (machine name).

### Get the service

```php
/** @var \Drupal\encrypt\EncryptServiceInterface $encrypt_service */
$encrypt_service = \Drupal::service('encryption');

$profile_id = 'example_machine_name';
/** @var \Drupal\encrypt\EncryptionProfileInterface $encryption_profile */
$encryption_profile = \Drupal::entityTypeManager()
  ->getStorage('encryption_profile')->load($profile_id);
```

### Encrypt a string

```php
$encrypted_string = $encrypt_service->encrypt($string_to_encrypt, $encryption_profile);
```

### Decrypt a string

```php
$decrypted_string = $encrypt_service->decrypt($string_to_decrypt, $encryption_profile);
```

### Note

- If you do not want to use the `use` statement in the examples above, you can
  use the following code to retrieve the encryption profile:

  ```php
  $encryption_profile = \Drupal::service('entity_type.manager')
    ->getStorage('encryption_profile')->load($instance_id);
  ```

- Encrypt supports both symmetric and asymmetric encryption, so be aware
  that asymmetric encryption methods may be able to encrypt BUT NOT decrypt your
  data! [Read more about symmetric and asymmetric cryptography.](https://en.wikipedia.org/wiki/Encryption)


## Writing your own EncryptionMethod plugin

If you want to write your own encryption method plugin, extend the
`EncryptionMethodBase` class and implement the methods defined by
`EncryptionMethodInterface`. See the `TestEncryptionMethod` class in the
`encrypt_test` module bundled in this module's `tests` directory.

Optionally, your encryption method plugin can provide a configuration form that
will automatically be shown upon creation of an `EncryptionProfile` entity. In
this case, you need to implement `EncryptionMethodPluginFormInterface` and
create the required methods. For a simple example, see the
`ConfigTestEncryptionMethod` class in the `encrypt_test` module.

If you are implementing an asymmetric encryption method (that can only
encrypt), your `decrypt()` method implementation should just throw an
`EncryptionMethodCanNotDecryptException` exception. For a simple example, see
the `AsymmetricalEncryptionMethod` class in the `encrypt_test` module.


## Modules that use Encrypt

The following modules use the Encrypt module to protect data, most-used first:

| Module | Description |
| --- | --- |
| [Webform Encrypt](https://www.drupal.org/project/webform_encrypt) | Encrypts data submitted via webforms. |
| [Salesforce Suite](https://www.drupal.org/project/salesforce) | Encrypts access tokens (Salesforce Encrypt submodule). |
| [Field Encryption](https://www.drupal.org/project/field_encrypt) | Encrypts entity field values. |
| [File Encrypt](https://www.drupal.org/project/file_encrypt) | Encrypts files uploaded via the core file field. |


## Client-side encryption

The Encrypt module does not currently support client-side encryption.

The [Client-Side File Crypto](https://www.drupal.org/project/client_side_file_crypto)
and [Client-Side Content Encryption](https://www.drupal.org/project/encrypt_content_client)
were efforts to create client-side encryption modules that were not integrated
with the Encrypt module, and they are no longer supported. Anyone interested in
working on client-side encryption in Drupal should post to
[issue #2629962](https://www.drupal.org/node/2629962).

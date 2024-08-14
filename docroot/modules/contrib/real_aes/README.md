## Overview

Real AES provides an encryption method plugin for the
[Encrypt](https://drupal.org/project/encrypt) module. This plugin offers AES
encryption using CBC mode and HMAC authentication through the
[Defuse PHP-Encryption](https://github.com/defuse/php-encryption) library.

## Requirements

- PHP 5.4 or later, with the OpenSSL extension
- Defuse PHP-Encryption library

## Installation

Install as you would normally install a contributed Drupal module. See
https://www.drupal.org/node/895232 for more information.
## Configuration

Configure your site for encryption as follows:

1. Enable the Real AES, Encrypt, and Key modules.
2. Generate a random 256-bit encryption key.
    - Option 1: Output your key to a file using a method such as the following:

      `dd if=/dev/urandom bs=32 count=1 > /path/to/secret.key`
      (change the path and filename to suit your needs)

    - Option 2: Output your key to standard output, and base64-encode it so it
      can be copied and pasted:

      `dd if=/dev/urandom bs=32 count=1 | base64 -i -`

3. Create a key using the Key module (at /admin/config/system/keys/add).
    - Select "Encryption" for the key type.
    - Select "256" for the key size.
    - Select your preferred key provider and enter provider-specific settings
      based on how you generated your key in Step 2.
    - The Configuration provider is for development use only. It is too insecure
      for production use.
    - The File provider is more secure, especially if the file is stored outside
      of the web root directory.
    - An even more secure option is to use an off-site key management service,
      such as [Lockr](https://www.drupal.org/project/lockr) or
      [Townsend Security's Alliance Key Manager](https://www.drupal.org/project/townsec_key)
    - Click "Save".
4. Create an encryption profile using the Encrypt module (at
   /admin/config/system/encryption/profiles/add).
    - For the encryption method, select "Authenticated AES (Real AES)".
    - Select the name of the key definition you created in step 2.
    - Click "Save".
5. Test your encryption by selecting "Test" under "Operations" for the
   encryption profile on the profiles listing page
   (/admin/config/system/encryption/profiles).

## About Authenticated Encryption

Authenticated encryption ensures data integrity of the ciphertext. When
decrypting, integrity is checked first.

Further decryption operations will only be executed when the integrity
check passes. This prevents certain ciphertext attacks on AES in CBC mode.

## Credits

This module was created by [LimoenGroen](https://limoengroen.nl/) after carefully
considering the various encryption modules and libraries available.

The port to Drupal 8 was performed by [Sven Decabooter](/u/svendecabooter), supported by
[Acquia](https://www.acquia.com/).

The library doing the actual work,
[Defuse PHP-Encryption](https://github.com/defuse/php-encryption), is maintained by
Taylor Hornby and Scott Arciszewski

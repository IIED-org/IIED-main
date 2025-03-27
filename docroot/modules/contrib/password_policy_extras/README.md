# Password Policy Extras

Various additions and enhancements to the Password Policy module.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/webform_address_autocomplete).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/webform_address_autocomplete).

## Features

- Auto-refresh of the password validation status table while typing.
- Option to only display the failed rules messages instead of the whole 3-column
table.
- Various accessibility improvements and fixes.
- Integration with [user_registrationpassword](https://www.drupal.org/project/user_registrationpassword)
module _(via submodule)_.
- Integration with [Password Reset Landing Page (prlp)](https://www.drupal.org/project/prlp)
module _(via submodule)_.

This project aims to be a sandbox for the Password Policy module.

We will try to keep in sync with [password_policy](https://www.drupal.org/project/password_policy)
versioning, at least for the major version number.

A dedicated submodule makes [password_policy](https://www.drupal.org/project/password_policy)
compatible with the [user_registrationpassword](https://www.drupal.org/project/user_registrationpassword)
module.

Another dedicated submodule makes [password_policy](https://www.drupal.org/project/password_policy)
compatible with the [Password Reset Landing Page (prlp)](https://www.drupal.org/project/prlp)
module _(version 8.x-1.11+ required)_.


## Requirements

This module requires the [Password Policy](https://www.drupal.org/project/password_policy)
module.

Depending on your use of the optional submodules, you might need to install the
[user_registrationpassword](https://www.drupal.org/project/user_registrationpassword)
and/or the [prlp](https://www.drupal.org/project/prlp) module.


## Installation

Install as you would normally install a contributed Drupal module.
For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Enable the module at Administration > Extend.
2. Go to `/admin/config/security/password-policy/extras/settings` to configure
the module settings.
3. Activate some submodules if needed.


## Maintainers

- Frank Mably - [mably](https://www.drupal.org/u/mably)

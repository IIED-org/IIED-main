# ISBN Field

The ISBN Module provides a way to keep track of ISBN to node relationships.
The module also handles the issue of 10 and 13 digit ISBNs.

The main purpose of creating this module was to work with library catalogs and
the issue of record duplication.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/isbn).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/isbn).


## Table of contents

- Requirements
- Installation
- Author
- Maintainers


## Requirements

This module requires the library ["nicebooks/isbn"](https://github.com/nicebooks-com/isbn)


## Installation
Since the module requires an external library, Composer or Ludwig must be used.

### Composer
If your site is [managed via Composer](https://www.drupal.org/node/2718229), use
Composer to download the module, which will also download the required library:

   ```sh
   composer require "drupal/isbn"
   ```

Use ```composer update drupal/isbn --with-dependencies``` to update to a new
release.

### Ludwig
Composer is recommended whenever possible. However, if you are not familiar with
Composer yet (or you want to avoid it for other reasons) you can install and use
the [Ludwig](https://www.drupal.org/project/ludwig) module to manage the ISBN
module library dependencies.

Read more at Ludwig Installation and Usage guide:
https://www.drupal.org/docs/contributed-modules/ludwig/installation-and-usage


## Author

Andy Austin
austinone@gmail.com


## Maintainers

- Jon Pugh - [Jon Pugh](https://www.drupal.org/u/jon-pugh)
- Matt A - [zbricoleur](https://www.drupal.org/u/zbricoleur)
- Rafael Silva - [rfsbsb](https://www.drupal.org/u/rfsbsb)
- Youri van Koppen - [MegaChriz](https://www.drupal.org/u/megachriz)

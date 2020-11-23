# Language Switcher Extended module for Drupal 8.x.
----------------------------------------------------------------

CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers
* CREDITS


INTRODUCTION
------------
  
The Language Switcher Extended module provides additional processors for
the language switcher block links.


### Features ###
* Link all language switcher items to their corresponding language frontpage.
* Hide language switcher items, if there is no translation for the current
entity
* Link language switcher items to the frontpage, if there is no translation for
the current entity


REQUIREMENTS
------------

This module requires the following modules:

* Block ([Drupal core](http://drupal.org/project/drupal))
* Language ([Drupal core](http://drupal.org/project/drupal))


INSTALLATION
------------

Install the module as usual, more info can be found on:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules


CONFIGURATION
-------------

* Go to the Language Switcher Extended configuration
(`/admin/config/regional/language/language-switcher-extended`)
* Select at least your preferred Language Switcher Mode
* Optional: Set a handler for untranslated content entities
* Optional: Choose if the Language Switcher items should be hidden, when only
a single item was left.


MAINTAINERS
-----------

Current maintainers:
- Stephan Zeidler (szeidler) - https://www.drupal.org/user/767652


CREDITS
-----------

The module is based on the work of the following people in
[this support forum thread]
(https://www.drupal.org/forum/support/translations/2016-08-28/language-switcher-doesnt-strikedisable-links-in-untranslated).

- Karol Haltenberger - https://www.drupal.org/u/karol-haltenberger
- Leon Kessler - https://www.drupal.org/u/leon-kessler
- Efstathios Papadopoulos - https://www.drupal.org/u/efpapado

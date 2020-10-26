INTRODUCTION
------------

This module provides a way to bulk import redirects if you are using the
Redirect module (https://www.drupal.org/project/redirect).

The import is performed from an uploaded CSV file with the following structure:
'old url', 'new_url', 'redirect_code' = 301, 'language' = ''.

REQUIREMENTS
------------

This module requires the Redirect module.
  * Redirect - https://www.drupal.org/project/redirect

INSTALLATION
------------

Install as you would normally install a contributed Drupal module. Visit:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.

CONFIGURATION
-------------

You can configure Redirect Imports at Administration >> Configuration >>
Search and metadata >> Redirect >> Import

The first section is for the import itself and is self explanatory. The second
section covers the imported data and how the redirects are to be handled.

MAINTAINERS
------------

Current maintainers:

  * Timo Welde (tjwelde) - https://www.drupal.org/u/tjwelde

  * Pablo López (plopesc) - https://www.drupal.org/u/plopesc

  * gabrielu - https://www.drupal.org/u/gabrielu
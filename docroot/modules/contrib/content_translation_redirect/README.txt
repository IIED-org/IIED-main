INTRODUCTION
------------

This module will be useful if you need to redirect users from pages of
non-existent translations of the content entity to a page with the original
language.

It is important that the user will be redirected only if the entity translation
URL is different from the entity URL in the original language. Also, do not
forget that the entity must be translatable.


FEATURES
--------

 * An administration interface to manage redirect settings for each
   content entity type bundle. Each bundle settings includes a redirect
   status code and a message that can be displayed to the user after
   redirection.


REQUIREMENTS
------------

This module requires:

 * Content Translation (Allows users to translate content entities).
 * Drupal 8.5 or greater.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Configure user permissions in Administration » People » Permissions:

    - Administer content translation redirects

      Users in roles with the "Administer content translation redirects"
      permission can perform administration tasks for
      Content Translation Redirect module.

 * Set the default settings in Administration » Configuration »
   Regional and language » Content translation redirects.

 * Configure redirects for content entity bundles in Administration »
   Configuration » Regional and language » Content translation redirects »
   Entity settings.


SIMILAR MODULES
---------------

Modules that provide some others useful functionalities, similar
to Content Translation Redirect module:

 * Content Language Access
   https://www.drupal.org/project/content_language_access
   This module helps when you have a content that needs to have
   access restriction by Drupal language.


CREDITS / CONTACT
-----------------

Developed and maintained by Andrey Tymchuk (WalkingDexter)
https://www.drupal.org/u/walkingdexter

Ongoing development is sponsored by Drupal Coder.
https://www.drupal.org/drupal-coder-initlab-llc

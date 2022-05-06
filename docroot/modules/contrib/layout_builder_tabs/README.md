CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module defines a section type for Drupal's core Layout Builder. Each block
added to this section will be rendered as its own tab. The tabs implementation
has been tested for accessibility, but we very much welcome any additional
feedback on how it could be improved.


INSTALLATION
------------

 * Install the Smart Date module as you would normally install a
   contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


REQUIREMENTS
------------

This module requires the Layout Builder module in Drupal core.


CONFIGURATION
-------------

 * There isn't really any specific configuration per se, but to make use of this
   module you need to edit either a content type or a specific entity's layout
   using Layout Builder, and then add a new section, then add the Tabs section
   type. Each block add to the section will display as its own tab.


MAINTAINERS
-----------

 * Current Maintainer: Martin Anderson-Clutz (mandclu) - https://www.drupal.org/u/mandclu

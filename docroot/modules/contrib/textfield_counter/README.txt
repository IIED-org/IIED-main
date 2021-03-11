CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Text Field Counter module provides new widgets for the form display of each
of the field types that ship with core, both text fields and text areas. The
widgets this module provides extend the default core widgets, adding a text
counter that displays the remaining number of characters allowed in the field.
This counter updates as the user types.

The field types that this module works on are:

 * Text (formatted)
 * Text (formatted, long)
 * Text (formatted, long, with summary)
 * Text (plain)
 * Text (plain, long)

 * For a full description of the module visit:
   https://www.drupal.org/project/textfield_counter

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/textfield_counter


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Text Field Counter module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Content Type > [Content type to
       edit] > Add field and add a new field from those listed above.
    3. Navigate to the 'Manage form display' tab. In the 'widget' column, select
       the widget that ends with 'counter'.
    4. Now when entering content into the new field, the counter will display
       and count down the remaining characters available in the field.


MAINTAINERS
-----------

 * Jay Friendly (Jaypan) - https://www.drupal.org/u/jaypan

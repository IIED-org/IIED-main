CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Views Layout module allows the user to configure a "grid" display of a view
that is constructed by reference to a specified layout.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/views_layout

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/views_layout


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

To use:
    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Navigate to Administration > Structure > Views > Add view and create a
       new view.
    3. In the Format field set, select the 'Views Layout Grid' style.
    4. On the style settings, provide the machine name of the layout to use.
    5. List the names of the regions you want to populate in the layout. You can
       omit regions if you don't want to populate all of them. They will be 
       left empty in the display.
    6. Save the settings and view the result.

Some ways to use this module for flexible results:

 * Create a paged view that has the same number of results per page as the
   layout has regions, to get one instance of the layout on each page.
 * Create a fancy layout with two results on the first row, three on the second,
   one on the third, etc. to display the results in a complex pattern.
 * Omit some regions for different effects.
 * Use a callback in skipped regions to insert other content in those locations.


MAINTAINERS
-----------

Current maintainers:
 * Karen Stevenson (KarenS) - https://www.drupal.org/u/karens

This project is sponsored by:
 * Lullabot - https://www.drupal.org/lullabot

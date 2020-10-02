CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The PDFPreview module shows a preview of PDF files uploaded through File Field.

This module provides a formatter which uses ImageMagick to extract the first
page of a PDF file to a JPG or PNG image which is used to link to the file.

 * For a full description of the module visit:
   https://www.drupal.org/project/pdfpreview

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/pdfpreview


REQUIREMENTS
------------

This module requires the following outside of Drupal core.

ImageMagick needs to be installed on your server and the convert binary needs to
be accessible and executable from PHP. For more information visit the
ImageMagick project page.

 * ImageMagick - https://www.drupal.org/project/imagemagick
 * http://www.imagemagick.org/script/index.php

Please note:  Imagemagick doesn't need to be the default toolkit, but the module
does need to be installed.


INSTALLATION
------------

 * Install the PDF Preview module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and its
       dependencies.
    2. Navigate to Administration > Structure > Content Type > [Content Type to
       edit] > Manage Display. Select 'PDFPreview' as the formatter of a file
       field.
    3. Use the configuration gear to display formatter settings to configure.
    4. Navigate to Administration > Configuration > PDF Preview to set up the
       preview path, preview size, image quality, and preview image type.
    5. Save configuration.


MAINTAINERS
-----------

 * Drupal 8 - Michael Strelan (mstrelan) - https://www.drupal.org/u/mstrelan
 * Drupal 7 - Pierre Buyle (pbuyle) - https://www.drupal.org/u/pbuyle
 * Drupal 7 - Juanjo García (juanjo_dv) - https://www.drupal.org/u/juanjo_dv

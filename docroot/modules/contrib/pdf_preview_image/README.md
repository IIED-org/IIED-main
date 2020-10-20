# PDF Preview Image

PDF Preview Image module provides an image preview from a pdf file and save it on a field image type.

# Requirements

  * File field, Image field
  * Package 'Convert a pdf to an image' which can pe installed via composer:
    $ composer require spatie/pdf-to-image.
  * Imagick and Ghostscript installed on server.

# Install

* Install package 'Convert a pdf to an image' via $ composer require spatie/pdf-to-image.
* Enable the module admin/modules.

# Using the module

* Go on a content type, create a file type field and set up on "Allowed file extensions" the pdf extension.
* On the bottom of file field settings will appear "Pdf preview autogeneration"; Check the box.
* There is an option to set up the image field where should be stored image from pdf.
* If there isn't an image field created in content type, go ahead create one, and then set up on "Pdf Preview Autogeneration".

# Contributors

* Ionut Stan (https://drupal.org/u/ionut.stan)
* Alexandru Tulbure (https://drupal.org/u/alex_tulbure)

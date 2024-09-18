INTRODUCTION
------------

The Media PDF Thumbnail module generates a thumbnail image from the first page page of a pdf file and sets that image as thumbnail for Media entity (File).

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/media_pdf_thumbnail

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/media_pdf_thumbnail

REQUIREMENTS
------------

This module requires spatie/pdf-to-image library: composer require spatie/pdf-to-image
This module requires the following PHP extension: imagick.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------

 * Settings can be found in Administration » Configuration » Multimedia » Media PDF Thumbnail
   /admin/media-pdf-thumbnail/settings/list

USAGE
-----

 * In the media entity bundle's display mode, choose the Media PDF Thumbnail formatter and set options as desired

 *(active tab)
  Edit
  Version control
  View history
  Maintainers
  Automated testing
  Unstar  32people starred this project All issues
  Status messageModule project Media PDF Thumbnail has been updated.
  By sgostanyan on 3 March 2019, updated 23 October 2021



  The module provides an image field formatter for the thumbnail field of Media entities. The default thumbnail can be replaced by an image generated from a pdf file referenced in any file field of any Media entity.

  Prerequisite :
  imagick PHP extension.
  spatie/pdf-to-image library: composer require spatie/pdf-to-image.
  If you get pages overlapped in one image (issue from the third library spatie/pdf-to-image, please install patch from : https://www.drupal.org/project/media_pdf_thumbnail/issues/3221786

  Instructions :
  Install spatie/pdf-to-image: composer require spatie/pdf-to-image
  Install as you would normally install a contributed Drupal module and enabled it.

  Usage :
  Choose the "Media PDF Thumbnail Image" field formatter in the Media entity view mode for thumbnail field and set desired options.
  When rendering the thumbnail field, the image from the file field (the one choosen in the field formatter's options) will be created and stored in a storage entity.
  The storage system is similar to the Redirect module.
  When the thumbnail field is rendered, the image file will be fetched from the storage entity. The original image of the thumbnail field is never modified, it is just replaced on display.
  Don't forget to clear caches after any changes for rebuilding the field render.

  * Token :

    Getting values :
    // @value can be "image_uri" or "image_id"
    [media_pdf_thumbnail:@pdf_field_name:@pdf_page:@format:@value]

    Getting render :
    [media_pdf_thumbnail:@pdf_field_name:@pdf_page:@format:render:@image_style]

    Getting render and link image to pdf file :
    [media_pdf_thumbnail:@pdf_field_name:@pdf_page:@format:render:@image_style:link_pdf]

    Examples :

    // Returns image file uri
    $fileUri = Drupal::token()->replace('[media_pdf_thumbnail:field_media_file:1:jpg:image_uri]', ['media' => $media]);

    // Returns image file id
    $fileId = Drupal::token()->replace('[media_pdf_thumbnail:field_media_file:1:jpg:image_id]', ['media' => $media]);

    // Returns image html render
    $render = $token->replace('[media_pdf_thumbnail:field_media_file:1:jpg:render:medium]', ['media' => $media]);

    // Returns image linked to pdf html render
    $renderLink = $token->replace('[media_pdf_thumbnail:field_media_file:1:jpg:render:medium:link_pdf]', ['media' => $media]);

  * Hook :

  Alter pdf image render, example :

  function hook_media_pdf_thumbnail_image_render_alter(&$element, $infos) {
    $value = $element[0]["#item"]->getValue();
    $value['alt'] = 'Thumbnail of the document ' . $infos['mediaEntity']->name->value;
    $element[0]["#item"]->setValue($value);
  }

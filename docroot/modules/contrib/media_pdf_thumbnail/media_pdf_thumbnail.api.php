<?php

/**
 * @file
 * Documentation for Media PDF Thumbnail API.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the PDF thumbnail image.
 *
 * @param array $element
 *   Image field element.
 * @param array $infos
 *   Media PDF thumbnail image info.
 *
 * @return void
 *   Return nothing.
 */
function hook_media_pdf_thumbnail_image_render_alter(array &$element, array $infos): void {
  $value = $element[0]["#item"]->getValue();
  $value['alt'] = 'Thumbnail of the document ' . $infos['mediaEntity']->name->value;
  $element[0]["#item"]->setValue($value);
}

/**
 * @} End of "addtogroup hooks".
 */

<?php

/**
 * @file
 * Contains media_pdf_thumbnail.module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function media_pdf_thumbnail_token_info() {

  $types['media_pdf_thumbnail'] = [
    'name' => t('Media PDF Thumbnail Tokens'),
    'description' => t('Media PDF Thumbnail tokens.'),
  ];

  foreach (_media_pdf_thumbnail_getFieldsList() as $list) {

    if (!empty($list['fields'])) {

      foreach ($list['fields'] as $field) {
        $tokenName = sprintf('%s:page:image_id', $field);
        $mediaPdfThumbnail[$tokenName] = [
          'name' => t('Media PDF Thumbnail Tokens'),
          'description' => t('Return pdf image file id for @field field.', ['@field' => $field]),
        ];
        $tokenName = sprintf('%s:page:image_uri', $field);
        $mediaPdfThumbnail[$tokenName] = [
          'name' => t('Media PDF Thumbnail Tokens'),
          'description' => t('Return pdf image file uri for @field field.', ['@field' => $field]),
        ];

        foreach (image_style_options() as $id => $imageStyle) {
          $id = empty($id) ? 'original' : $id;
          $tokenName = sprintf('%s:page:render:%s', $field, $id);
          $mediaPdfThumbnail[$tokenName] = [
            'name' => t('Media PDF Thumbnail Tokens'),
            'description' => t('Return rendered pdf image file for @field field.', ['@field' => $field]),
          ];
          $tokenName = sprintf('%s:page:render:%s:link_pdf', $field, $id);
          $mediaPdfThumbnail[$tokenName] = [
            'name' => t('Media PDF Thumbnail Tokens'),
            'description' => t('Return rendered linked pdf image file for @field field.', ['@field' => $field]),
          ];
        }
      }
    }
  }

  if (empty($mediaPdfThumbnail)) {
    return [];
  }

  return [
    'types' => $types,
    'tokens' => ['media_pdf_thumbnail' => $mediaPdfThumbnail],
  ];

}

/**
 * Implements hook_tokens().
 */
function media_pdf_thumbnail_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable) {

  $replacements = [];

  if ($type == 'media_pdf_thumbnail') {

    foreach ($tokens as $name => $original) {
      $nameArray = explode(':', $name);
      $pdfField = $nameArray[0];
      $page = $nameArray[1];
      $format = $nameArray[2];
      $value = $nameArray[3];
      $imageStyle = !empty($nameArray[4]) ? $nameArray[4] : NULL;
      $linkPdf = !empty($nameArray[5]) ? $nameArray[5] : NULL;
      $media = $data['media'];
      $mediaPdfThumbnailImageManager = Drupal::service('media_pdf_thumbnail.image.manager');
      $infos = $mediaPdfThumbnailImageManager->createThumbnail($media, $pdfField, $format, $page);

      if ($value == 'render' && $imageStyle) {
        $text = [
          '#theme' => 'image_style',
          '#style_name' => $imageStyle,
          '#uri' => $infos['image_uri'],
        ];
        if ($linkPdf) {
          $stream = Drupal::service('stream_wrapper_manager')->getViaUri($infos['pdf_uri'])->getExternalUrl();
          $text['#prefix'] = '<a href="' . Url::fromUri($stream)->toString() . '">';
          $text['#suffix'] = '</a>';
        }
        $text = Drupal::service('renderer')->render($text);
      }
      else {
        $text = $infos[$value];
      }
      $replacements[$original] = $text;
    }
  }

  return $replacements;
}

<?php

/**
 * @file
 * Module to define custom filters.
 */

namespace Drupal\iied_filters\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * @Filter(
 *   id = "iied_filter_video",
 *   title = @Translation("IIED Video filter"),
 *   description = @Translation("Filter for legacy inline video tags from the https://www.drupal.org/project/video_embed_field module"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */

class FilterVideo extends FilterBase {

  public function process($text, $langcode) {

    preg_match_all('/ \[VIDEO:: ( [^\[\]]+ )* \] /x', $text, $matches);

    $tag_match = (array) array_unique($matches[1]);

    foreach ($tag_match as $tag) {
      $parts = explode('::', $tag);

      // Get video style.
      if (isset($parts[1])) {
        $style = $parts[1];
      } else {
        $style = 'normal';
      }

      //$embed_code = theme('video_embed_field_embed_code', array('url' => $parts[0], 'style' => $style));

      $embed_code = "testing";


      $text = str_replace('[VIDEO::' . $tag . ']', $embed_code, $text);
    }



    return new FilterProcessResult($text);
  }
}

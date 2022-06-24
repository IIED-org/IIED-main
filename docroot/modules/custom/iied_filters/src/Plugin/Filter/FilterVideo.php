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

      $render = [
        '#theme' => 'iied_filters_video_embed_code',
        '#url' => $parts[0],
        '#style' => $style,
      ];

      $variables['url'] = $parts[0];

      // Get the handler.
      $handler = iied_filters_get_handler($variables['url']);
      $variables['handler'] = $handler['name'];

      $style = [];
      if (isset($style->data[$variables['handler']])) {
        $variables['style_settings'] = $style->data[$variables['handler']];
      }
      // Safety value for when we add new handlers and there are styles stored.
      else {
        $variables['style_settings'] = $handler['defaults'];
      }

      // Prepare the URL.
      if (!stristr($variables['url'], 'http://') && !stristr($variables['url'], 'https://')) {
        $variables['url'] = 'http://' . $variables['url'];
      }

      // Prepare embed code.
      if ($handler && isset($handler['function']) && function_exists($handler['function'])) {
        $embed_code = call_user_func($handler['function'], $variables['url'], $variables['style_settings']);
        $variables['embed_code'] = ($embed_code);
      } else {
        $variables['embed_code'] = l($variables['url'], $variables['url']);
      }

      // Define render array to generate the markup
      $render = [
        '#theme' => 'iied_filters_video_embed_code',
        '#url' => $parts[0],
        '#style' => $style,
        '#embed_code' => $embed_code['#markup'],
      ];

      // Render it, as we need to deliver the actual markup.
      $output = \Drupal::service('renderer')->render($render);

      // Do the replacement
      $text = str_replace('[VIDEO::' . $tag . ']', $output, $text);
    }

    return new FilterProcessResult($text);

  }

}

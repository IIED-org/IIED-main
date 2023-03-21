<?php

namespace Drupal\linkchecker\Plugin\LinkExtractor;

use Drupal\linkchecker\Plugin\LinkExtractorBase;

/**
 * Class LinkLinkExtractor.
 *
 * @LinkExtractor(
 *   id = "link_link_extractor",
 *   label = @Translation("Link extractor"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkLinkExtractor extends LinkExtractorBase {

  /**
   * {@inheritdoc}
   */
  protected function extractUrlFromField(array $value) {
    // Return the uri index from the $value array.
    return empty($value['uri']) ? [] : [$value['uri']];
  }

}

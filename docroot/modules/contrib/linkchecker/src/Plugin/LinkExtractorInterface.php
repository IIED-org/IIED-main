<?php

namespace Drupal\linkchecker\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Link extractor plugin plugins.
 */
interface LinkExtractorInterface extends PluginInspectionInterface {

  /**
   * Extracts links from field list.
   *
   * @param array $value
   *   The field value.
   *
   * @return array
   *   List of URLs.
   */
  public function extract(array $value);

}

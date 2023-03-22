<?php

namespace Drupal\linkchecker\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Link extractor plugin item annotation object.
 *
 * @see \Drupal\linkchecker\Plugin\LinkExtractorManager
 * @see plugin_api
 *
 * @Annotation
 */
class LinkExtractor extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An array of field types the extractor supports.
   *
   * @var array
   */
  public $field_types = [];

}

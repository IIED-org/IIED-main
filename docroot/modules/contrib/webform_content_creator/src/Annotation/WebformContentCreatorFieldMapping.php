<?php

namespace Drupal\webform_content_creator\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a data type of structured data.
 *
 * Plugin Namespace: Plugin\WebformContentCreator\WebformContentCreatorFieldMapping.
 *
 * @see \Drupal\structured_data\StructuredDataManager
 * @see plugin_api
 *
 * @Annotation
 */
class WebformContentCreatorFieldMapping extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the webform content creator field mapping.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

  /**
   * An array of field types the content creator field mapping supports.
   *
   * @var array
   */
  public $field_types = [];

}

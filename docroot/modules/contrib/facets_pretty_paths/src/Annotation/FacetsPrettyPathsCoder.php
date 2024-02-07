<?php

namespace Drupal\facets_pretty_paths\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Facets pretty paths Coder annotation.
 *
 * Coder plugins are used to transform a value for url usage. For example, the
 * TaxonomyTermCoder will use the alias stored in the term for usages in urls.
 *
 * @see \Drupal\facets_pretty_paths\Coder\CoderPluginManager
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class FacetsPrettyPathsCoder extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The description.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}

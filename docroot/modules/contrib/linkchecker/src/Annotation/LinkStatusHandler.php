<?php

namespace Drupal\linkchecker\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Link status handler plugin item annotation object.
 *
 * @see \Drupal\linkchecker\Plugin\LinkStatusHandlerManager
 * @see plugin_api
 *
 * @Annotation
 */
class LinkStatusHandler extends Plugin {


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
   * An array of status codes which the handler supports.
   *
   * @var array
   */
  public $status_codes = [];

  /**
   * Flag that indicates if plugin is enabled.
   *
   * This flag will help to disable some plugins with hook info alter.
   *
   * @var bool
   */
  public $enabled = TRUE;

}

<?php

namespace Drupal\views_templates\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a ViewsBuilder annotation object.
 *
 * @todo For some reason this not getting picked up by the manager.
 *
 * @Annotation
 */
class ViewsBuilder extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * Description for list page.
   *
   * @var string
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The administrative label of the View Builder.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $admin_label = '';

  /**
   * Class used to retrieve derivative definitions of the Views Builder.
   *
   * @var string
   */
  public $derivative = '';

  /**
   * Base table of View.
   *
   * @var string
   */
  public $base_table;

  /**
   * The module that provides the template.
   *
   * @var string
   */
  public $module;

  /**
   * Keys and values to replaced in the Views Template.
   *
   * @var array
   */
  public $replace_values;

  /**
   * Id of Views template.
   *
   * @var string
   */
  public $view_template_id;

}

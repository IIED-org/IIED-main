<?php

namespace Drupal\tfa\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TFA Login annotation object.
 *
 * @Annotation
 */
class TfaLogin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Tfa login.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description shown to users.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * Plugin ID for user-specific setup plugin for this login plugin.
   *
   * @var string
   */
  public $setupPluginId;

}

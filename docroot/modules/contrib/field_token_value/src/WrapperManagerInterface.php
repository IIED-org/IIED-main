<?php

/**
 * @file
 * Contains \Drupal\field_token_value\WrapperManager.
 */

namespace Drupal\field_token_value;

/**
 * Gathers and provides the tags that can be used to wrap field content within
 * Field Token Value fields.
 */
interface WrapperManagerInterface {

  /**
   * Get the tags than can wrap fields.
   *
   * @return array
   *   An array of HTML tags which can wrap the field value.
   */
  public function getWrapperOptions();

}

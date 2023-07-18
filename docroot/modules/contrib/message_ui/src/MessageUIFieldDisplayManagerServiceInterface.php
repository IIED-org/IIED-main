<?php

namespace Drupal\message_ui;

/**
 * Interface MessageUIFieldDisplayManagerServiceInterface.
 *
 * @package Drupal\message_ui
 */
interface MessageUIFieldDisplayManagerServiceInterface {

  /**
   * Setting the fields to display.
   *
   * @param string $template
   *   The message template.
   */
  public function setFieldsDisplay($template);

}

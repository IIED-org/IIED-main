<?php

namespace Drupal\tfa\Plugin;

/**
 * Interface TfaSendInterface.
 *
 * Send plugins interact with the Tfa begin() process to communicate a code
 * during the start of the TFA process.
 *
 * Implementations of a send plugin should also be a validation plugin.
 */
interface TfaSendInterface {

  /**
   * TFA process begin.
   */
  public function begin();

}

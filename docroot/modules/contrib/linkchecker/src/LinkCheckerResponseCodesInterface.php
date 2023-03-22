<?php

namespace Drupal\linkchecker;

/**
 * Class LinkcheckerResponseCodes.
 *
 * @package Drupal\linkchecker
 */
interface LinkCheckerResponseCodesInterface {

  /**
   * Check if the given HTTP response code is valid.
   *
   * @param int $code
   *   An numeric response code.
   *
   * @return bool
   *   TRUE if the status code is valid, otherwise FALSE.
   */
  public function isValid(int $code);

}

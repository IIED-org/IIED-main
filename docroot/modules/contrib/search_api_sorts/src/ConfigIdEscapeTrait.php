<?php

namespace Drupal\search_api_sorts;

/**
 * Wrapper methods for \Drupal\search_api_sorts\SearchApiSortsField.
 *
 * Using this trait will add escapeConfigId() and unescapeConfigId() methods to
 * the class. These must be used for every config id when working loading or
 * saving configuration entities. This allows derivative ids, which containing
 * a colon, to be saved as config ids.
 */
trait ConfigIdEscapeTrait {

  /**
   * Escape a config id which can be used to save as a config entity.
   */
  protected function getEscapedConfigId($original_config_id) {
    return str_replace(':', '---', $original_config_id);
  }

  /**
   * Get original config id after loading a config entity using an escaped id.
   */
  protected function getOriginalConfigId($escaped_config_id) {
    return str_replace('---', ':', $escaped_config_id);
  }

}

<?php

namespace Drupal\tablefield\Utility;

/**
 * Provides helpers to use timers throughout a request.
 *
 * @ingroup utility
 */
class Tablefield {

  /**
   * Helper function to turn form elements into a structured array.
   *
   * @param array $tablefield
   *   The table as it appears in FAPI.
   */
  public static function rationalizeTable(array $tablefield) {
    $tabledata = [];

    // Rationalize the table data.
    if (!empty($tablefield)) {
      // Remove exterraneous form data.
      $count_cols = $tablefield['rebuild']['count_cols'];
      $count_rows = $tablefield['rebuild']['count_rows'];
      unset($tablefield['rebuild']);
      unset($tablefield['import']);

      foreach ($tablefield as $key => $value) {
        preg_match('/cell_(.*)_(.*)/', $key, $cell);
        // $cell[1] is row count $cell[2] is col count.
        if ((int) $cell[1] < $count_rows && (int) $cell[2] < $count_cols) {
          $tabledata[$cell[1]][$cell[2]] = $value;
        }
      }
    }

    return $tabledata;
  }

}

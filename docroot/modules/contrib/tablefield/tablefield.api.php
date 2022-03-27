<?php

/**
 * @file
 * Hooks and API provided by the Tablefield module.
 */

/**
 * Alters Tablefield encodings.
 *
 * @param array $encodings
 *   The list of encodings to support for CSV import.
 *
 * @ingroup tablefield
 */
function hook_tablefield_encodings_alter(array &$encodings) {
  // Add UTF-16 encoding support.
  $encodings[] = 'UTF-16';
}

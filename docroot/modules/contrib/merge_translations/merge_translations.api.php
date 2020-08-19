<?php

/**
 * @file
 * merge_translations.api.php
 */

/**
 * Add ability to alter $node values before add to translation.
 *
 * @param array $node_array
 *   Node_array.
 */
function hook_merge_translations_prepare_alter(array &$node_array) {
  $node_array['title'][0]['value'] = 'Translated title';
}

<?php

/**
 * @file
 * Post update functions for DS.
 */

/**
 * Rebuild the theme registry to pick up 'initial preprocess'.
 */
function ds_post_update_theme_registry_clear() {
  // Empty update to trigger container rebuild.
}

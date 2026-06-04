<?php

/**
 * @file
 * Post update functions for Components.
 *
 * All empty post-update hooks ensure the cache is cleared.
 * @see https://www.drupal.org/node/2960601
 */

/**
 * Clear caches to allow components.twig.extension service to debug templates.
 */
function components_post_update_twig_extension_debug(): void {
}

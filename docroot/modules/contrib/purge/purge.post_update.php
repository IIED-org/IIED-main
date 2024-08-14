<?php

/**
 * @file
 * Post update functions for Purge.
 */

declare(strict_types=1);

/**
 * Force a cache refresh because new services were added.
 */
function purge_post_update_refresh_container(&$sandbox) {
  // Empty post-update hook.
}

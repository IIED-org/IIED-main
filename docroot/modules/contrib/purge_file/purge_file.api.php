<?php

/**
 * @file
 * Hooks specific to the Purge File module.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\file\FileInterface;

/**
 * Alter the base urls used to purge the file.
 *
 * @param array $base_urls
 *   The base urls to purge for the file.
 * @param \Drupal\file\FileInterface $file
 *   The file entity.
 */
function hook_purge_file_base_urls_alter(array &$base_urls, FileInterface $file) {
  $base_urls[] = 'https://www.example.com';
}

/**
 * @} End of "addtogroup hooks".
 */

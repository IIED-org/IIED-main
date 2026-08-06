<?php

namespace Drupal\search_api_solr\Utility;

use ZipStream\ZipStream;

/**
<<<<<<< HEAD
 * Creates ZipStream instances and headers for config downloads.
=======
 * Handles ZipStream v2 vs v3 issues.
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
 */
class ZipStreamFactory {

  /**
   * Returns a ZipStream instance.
   *
<<<<<<< HEAD
   * @param string $name
   *   The output archive name.
   * @param resource|null $resource
   *   Output stream resource.
=======
   * @param \ZipStream\Option\Archive|ressource|NUll $archive_options_or_ressource
   *   Archive options.
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
   *
   * @return \ZipStream\ZipStream
   *   The ZipStream that contains all configuration files.
   */
<<<<<<< HEAD
  public static function createInstance($name, $resource = NULL): ZipStream {
    if ($resource) {
      return new ZipStream(
        outputStream: $resource,
        enableZip64: FALSE,
        defaultEnableZeroHeader: FALSE,
        httpHeaderCallback: static::sendHttpHeader(...),
      );
    }

    return new ZipStream(
      enableZip64: FALSE,
      defaultEnableZeroHeader: FALSE,
      outputName: $name,
      httpHeaderCallback: static::sendHttpHeader(...),
    );
  }

  /**
   * Sends an HTTP header for ZipStream downloads.
   *
   * Ensures config zip downloads are not stored in shared caches.
   *
   * @param string $header
   *   The header line emitted by ZipStream.
   */
  public static function sendHttpHeader(string $header): void {
    if (0 === stripos($header, 'Cache-Control:')) {
      $header = 'Cache-Control: private';
    }

    header($header, TRUE);
=======
  public static function createInstance($name, $archive_options_or_ressource = NULL): ZipStream {
    if (class_exists('\ZipStream\Option\Archive')) {
      // Version 2.x.
      return new ZipStream($name, $archive_options_or_ressource);
    }

    // In case of PHP 7.4 the ZipStream 3.x code leads to parse errors.
    // So it has to moved to another file.
    return ZipStream3Factory::createInstance($name, $archive_options_or_ressource);
>>>>>>> parent of 3b9f439507 (remove gitignored directories)
  }

}

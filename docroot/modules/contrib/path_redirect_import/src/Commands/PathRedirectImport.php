<?php

namespace Drupal\path_redirect_import\Commands;

use Drush\Commands\DrushCommands;
use Drupal\path_redirect_import\ImporterService;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
class PathRedirectImport extends DrushCommands {

  /**
   * Import redirects.
   *
   * @command path-redirect-import
   * @option $no_headers
   * @option $override
   * @option $status_code
   * @option $delimiter
   * @option $language
   * @option $allow_nonexistent
   * @aliases primport
   */
  public function pathRedirectImportCsv(
    $file,
    $options = [
      'no_headers' => FALSE,
      'override' => FALSE,
      'status_code' => 301,
      'delimiter' => ',',
      'language' => '',
      'allow_nonexistent' => FALSE,
    ]) {
    if (!file_exists($file)) {
      $this->logger()->error("File $file doesn't exist \n");
      exit;
    }

    ImporterService::import($file, $options);
  }

}

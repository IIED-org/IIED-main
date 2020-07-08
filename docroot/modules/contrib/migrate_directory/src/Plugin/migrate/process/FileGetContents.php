<?php

namespace Drupal\migrate_directory\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Dumps the input value to stdout. Passes the rest through.
 *
 * @MigrateProcessPlugin(
 *   id = "file_get_contents"
 * )
 *
 */
class FileGetContents extends ProcessPluginBase {

  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Skip everything if the value is empty.
    if (empty($value)) {
      return NULL;
    }

    // Get the offset if one is provided.
    $offset = isset($this->configuration['offset']) ? $this->configuration['offset'] : 0;

    // Get the file with a max length...
    if (isset($this->configuration['maxlen'])) {
      $maxlen = $this->configuration['maxlen'];
      $content = file_get_contents($value, FALSE, NULL, $offset, $maxlen);
    }
    else {
      // ...or don't, if none were given.
      $content = file_get_contents($value, FALSE, NULL, $offset);
    }

    // Couldn't read the file? Return NULL because migrate likes that.
    if ($content === FALSE) {
      return NULL;
    }

    // Otherwise return the file content.
    return $content;
  }

}

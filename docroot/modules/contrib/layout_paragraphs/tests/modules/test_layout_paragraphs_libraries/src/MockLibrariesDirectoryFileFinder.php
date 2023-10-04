<?php

namespace Drupal\test_layout_paragraphs_libraries;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;

/**
 * {@inheritdoc}
 */
class MockLibrariesDirectoryFileFinder extends LibrariesDirectoryFileFinder {

  /**
   * {@inheritdoc}
   */
  public function find($path) {
    if ($path === 'dragula') {
      return 'string';
    }

    return parent::find($path);
  }

}
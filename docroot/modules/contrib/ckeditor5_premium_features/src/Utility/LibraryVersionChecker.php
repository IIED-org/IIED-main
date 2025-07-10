<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features\Utility;

use Drupal\Core\Asset\LibraryDiscoveryInterface;

/**
 * Provides the library version checker for ckeditor5.
 */
class LibraryVersionChecker {

  /**
   * Current version of core ckeditor5.
   *
   * @var string
   */
  protected string $ckeditor5Version;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   LibraryDiscovery.
   */
  public function __construct(
    protected LibraryDiscoveryInterface $libraryDiscovery,
  ) {
    $lib = $libraryDiscovery->getLibraryByName('core', 'ckeditor5');
    $this->ckeditor5Version = $lib['version'];
  }

  /**
   * Performs version compare.
   *
   * @param string $expectedVersion
   *   Expected or higher version of the library.
   *
   * @return bool
   *   If version is the same or higher returns TRUE.
   */
  public function isLibraryVersionHigherOrEqual(string $expectedVersion): bool {
    if (in_array($this->ckeditor5Version, ['nightly', 'master'])) {
      return TRUE;
    }
    if (version_compare($this->ckeditor5Version, $expectedVersion) >= 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get CKEditor 5 version installed in the system.
   *
   * @return string
   *    CKEditor 5 version.
   */
  public function getCurrentVersion(): string {
    return $this->ckeditor5Version;
  }

}

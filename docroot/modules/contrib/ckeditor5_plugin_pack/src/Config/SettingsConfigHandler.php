<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_plugin_pack\Config;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class SettingsConfigHandler implements SettingsConfigHandlerInterface {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected LibraryDiscoveryInterface $libraryDiscovery;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs the handler.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   Library discovery service.
   */
  public function __construct(protected LibraryDiscoveryInterface $library_discovery, protected ConfigFactoryInterface $config_factory) {
    $this->libraryDiscovery = $library_discovery;
    $this->config = $config_factory->get('ckeditor5_plugin_pack.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getDllLocation(string $file_name = ''): string {
    $base_path = $this->config?->get('dll_location') ?: $this->getDefaultDllLocation();

    $base_path = rtrim($base_path, ' /') . '/';

    $base_path = $this->replaceTokens($base_path);

    return $base_path . $file_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultDllLocation(): string {
    return 'https://cdn.ckeditor.com/ckeditor5/' . SettingsConfigHandlerInterface::DLL_PATH_VERSION_TOKEN . '/dll/';
  }

  /**
   * Gets the DLLs version.
   *
   * @return string
   *   The DLLs version.
   */
  public function getDllVersion(): string {
    $library = $this->libraryDiscovery->getLibraryByName('core', 'ckeditor5');

    return $library['version'];
  }

  /**
   * {@inheritdoc}
   */
  public function isLocalLibraryPathSpecified(): bool {
    return !empty($this->config?->get('dll_location'));
  }

  /**
   * Replaces supported tokens in passed parameter path.
   *
   * @param string $path
   *   A URL with potential tokens to replace.
   *
   * @return string
   */
  protected function replaceTokens(string $path): string {
    $tokens = [
      SettingsConfigHandlerInterface::DLL_PATH_VERSION_TOKEN => $this->getDllVersion(),
    ];

    foreach ($tokens as $token => $value) {
      if (!$value) {
        continue;
      }
      $path = str_replace($token, $value, $path);
    }

    return $path;
  }

}

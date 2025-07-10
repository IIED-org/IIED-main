<?php

/*
 * Copyright (c) 2003-2025, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

declare(strict_types=1);

namespace Drupal\ckeditor5_premium_features\Utility;

use Drupal\ckeditor5_premium_features\Plugin\CKEditor5Plugin\ExportBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Theme\ThemeManager;

/**
 * Css style list provider.
 */
class CssStyleProvider {

  /**
   * Creates CssStyleProvider instance.
   *
   * @param \Drupal\Core\Theme\ThemeManager $themeManager
   *   Theme ThemeManager service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   File Url generator service.
   */
  public function __construct(protected ThemeManager $themeManager,
                              protected FileSystemInterface $fileSystem,
                              protected FileUrlGeneratorInterface $fileUrlGenerator) {
  }

  /**
   * Check if url is font css.
   *
   * @param string $url
   *   Url to style file.
   *
   * @return bool
   *   Is font url.
   */
  private function isFontCssFile(string $url): bool {
    return str_ends_with($url, 'fonts.css') || str_starts_with($url, '//fonts');
  }

  /**
   * Get List of styles file urls.
   *
   * @param bool $fonts_list
   *   Return fonts files.
   *
   * @return array
   *   Css file style list.
   */
  public function getCssStylesheetsUrls(bool $fonts_list = FALSE): array {
    $styles_urls = $this->getCssFilesListFromActiveTheme();
    $fonts = [];
    foreach ($styles_urls as $styles_url) {
      if ($this->isFontCssFile($styles_url)) {
        $fonts[] = $styles_url;
      }
    }

    return $fonts_list ? $fonts : array_diff($styles_urls, $fonts);
  }

  /**
   * Get list of css styles file used in current theme.
   *
   * @return array
   *   List of urls.
   */
  public function getCssFilesListFromActiveTheme(): array {
    $active_theme = $this->themeManager->getActiveTheme();

    return _ckeditor5_theme_css($active_theme->getName());
  }

  /**
   * Get list all css used in current editor instance.
   *
   * Formatted in pattern:
   *
   * @see isFontCssFile();
   * - Fonts files.
   * - EDITOR_STYLES (default one).
   * - All others (non fonts).
   */
  public function getFormattedListOfCssFiles(): array {
    $fonts = $this->getCssStylesheetsUrls(TRUE);
    $non_fonts = $this->getCssStylesheetsUrls();

    return array_merge($fonts, ['EDITOR_STYLES'], $non_fonts);
  }

  /**
   * Update file with the custom css.
   *
   * Delete file if css content is NULL and the file exists.
   *
   * @param string|null $customCss
   *   CSS content.
   * @param string $fileName
   *   File name.
   * @param string $directoryPath
   *   Directory path where the file is located.
   */
  public function updateCustomCssFile(?string $customCss, string $fileName, string $directoryPath = ExportBase::CUSTOM_CSS_DIRECTORY_PATH): void {
    $filePath = $directoryPath . $fileName . '.css';
    if ($customCss) {
      $this->fileSystem->prepareDirectory($directoryPath, FileSystemInterface::CREATE_DIRECTORY);
      $this->fileSystem->saveData($customCss, $filePath, FileSystemInterface::EXISTS_REPLACE);
    }
    else {
      $relativePath = $this->fileUrlGenerator->generateString($filePath);
      if ($this->fileSystem->getDestinationFilename($relativePath, FileSystemInterface::EXISTS_ERROR)) {
        $this->fileSystem->delete($filePath);
      }
    }
  }

  /**
   * Get the custom css file path.
   *
   * @param string $fileName
   *   File name.
   * @param string $directoryPath
   *   Directory path where the file is located.
   *
   * @return bool|string
   *   Relative path to the file or FALSE.
   */
  public function getCustomCssFile(string $fileName, string $directoryPath = ExportBase::CUSTOM_CSS_DIRECTORY_PATH):bool|string {
    $filePath = $directoryPath . $fileName . '.css';
    $relativePath = $this->fileUrlGenerator->generateString($filePath);
    if (!$this->fileSystem->getDestinationFilename($filePath, FileSystemInterface::EXISTS_ERROR)) {
      return $relativePath;
    }
    return FALSE;
  }

}

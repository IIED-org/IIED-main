<?php

namespace Drupal\media_pdf_thumbnail;

use Spatie\PdfToImage\Exceptions\PdfDoesNotExist;
use Spatie\PdfToImage\Pdf as SpatiePdfAlias;

/**
 * Class Pdf.
 *
 *  Extends SpatiePdfAlias to add a constructor
 *  that allows to pass a page number.
 *
 * @package Drupal\media_pdf_thumbnail
 */
class Pdf extends SpatiePdfAlias {

  /**
   * Pdf constructor.
   *
   * @param string $pdfFile
   *   The pdf file.
   * @param string|int $page
   *   The page number.
   *
   * @throws \Spatie\PdfToImage\Exceptions\PdfDoesNotExist|\ImagickException
   *   If the pdf file does not exist.
   */
  public function __construct(string $pdfFile, string | int $page = 0) {
    if (!file_exists($pdfFile)) {
      throw new PdfDoesNotExist("File `{$pdfFile}` does not exist");
    }
    $this->pdfFile = $pdfFile;
    $this->imagick = new \Imagick();
    try {
      $pageIndex = $page >= 1 ? $page - 1 : 0;
      $this->imagick->readImage($this->getFileName($pdfFile, $pageIndex));
    }
    catch (\ImagickException $e) {
      // If the page is not found, try to get the first page.
      $this->imagick->readImage($pdfFile . sprintf('[%s]', 0));
    }
  }

  /**
   * Get the file name.
   *
   * @param string $pdfFile
   *   The pdf file.
   * @param string|int $page
   *   The page number.
   *
   * @return string
   *   The file name.
   */
  protected function getFileName(string $pdfFile, string | int $page): string {
    return $pdfFile . sprintf('[%s]', $page);
  }

}

<?php

namespace Drupal\field_formatter_file_size\Plugin\Field\FieldFormatter;

/**
 * @file
 * Field Formatter File Size.
 */

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * @FieldFormatter(
 *   id = "File_size",
 *   label = @Translation("File Size"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileSizeFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];
    $summary[] = $this->t('Display the file size');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {

      $item = $file->_referringItem;

      $elements[$delta] = [
        '#theme' => 'file_link',
        '#file' => $file,
        '#description' => $this->t('@label (@size)', [
          '@label' => !empty($item->description) ? $item->description : $file->label(),
          '@size' => format_size($file->getSize()),
        ]),
        '#cache' => [
          'tags' => $file->getCacheTags(),
        ],
      ];

      // Pass field item attributes to the theme function.
      if (isset($item->_attributes)) {
        $elements[$delta] += ['#attributes' => []];
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }
    }

    return $elements;
  }

}

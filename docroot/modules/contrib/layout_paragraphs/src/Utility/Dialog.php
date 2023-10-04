<?php

namespace Drupal\layout_paragraphs\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;

/**
 * Defines a Dialog utility class.
 */
class Dialog {

  /**
   * Generates a dialog id for a given layout.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout paragraphs object.
   *
   * @return string
   *   The id.
   */
  public static function dialogId(LayoutParagraphsLayout $layout) {
    return Html::getId('lpb-dialog-' . $layout->id());
  }

  /**
   * Generates a dialog selector for a given layout.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout paragraphs layout object.
   *
   * @return string
   *   The dom selector for the dialog.
   */
  public static function dialogSelector(LayoutParagraphsLayout $layout) {
    return '#' . static::dialogId($layout);
  }

  /**
   * Returns a CloseDialogComand with the correct selector.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout $layout
   *   The layout paragraphs layout object.
   *
   * @return \Drupal\Core\Ajax\CommandInterface
   *   The close command.
   */
  public static function closeDialogCommand(LayoutParagraphsLayout $layout) {
    return new CloseDialogCommand(static::dialogSelector($layout));
  }

  /**
   * Returns an array of dialog settings for modal edit forms.
   *
   * @param \Drupal\layout_paragraphs\LayoutParagraphsLayout|null $layout
   *   If provided, will add a target for the correct dialog id value.
   *
   * @return array
   *   The modal settings.
   */
  public static function dialogSettings(LayoutParagraphsLayout $layout = NULL) {
    $config = \Drupal::config('layout_paragraphs.modal_settings');

    $modal_settings = [
      'width' => $config->get('width') ?? '90%',
      'height' => $config->get('height') ?? 'auto',
      'autoResize' => $config->get('autoresize'),
      'modal' => TRUE,
      'drupalAutoButtons' => FALSE,
      'dialogClass' => 'lpb-dialog',
    ];

    if (!empty($layout)) {
      $modal_settings['target'] = static::dialogId($layout);
    }

    return $modal_settings;
  }

}

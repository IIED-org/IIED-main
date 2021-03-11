<?php

namespace Drupal\notification\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "notification" plugin.
 *
 * @CKEditorPlugin(
 *   id = "notification",
 *   label = @Translation("Notification"),
 * )
 */
class Notification extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/notification/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

}

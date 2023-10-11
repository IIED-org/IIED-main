<?php

declare(strict_types=1);

namespace Drupal\video_embed_wysiwyg\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade for the CKEditor plugin.
 *
 * @CKEditor4To5Upgrade(
 *   id = "video_embed_wysiwyg",
 *   cke4_buttons = {
 *     "video_embed",
 *   },
 *   cke4_plugin_settings = {
 *     "video_embed",
 *   }
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class VideoEmbed extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    switch ($cke4_button) {
      case 'video_embed':
        return ['videoEmbed'];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    switch ($cke4_plugin_id) {
      case 'video_embed':
        // Identical configuration.
        return ['video_embed_wysiwyg_video_embed' => $cke4_plugin_settings];

      default:
        throw new \OutOfBoundsException();
    }
  }

  /**
   * @inheritDoc
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    throw new \OutOfBoundsException();
  }

}

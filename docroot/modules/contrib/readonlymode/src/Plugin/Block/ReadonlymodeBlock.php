<?php

namespace Drupal\readonlymode\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Read only mode info block.
 *
 * @Block(
 *   id = "readonlymode_block",
 *   admin_label = @Translation("Read Only Mode")
 * )
 */
class ReadonlymodeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];
    $config = \Drupal::config('readonlymode.settings');
    if ($config->get('enabled')) {
      $site = \Drupal::config('system.site');
      $output = [
        '#title' => $this->t('Read only mode'),
        '#markup' => $site->get('name') . ' is currently in maintenance. During this maintenance it is not possible to change site content (like comments, pages and users).',
      ];

    }
    return $output;
  }

}

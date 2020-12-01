<?php

namespace Drupal\iied_utils\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Header search' block.
 *
 * @Block(
 *   id = "header_search_block",
 *   admin_label = @Translation("Header search block"),
 * )
 */
class PubsSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\iied_utils\Form\HeaderSearch');
  }

}

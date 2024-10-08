<?php

/**
 * @file
 * Post-update functions for Colorbox.
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Saves the image style dependencies into form and view display entities.
 */
function colorbox_post_update_image_style_dependencies() {
  // Merge view and form displays. Use array_values() to avoid key collisions.
  $displays = array_merge(array_values(EntityViewDisplay::loadMultiple()), array_values(EntityFormDisplay::loadMultiple()));
  /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface[] $displays */
  foreach ($displays as $display) {
    // Re-save each config entity to add missed dependencies.
    $display->save();
  }
}

<?php

namespace Drupal\ds\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a DsFieldTemplate attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DsFieldTemplate extends Plugin {

  /**
   * Constructs a DsFieldTemplate plugin attribute object.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The human-readable name of the DS field layout plugin.
   * @param string $theme
   *   The theme function for this field layout.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly string $theme,
    public readonly ?string $deriver = NULL
  ) {}

}

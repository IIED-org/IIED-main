<?php

namespace Drupal\ds\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a DsField attribute object.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class DsField extends Plugin {

  /**
   * Constructs a DsField plugin attribute object.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   (optional) The human-readable name of the DS plugin.
   * @param string|string[]|null $entity_type
   *   (optional) The entity type this plugin should work on.
   * @param array|null $ui_limit
   *   (optional) An array of limits for showing this field.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   * @param string|null $provider
   *   (optional) The provider of the DS plugin.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly mixed $entity_type = NULL,
    public readonly ?array $ui_limit = NULL,
    public readonly ?string $deriver = NULL,
    protected ?string $provider = NULL,
  ) {}

}

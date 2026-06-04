<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Resolves preferred/alternative field reference values for name formatting.
 *
 * @internal
 */
interface AdditionalComponentInterface {

  /**
   * Gets the rendered or label value for an additional component reference.
   */
  public function getAdditionalComponent(FieldItemListInterface $items, mixed $key_value, mixed $sep_value): string;

}

<?php

namespace Drupal\name\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;

/**
 * Formats a string for HTML display by replacing variable placeholders.
 *
 * Adds special handling of @names, @last.
 */
class NameListFormattableMarkup implements MarkupInterface {

  /**
   * The names.
   *
   * @var array
   */
  protected $names = [];


  /**
   * The name separator.
   *
   * @var string
   */
  protected $separator = ', ';

  /**
   * Constructor for NameListFormattableMarkup.
   *
   * @param array $names
   *   Names to render in the list.
   * @param mixed $separator
   *   A separator between rendered names. When the separator implements
   *   MarkupInterface, it is treated as trusted markup. Any other value is
   *   cast to string and escaped.
   */
  public function __construct(array $names = [], $separator = ', ') {
    $this->names = $names;
    $this->separator = $separator instanceof MarkupInterface ? (string) $separator : Html::escape((string) $separator);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->escapeValues($this->names);
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() : mixed {
    return $this->__toString();
  }

  /**
   * Escapes values if needed.
   *
   * @param array|string|\Drupal\Component\Render\MarkupInterface $value
   *   A placeholder replacement value. Will recursively escape array values
   *   using the specified separator.
   *
   * @return string
   *   The properly escaped replacement value.
   */
  protected function escapeValues($value) {
    if (is_array($value)) {
      $escaped = [];
      foreach ($value as $child_value) {
        $escaped[] = $this->escapeValues($child_value);
      }
      return implode($this->separator, $escaped);
    }

    return $value instanceof MarkupInterface ? (string) $value : Html::escape($value);
  }

}

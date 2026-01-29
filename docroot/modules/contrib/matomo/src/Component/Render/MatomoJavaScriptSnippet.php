<?php

declare(strict_types=1);

namespace Drupal\matomo\Component\Render;

use Drupal\Component\Render\MarkupInterface;

/**
 * Formats a string for JavaScript display.
 */
class MatomoJavaScriptSnippet implements MarkupInterface {

  /**
   * The string to escape.
   *
   * @var string
   */
  protected $string;

  /**
   * Constructs an HtmlEscapedText object.
   *
   * @param string $string
   *   The string to escape. This value will be cast to a string.
   */
  public function __construct($string) {
    $this->string = (string) $string;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->string;
  }

  /**
   * {@inheritdoc}
   */
  #[\ReturnTypeWillChange]
  public function jsonSerialize() {
    return $this->__toString();
  }

}

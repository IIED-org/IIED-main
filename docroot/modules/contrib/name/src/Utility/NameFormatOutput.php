<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;

/**
 * Wraps a formatted name string in the appropriate Drupal render type.
 *
 * @internal
 */
final class NameFormatOutput {

  /**
   * Wraps a formatted name string according to the active markup mode.
   *
   * - simple / rdfa / microdata: component values are already HTML-escaped by
   *   NameFormatTokens, so the string is wrapped in FormattableMarkup with no
   *   additional substitutions.
   * - raw: the string passes through Xss::filterAdmin() and is marked safe.
   * - none (default): the string is HTML-escaped via HtmlEscapedText.
   *
   * @param string $name_string
   *   The assembled name string from NameFormatParser::format().
   * @param string $markup
   *   The markup mode key.
   *
   * @return mixed
   *   A renderable value (string or MarkupInterface object).
   */
  public static function wrap(string $name_string, string $markup): mixed {
    switch ($markup) {
      case 'simple':
      case 'rdfa':
      case 'microdata':
        return (new FormattableMarkup($name_string, []))->jsonSerialize();

      case 'raw':
        return Markup::create(Xss::filterAdmin($name_string));

      case 'none':
      default:
        return (new HtmlEscapedText($name_string))->jsonSerialize();

    }
  }

}

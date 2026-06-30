<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

use Drupal\Component\Utility\Html;

/**
 * Builds the token map used by NameFormatLexer from name components.
 *
 * All rendering decisions (markup mode, separators) are passed in as plain
 * parameters; this class carries no instance state.
 *
 * @internal
 */
final class NameFormatTokens {

  /**
   * Schema.org property mapping for component keys.
   *
   * Note that credentials intentionally use "credential" here, so component
   * key "credentials" does not get a schema attribute.
   *
   * @var array<string, string>
   */
  private const SCHEMA_PROPERTY_MAP = [
    'title'       => 'honorificPrefix',
    'given'       => 'givenName',
    'middle'      => 'additionalName',
    'family'      => 'familyName',
    'credential'  => 'honorificSuffix',
    'alternative' => 'alternateName',
  ];

  /**
   * Builds the full token map from an array of name components.
   *
   * The returned array is keyed by single-character token letters.
   * Conditional tokens (d, D, e, E) are populated only when the required
   * source components are non-empty; they fall back to NULL so that
   * NameFormatParser::resolveTokenValue() converts them to empty strings.
   *
   * @param array $name_components
   *   Keyed array of raw name component values.
   * @param string $sep1
   *   First separator (token i).
   * @param string $sep2
   *   Second separator (token j).
   * @param string $sep3
   *   Third separator (token k).
   * @param string $markup
   *   Markup mode (none, simple, microdata, rdfa).
   *
   * @return array<string, string|null>
   *   Token map keyed by format letter.
   */
  public static function build(
    array $name_components,
    string $sep1,
    string $sep2,
    string $sep3,
    string $markup,
  ): array {
    $name_components = (array) $name_components;
    $name_components += [
      'title'        => '',
      'given'        => '',
      'middle'       => '',
      'family'       => '',
      'credentials'  => '',
      'generational' => '',
      'preferred'    => '',
      'alternative'  => '',
    ];

    $tokens = [
      't' => self::renderComponent($name_components['title'], 'title', $markup),
      'g' => self::renderComponent($name_components['given'], 'given', $markup),
      'p' => self::renderFirstComponent(
        [$name_components['preferred'], $name_components['given']],
        'given',
        $markup,
      ),
      'q' => self::renderComponent($name_components['preferred'], 'preferred', $markup),
      'm' => self::renderComponent($name_components['middle'], 'middle', $markup),
      'f' => self::renderComponent($name_components['family'], 'family', $markup),
      'c' => self::renderComponent($name_components['credentials'], 'credentials', $markup),
      'a' => self::renderComponent($name_components['alternative'], 'alternative', $markup),
      's' => self::renderComponent($name_components['generational'], 'generational', $markup),
      'v' => self::renderComponent($name_components['preferred'], 'preferred', $markup, 'initial'),
      'w' => self::renderFirstComponent(
        [$name_components['preferred'], $name_components['given']],
        'given',
        $markup,
        'initial',
      ),
      'x' => self::renderComponent($name_components['given'], 'given', $markup, 'initial'),
      'y' => self::renderComponent($name_components['middle'], 'middle', $markup, 'initial'),
      'z' => self::renderComponent($name_components['family'], 'family', $markup, 'initial'),
      'A' => self::renderComponent($name_components['alternative'], 'alternative', $markup, 'initial'),
      'I' => self::renderComponent(
        $name_components['given'] . ' ' . $name_components['family'],
        'initials',
        $markup,
        'initials',
      ),
      'J' => self::renderComponent(
        $name_components['given'] . ' ' . $name_components['middle'] . ' ' . $name_components['family'],
        'initials',
        $markup,
        'initials',
      ),
      'K' => self::renderComponent($name_components['given'], 'initials', $markup, 'initials'),
      'M' => self::renderComponent(
        $name_components['given'] . ' ' . $name_components['middle'],
        'initials',
        $markup,
        'initials',
      ),
      'i' => $sep1,
      'j' => $sep2,
      'k' => $sep3,
    ];

    $preferred = $tokens['p'];
    $given     = $tokens['g'];
    $family    = $tokens['f'];

    if ($preferred || $family) {
      $tokens += [
        'd' => $preferred ? $preferred : $family,
        'D' => $family ? $family : $preferred,
      ];
    }
    if ($given || $family) {
      $tokens += [
        'e' => $given ? $given : $family,
        'E' => $family ? $family : $given,
      ];
    }

    // Ensure d, D, e, E are always present so resolveValue() converts
    // them to empty strings rather than treating them as literal characters.
    $tokens += [
      'd' => NULL,
      'D' => NULL,
      'e' => NULL,
      'E' => NULL,
    ];

    return $tokens;
  }

  /**
   * Resolves a single format character to its token value.
   *
   * Returns the token's string value when a matching entry exists in the token
   * map. Non-string token values (e.g. NULL) resolve to an empty string.
   * Characters with no matching token are returned as-is (literal output).
   *
   * @param string $char
   *   A single format character.
   * @param array $tokens
   *   The token map.
   *
   * @return string
   *   The resolved value or the original character.
   */
  public static function resolveValue(string $char, array $tokens): string {
    if (array_key_exists($char, $tokens)) {
      return is_string($tokens[$char]) ? $tokens[$char] : '';
    }

    return $char;
  }

  /**
   * Renders a single name component value.
   *
   * Returns NULL for empty or zero-length values. When markup mode is active,
   * the value is HTML-escaped and wrapped in a <span> element carrying the
   * component class and, for microdata/rdfa, the appropriate schema attribute.
   *
   * Note: the schema.org map uses the key 'credential' (singular) while the
   * component array uses 'credentials' (plural), so the credentials component
   * intentionally omits the itemprop/property attribute.
   *
   * @param string|null $value
   *   The raw component value.
   * @param string $component_key
   *   The component machine name (e.g. 'given', 'family').
   * @param string $markup
   *   Markup mode: none, simple, microdata, or rdfa.
   * @param string|null $modifier
   *   Modifier key: 'initial' for first-letter, 'initials' for all-word
   *   initials, or NULL for no modifier.
   *
   * @return string|null
   *   The rendered string, or NULL when the value is empty.
   */
  public static function renderComponent(
    ?string $value,
    string $component_key,
    string $markup,
    ?string $modifier = NULL,
  ): ?string {
    if (empty($value) || !mb_strlen($value)) {
      return NULL;
    }

    $value = self::applyComponentModifier($value, $modifier);
    return self::formatWithMarkup($value, $component_key, $markup);
  }

  /**
   * Applies initial/initials transforms for a component value.
   */
  private static function applyComponentModifier(
    string $value,
    ?string $modifier,
  ): string {
    return match ($modifier) {
      'initial' => mb_substr($value, 0, 1),
      'initials' => UnicodeExtras::initials($value),
      default => $value,
    };
  }

  /**
   * Formats a component value according to the configured markup mode.
   */
  private static function formatWithMarkup(
    string $value,
    string $component_key,
    string $markup,
  ): string {
    return match ($markup) {
      'simple' => self::wrapSimpleSpan($component_key, $value),
      'microdata' => self::wrapMicrodataSpan($component_key, $value),
      'rdfa' => self::wrapRdfaSpan($component_key, $value),
      default => $value,
    };
  }

  /**
   * Wraps a value in a simple classed span.
   */
  private static function wrapSimpleSpan(
    string $component_key,
    string $value,
  ): string {
    return '<span class="' . Html::escape($component_key) . '">'
      . Html::escape($value) . '</span>';
  }

  /**
   * Wraps a value in a microdata span.
   */
  private static function wrapMicrodataSpan(
    string $component_key,
    string $value,
  ): string {
    $itemprop = self::schemaAttribute($component_key, 'itemprop');
    return '<span class="' . Html::escape($component_key) . '"'
      . $itemprop . '>' . Html::escape($value) . '</span>';
  }

  /**
   * Wraps a value in an RDFa span.
   */
  private static function wrapRdfaSpan(
    string $component_key,
    string $value,
  ): string {
    $property = self::schemaAttribute($component_key, 'property', 'schema:');
    return '<span class="' . Html::escape($component_key) . '"'
      . $property . '>' . Html::escape($value) . '</span>';
  }

  /**
   * Returns a schema attribute string for the given component key.
   */
  private static function schemaAttribute(
    string $component_key,
    string $attribute_name,
    string $prefix = '',
  ): string {
    if (!isset(self::SCHEMA_PROPERTY_MAP[$component_key])) {
      return '';
    }

    return ' ' . $attribute_name . '="' . $prefix
      . self::SCHEMA_PROPERTY_MAP[$component_key] . '"';
  }

  /**
   * Returns the rendered output of the first non-empty value in the list.
   *
   * @param array $values
   *   Candidate values in priority order.
   * @param string $component_key
   *   The component context used for markup wrapping.
   * @param string $markup
   *   Markup mode: none, simple, microdata, or rdfa.
   * @param string|null $modifier
   *   Optional modifier passed through to renderComponent().
   *
   * @return string|null
   *   The first non-empty rendered value, or NULL when all are empty.
   */
  public static function renderFirstComponent(
    array $values,
    string $component_key,
    string $markup,
    ?string $modifier = NULL,
  ): ?string {
    foreach ($values as $value) {
      $output = self::renderComponent($value, $component_key, $markup, $modifier);
      if (isset($output) && strlen($output)) {
        return $output;
      }
    }

    return NULL;
  }

}

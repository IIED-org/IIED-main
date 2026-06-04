<?php

declare(strict_types=1);

namespace Drupal\name\Service;

/**
 * Defines a name formatter for component arrays and lists.
 */
interface NameFormatterInterface {

  /**
   * Formats an array of name components.
   *
   * @param array<string, mixed> $components
   *   Name components (title, given, middle, family, etc.).
   * @param string $type
   *   Name format entity id; falls back to default.
   * @param string|null $langcode
   *   Language code or NULL for UI language.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   A renderable object representing the name.
   */
  public function format(array $components, $type = 'default', $langcode = NULL);

  /**
   * Formats a list of author information.
   *
   * @param array<int, array<string, mixed>> $items
   *   Nested name component arrays.
   * @param string $type
   *   Name format entity id.
   * @param string $list_type
   *   Name list format entity id.
   * @param string|null $langcode
   *   Language code or NULL for UI language.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The processed name in a MarkupInterface object.
   */
  public function formatList(array $items, $type = 'default', $list_type = 'default', $langcode = NULL);

  /**
   * Sets the value of a setting for the formatter.
   *
   * @param string $key
   *   The setting name.
   * @param mixed $value
   *   The setting value.
   *
   * @return static
   *   The formatter instance.
   */
  public function setSetting($key, $value);

  /**
   * Gets the value of a setting for the formatter.
   *
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The value of the setting or NULL if not found.
   */
  public function getSetting($key);

  /**
   * Defines the supported final delimiter options.
   *
   * @param bool $include_examples
   *   TRUE to include examples in the options.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed options that are supported.
   */
  public function getLastDelimiterTypes($include_examples = TRUE);

  /**
   * Deprecated: use getLastDelimiterTypes() instead.
   *
   * @param bool $include_examples
   *   TRUE to include examples in the options.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed options that are supported.
   *
   *   cspell:ignore delimitor
   *
   * @deprecated in name:8.x-1.1 and is removed from name:2.0.0. Use
   *   getLastDelimiterTypes() instead.
   *
   * @see https://www.drupal.org/project/name/issues/3518599
   */
  public function getLastDelimitorTypes($include_examples = TRUE);

  /**
   * Defines the supported final delimiter behavior options.
   *
   * @param bool $include_examples
   *   TRUE to include examples in the options.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed options that are supported.
   */
  public function getLastDelimiterBehaviors($include_examples = TRUE);

  /**
   * Deprecated: use getLastDelimiterBehaviors() instead.
   *
   * @param bool $include_examples
   *   TRUE to include examples in the options.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup|string>
   *   Keyed options that are supported.
   *
   *   cspell:ignore delimitor
   *
   * @deprecated in name:8.x-1.1 and is removed from name:2.0.0. Use
   *   getLastDelimiterBehaviors() instead.
   *
   * @see https://www.drupal.org/project/name/issues/3518599
   */
  public function getLastDelimitorBehaviors($include_examples = TRUE);

}

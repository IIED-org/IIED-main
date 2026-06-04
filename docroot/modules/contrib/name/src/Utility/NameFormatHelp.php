<?php

declare(strict_types=1);

namespace Drupal\name\Utility;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides translated labels and render arrays for the format-string UI.
 *
 * All strings are returned as TranslatableMarkup instances so no Drupal
 * service container is required; translation is deferred to render time.
 *
 * @internal
 */
final class NameFormatHelp {

  /**
   * Returns the supported markup mode labels keyed by machine name.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Markup option labels.
   */
  public static function markupOptions(): array {
    return [
      'none'      => new TranslatableMarkup('No markup'),
      'raw'       => new TranslatableMarkup('Raw, unescaped text'),
      'simple'    => new TranslatableMarkup('Component classes'),
      'microdata' => new TranslatableMarkup('Microdata itemprop components'),
      'rdfa'      => new TranslatableMarkup('RDFa property components'),
    ];
  }

  /**
   * Returns format token descriptions keyed by token letter.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Token descriptions keyed by format letter.
   */
  public static function tokenHelp(): array {
    $tokens = self::tokenDefinitions();

    foreach ($tokens as $letter => $description) {
      if (preg_match('/^[a-z]+$/', $letter)) {
        $tokens[$letter] = new TranslatableMarkup(
          '@description<br><small>(lowercase @letter)</small>',
          [
            '@description' => $description,
            '@letter'      => mb_strtoupper($letter),
          ],
        );
      }
      elseif (preg_match('/^[A-Z]+$/', $letter)) {
        $tokens[$letter] = new TranslatableMarkup(
          '@description<br><small>(uppercase @letter)</small>',
          [
            '@description' => $description,
            '@letter'      => mb_strtoupper($letter),
          ],
        );
      }
    }

    return $tokens;
  }

  /**
   * Returns plain format token descriptions keyed by token letter.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Token descriptions keyed by format letter.
   */
  public static function tokenHelpPlain(): array {
    return self::tokenDefinitions();
  }

  /**
   * Returns plain token definitions keyed by token letter.
   *
   * @return array<string, \Drupal\Core\StringTranslation\TranslatableMarkup>
   *   Token descriptions keyed by format letter.
   */
  private static function tokenDefinitions(): array {
    return [
      't' => new TranslatableMarkup('Title.'),
      'p' => new TranslatableMarkup('Preferred name, use given name if not set.'),
      'q' => new TranslatableMarkup('Preferred name.'),
      'g' => new TranslatableMarkup('Given name.'),
      'm' => new TranslatableMarkup('Middle name(s).'),
      'f' => new TranslatableMarkup('Family name.'),
      'c' => new TranslatableMarkup('Credentials.'),
      's' => new TranslatableMarkup('Generational suffix.'),
      'a' => new TranslatableMarkup('Alternative value.'),
      'v' => new TranslatableMarkup('First letter preferred name.'),
      'w' => new TranslatableMarkup('First letter preferred or given name.'),
      'x' => new TranslatableMarkup('First letter given.'),
      'y' => new TranslatableMarkup('First letter middle.'),
      'z' => new TranslatableMarkup('First letter family.'),
      'A' => new TranslatableMarkup('First letter of alternative value.'),
      'I' => new TranslatableMarkup('Initials (all) from given and family.'),
      'J' => new TranslatableMarkup('Initials (all) from given, middle and family.'),
      'K' => new TranslatableMarkup('Initials (all) from given.'),
      'M' => new TranslatableMarkup('Initials (all) from given and middle.'),
      'd' => new TranslatableMarkup('Conditional: Either the preferred given or family name. Preferred name is given preference over given or family names.'),
      'D' => new TranslatableMarkup('Conditional: Either the preferred given or family name. Family name is given preference over preferred or given names.'),
      'e' => new TranslatableMarkup('Conditional: Either the given or family name. Given name is given preference.'),
      'E' => new TranslatableMarkup('Conditional: Either the given or family name. Family name is given preference.'),
      'i' => new TranslatableMarkup('Separator 1.'),
      'j' => new TranslatableMarkup('Separator 2.'),
      'k' => new TranslatableMarkup('Separator 3.'),
      '\\' => new TranslatableMarkup('You can prevent a character in the format string from being expanded by escaping it with a preceding backslash.'),
      'L' => new TranslatableMarkup('Modifier: Converts the next token to all lowercase.'),
      'U' => new TranslatableMarkup('Modifier: Converts the next token to all uppercase.'),
      'F' => new TranslatableMarkup('Modifier: Converts the first letter to uppercase.'),
      'G' => new TranslatableMarkup('Modifier: Converts the first letter of ALL words to uppercase.'),
      'T' => new TranslatableMarkup('Modifier: Trims whitespace around the next token.'),
      'S' => new TranslatableMarkup('Modifier: Ensures that the next token is safe for the display.'),
      'B' => new TranslatableMarkup('Modifier: Use the first word of the next token.'),
      'b' => new TranslatableMarkup('Modifier: Use the last word of the next token.'),
      '+' => new TranslatableMarkup('Conditional: Insert the token if both the surrounding tokens are not empty.'),
      '-' => new TranslatableMarkup('Conditional: Insert the token if the previous token is not empty.'),
      '~' => new TranslatableMarkup('Conditional: Insert the token if the previous token is empty.'),
      '=' => new TranslatableMarkup('Conditional: Insert the token if the next token is not empty.'),
      '^' => new TranslatableMarkup('Conditional: Insert the token if the next token is empty.'),
      '|' => new TranslatableMarkup('Conditional: Uses the previous token unless empty, otherwise it uses this token.'),
      '(' => new TranslatableMarkup('Group: Start of token grouping.'),
      ')' => new TranslatableMarkup('Group: End of token grouping.'),
    ];
  }

  /**
   * Returns a bare theme render array for the format token reference.
   *
   * Unlike renderableTokenHelp(), this method omits the collapsible
   * #details wrapper and is intended for contexts where the list should
   * always be visible, such as help topics.
   *
   * @return array<string, mixed>
   *   A Drupal render array.
   */
  public static function renderableTokenReference(): array {
    return [
      '#theme'  => 'name_format_parameter_help',
      '#tokens' => self::tokenHelp(),
    ];
  }

  /**
   * Returns a renderable details array containing format token help.
   *
   * @return array<string, mixed>
   *   A Drupal render array.
   */
  public static function renderableTokenHelp(): array {
    return [
      '#type'        => 'details',
      '#title'       => new TranslatableMarkup('Format string help'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
      '#parents'     => [],
      'format_parameters' => [
        '#theme'  => 'name_format_parameter_help',
        '#tokens' => self::tokenHelp(),
      ],
    ];
  }

}

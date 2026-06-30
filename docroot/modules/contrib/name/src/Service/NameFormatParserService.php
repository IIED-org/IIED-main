<?php

declare(strict_types=1);

namespace Drupal\name\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\name\Utility\NameFormatHelp;
use Drupal\name\Utility\NameFormatOutput;
use Drupal\name\Utility\NameFormatParser;
use Drupal\name\Utility\NameFormatTokens;

/**
 * Converts a name from an array of components into a defined format.
 *
 * The parsing and rendering mechanics have been extracted into stateless
 * utility classes under Drupal\name\Utility. This class retains its full
 * public and protected API for backwards compatibility with subclasses
 * and existing service consumers.
 *
 * @see \Drupal\name\Utility\NameFormatParser
 * @see \Drupal\name\Utility\NameFormatTokens
 * @see \Drupal\name\Utility\NameFormatOutput
 * @see \Drupal\name\Utility\NameFormatHelp
 */
class NameFormatParserService implements NameFormatParserInterface {

  // StringTranslationTrait is retained for backwards compatibility; subclasses
  // may call $this->t(). Internal strings now use TranslatableMarkup directly
  // via NameFormatHelp.
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Markup style for decorating name components.
   *
   * @var string
   */
  protected $markup = 'none';

  /**
   * First separator.
   *
   * @var string
   */
  protected $sep1 = ' ';

  /**
   * Second separator.
   *
   * @var string
   */
  protected $sep2 = ', ';

  /**
   * Third separator.
   *
   * @var string
   */
  protected $sep3 = '';

  /**
   * Used to separate words using the "b" and "B" modifiers.
   *
   * @var string
   */
  protected $boundaryRegExp = '/[\b,\s]/';

  /**
   * Constructs a NameFormatParserService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface|null $config_factory
   *   The config factory, or NULL to use the global container (backward
   *   compatibility for legacy service definitions with no arguments or for
   *   manual construction). Prefer passing this explicitly in new code.
   */
  public function __construct(?ConfigFactoryInterface $config_factory = NULL) {
    // @phpstan-ignore-next-line
    $this->configFactory = $config_factory ?? \Drupal::configFactory();
  }

  /**
   * Parses a name component array into the given format.
   *
   * @param array $name_components
   *   Keyed array of name components.
   * @param string $format
   *   The name format pattern to generate the name.
   * @param array $settings
   *   Additional settings to control the parser.
   *   - sep1 (string): first separator.
   *   - sep2 (string): second separator.
   *   - sep3 (string): third separator.
   *   - markup (string): key of the markup type.
   *
   * @return string
   *   A renderable object representing the name.
   */
  public function parse(array $name_components, string $format = '', array $settings = []): mixed {
    if ($settings === []) {
      $config       = $this->configFactory->get('name.settings');
      $this->sep1   = (string) $config->get('sep1');
      $this->sep2   = (string) $config->get('sep2');
      $this->sep3   = (string) $config->get('sep3');
      $this->markup = 'none';
    }
    else {
      foreach (['sep1', 'sep2', 'sep3'] as $sep_key) {
        if (isset($settings[$sep_key])) {
          $this->{$sep_key} = (string) $settings[$sep_key];
        }
      }
      $this->markup = !empty($settings['markup']) ? $settings['markup'] : 'none';
    }

    if ($format === '') {
      return NameFormatOutput::wrap('', $this->markup);
    }

    $tokens = NameFormatTokens::build(
      $name_components,
      $this->sep1,
      $this->sep2,
      $this->sep3,
      $this->markup,
    );
    $name_string = NameFormatParser::format($format, $tokens);

    return NameFormatOutput::wrap($name_string, $this->markup);
  }

  /**
   * Formats an array of name components into the supplied format.
   *
   * Retained as a protected method for backwards compatibility with
   * subclasses. Delegates to NameFormatParser::format() after generating
   * tokens via generateTokens().
   *
   * @param array $name_components
   *   A keyed array of the components.
   * @param string $format
   *   The name format string or segment to parse.
   * @param array $tokens
   *   The generated tokens.
   *
   * @return string
   *   The formatted string.
   */
  protected function format(array $name_components, $format = '', ?array $tokens = NULL) {
    if (empty($format)) {
      return '';
    }

    $tokens ??= $this->generateTokens($name_components);
    return NameFormatParser::format((string) $format, $tokens);
  }

  /**
   * Adds a component.
   *
   * Retained as a protected method for backwards compatibility with
   * subclasses. The by-reference parameters are preserved so existing
   * override signatures remain valid.
   *
   * @param string $string
   *   The token string to process.
   * @param string $modifiers
   *   The modifiers to apply.
   * @param string $conditions
   *   The conditional flags.
   *
   * @return array
   *   The processed piece.
   */
  protected function addComponent($string, &$modifiers = '', &$conditions = '') {
    $value      = NameFormatParser::applyModifiers($string, $modifiers, $this->boundaryRegExp);
    $piece      = ['value' => $value, 'conditions' => $conditions];
    $conditions = '';
    $modifiers  = '';
    return $piece;
  }

  /**
   * Applies the specified modifiers to the string.
   *
   * Retained as a protected method for backwards compatibility with
   * subclasses. Delegates to NameFormatParser::applyModifiers() using the
   * instance boundary regexp.
   *
   * @param string $string
   *   The token string to process.
   * @param string $modifiers
   *   The modifiers to apply.
   *
   * @return string
   *   The processed string.
   */
  protected function applyModifiers($string, $modifiers) {
    return NameFormatParser::applyModifiers($string, $modifiers, $this->boundaryRegExp);
  }

  /**
   * Helper function to put out the first matched bracket position.
   *
   * Retained as a protected method for backwards compatibility with
   * subclasses. Delegates to NameFormatParser::closingBracketPosition().
   *
   * @param string $string
   *   Accepts strings in the format, ^ marks the matched bracket.
   *
   *   i.e. '(xxx^)xxx(xxxx)xxxx' or '(xxx(xxx(xxxx))xxx^)'.
   *
   * @return mixed
   *   The closing bracket position or FALSE if not found.
   */
  protected function closingBracketPosition($string) {
    return NameFormatParser::closingBracketPosition($string);
  }

  /**
   * Generates the tokens from the name item.
   *
   * Retained as a protected method for backwards compatibility with
   * subclasses. Delegates to NameFormatTokens::build() using the current
   * instance separators and markup mode.
   *
   * @param array $name_components
   *   The array of name components.
   *
   * @return array
   *   The keyed tokens generated for the given name.
   */
  protected function generateTokens(array $name_components) {
    return NameFormatTokens::build(
      $name_components,
      $this->sep1,
      $this->sep2,
      $this->sep3,
      $this->markup,
    );
  }

  /**
   * Finds and renders the first renderable name component value.
   *
   * Retained as a protected method for backwards compatibility with
   * subclasses. Delegates to NameFormatTokens::renderFirstComponent().
   *
   * @param array $values
   *   An array of values to find the first to render.
   * @param string $component_key
   *   The component context.
   * @param string $modifier
   *   Internal flag for processing.
   *
   * @return string|null
   *   The rendered component.
   */
  protected function renderFirstComponent(array $values, $component_key, $modifier = NULL) {
    return NameFormatTokens::renderFirstComponent(
      $values,
      $component_key,
      $this->markup,
      $modifier,
    );
  }

  /**
   * Renders a name component value.
   *
   * Retained as a protected method for backwards compatibility with
   * subclasses. Delegates to NameFormatTokens::renderComponent().
   *
   * @param string $value
   *   A value to render.
   * @param string $component_key
   *   The component context.
   * @param string $modifier
   *   Internal flag for processing.
   *
   * @return string|null
   *   The rendered component.
   */
  protected function renderComponent($value, $component_key, $modifier = NULL) {
    return NameFormatTokens::renderComponent($value, $component_key, $this->markup, $modifier);
  }

  /**
   * Supported markup options.
   *
   * @return array
   *   A keyed array of markup options.
   */
  public function getMarkupOptions(): array {
    return NameFormatHelp::markupOptions();
  }

  /**
   * Supported tokens.
   *
   * @param bool $describe
   *   Appends the description of the letter to the description.
   *
   * @return string[]
   *   An array of strings keyed by the token.
   */
  public function tokenHelp(bool $describe = TRUE): array {
    return $describe ? NameFormatHelp::tokenHelp() : NameFormatHelp::tokenHelpPlain();
  }

  /**
   * Helper function to provide name format token help.
   *
   * @return array
   *   A renderable array of tokens in a details element.
   */
  public function renderableTokenHelp(): array {
    return NameFormatHelp::renderableTokenHelp();
  }

}

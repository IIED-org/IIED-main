<?php

declare(strict_types=1);

namespace Drupal\name\Twig;

use Drupal\name\Utility\NameFormatHelp;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension that exposes format-token help to help topic templates.
 *
 * Registers the name_format_token_help() function, which returns a render
 * array for use with render_var() in help topic Twig files. This follows
 * the same pattern as Drupal core's help_route_link() / help_topic_link()
 * functions provided by \Drupal\help\HelpTwigExtension.
 *
 * @internal
 *   Tagged services are internal.
 */
class NameFormatHelpTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    return [
      new TwigFunction(
        'name_format_token_help',
        [$this, 'getTokenHelp'],
      ),
    ];
  }

  /**
   * Returns a render array for the format token reference.
   *
   * Intended for use in help topic templates via render_var():
   * @code
   * {{ render_var(name_format_token_help()) }}
   * @endcode
   *
   * @return array<string, mixed>
   *   A Drupal render array using the name_format_parameter_help theme.
   */
  public function getTokenHelp(): array {
    return NameFormatHelp::renderableTokenReference();
  }

}

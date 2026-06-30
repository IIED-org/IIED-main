# Classes and Interfaces

This page highlights the classes most developers interact with.

## Field API plugins

- `Drupal\name\Plugin\Field\FieldType\NameItem`
  - field schema and settings for `name` values
- `Drupal\name\Plugin\Field\FieldWidget\NameWidget`
  - form input for each component
- `Drupal\name\Plugin\Field\FieldFormatter\NameFormatter`
  - render-time formatter plugin that delegates to `name.formatter`

## Form and render classes

- `Drupal\name\Element\Name`
  - composite form element (`#type => 'name'`)
- `Drupal\name\Controller\AutocompleteController`
  - endpoint that delegates matching to `name.autocomplete`

## Service interfaces (preferred type hints)

- `Drupal\name\Service\NameFormatterInterface`
- `Drupal\name\Service\NameFormatParserInterface`
- `Drupal\name\Service\GeneratorInterface`
- `Drupal\name\Service\AutocompleteInterface`
- `Drupal\name\Service\NameOptionInterface`
- `Drupal\name\Service\WidgetLayoutInterface`

## Config entity classes

- `Drupal\name\Entity\NameFormat`
- `Drupal\name\Entity\NameListFormat`
- `Drupal\name\Entity\NameFormatInterface`
- `Drupal\name\Entity\NameListFormatInterface`

## Format parsing utilities

These classes are marked `@internal` and implement the format-string pipeline
behind `name.format_parser`. They are documented here for contributors and
developers extending the service.

- `Drupal\name\Utility\NameFormatParser` — static facade over the pipeline.
- `Drupal\name\Utility\NameFormatTokens` — builds the token map from name
  components.
- `Drupal\name\Utility\NameFormatLexer` — walks the format string and resolves
  tokens.
- `Drupal\name\Utility\NameFormatModifiers` — applies casing, initials, and
  word-boundary modifiers.
- `Drupal\name\Utility\NameFormatAssembler` — joins resolved pieces into a
  plain string.
- `Drupal\name\Utility\NameFormatOutput` — wraps the assembled string in the
  appropriate Drupal renderable type.
- `Drupal\name\Utility\NameFormatHelp` — provides translated token labels
  shared by the format edit form and the `name.formats` help topic.

See [Parser Service](../services/parser.md) for the public API over this
pipeline.

## Twig extensions

- `Drupal\name\Twig\NameFormatHelpTwigExtension` — registers
  `name_format_token_help()` for use in help topic templates via
  `render_var()`. Not intended for general theme use.

## Integration classes

- Feeds: `Drupal\name\Feeds\Target\NameTarget`
- Diff: `Drupal\name\Plugin\diff\Field\NameFieldBuilder`
- Views filter: `Drupal\name\Plugin\views\filter\Fulltext`
- Migrate plugin: `Drupal\name\Plugin\migrate\field\NameField`
- Migrate process plugin: `Drupal\name\Plugin\migrate\process\NameField`

## Example: class-based DI

```php-inline
use Drupal\name\Service\NameFormatterInterface;

final class NamePresenter {

  public function __construct(
    private readonly NameFormatterInterface $formatter,
  ) {}
}
```

## Related docs

- [API Overview](../api/overview.md)
- [Services Overview](../services/index.md)
- [Code Examples](../examples/index.md)

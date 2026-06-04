# API Overview

## Architecture summary

The Name module combines three API layers:

1. **Field API plugins** for storage, form input, and display.
2. **Container services** for formatting, parsing, generation, autocomplete,
   and option resolution.
3. **Extension points** such as `hook_name_widget_layouts()` and integration
   plugins for other modules.

## Core data model

Name values are keyed arrays with these component keys:

- `title`
- `given`
- `middle`
- `family`
- `generational`
- `credentials`

Optional extra keys, such as preferred/alternative variants, may be handled by
formatting rules depending on field and formatter settings.

## Plugin layer

- Field type: `Drupal\name\Plugin\Field\FieldType\NameItem`
- Widget: `Drupal\name\Plugin\Field\FieldWidget\NameWidget`
- Formatter plugin: `Drupal\name\Plugin\Field\FieldFormatter\NameFormatter`
- Form element: `Drupal\name\Element\Name`

The formatter plugin delegates formatting logic to the formatter service.

## Service layer

Primary service IDs:

- `name.formatter`
- `name.format_parser`
- `name.generator`
- `name.autocomplete`
- `name.options_provider`

See [Services Overview](../services/index.md) for exact class/interface
mappings.

## Recommended usage pattern

For reusable class-based code, inject interfaces from `Drupal\name\Service`.

```php-inline
use Drupal\name\Service\NameFormatterInterface;

final class ExampleConsumer {

  public function __construct(
    private readonly NameFormatterInterface $formatter,
  ) {}
}
```

Direct `\Drupal::service()` calls are acceptable for procedural or one-off
code, but avoid them in classes you control.

## Configuration entities

- `Drupal\name\Entity\NameFormat` for component display patterns.
- `Drupal\name\Entity\NameListFormat` for list conjunction/delimiter behavior.

## Format parsing architecture

The `name.format_parser` service delegates its rendering pipeline to a set of
stateless utility classes under `Drupal\name\Utility`:

- `NameFormatTokens::build()` — converts a name component array into a token map.
- `NameFormatLexer::tokenize()` — walks the format string and resolves tokens.
- `NameFormatModifiers` — applies casing, initials, and conditional modifiers.
- `NameFormatAssembler::assemble()` — joins resolved pieces into a plain string.
- `NameFormatParser` — static facade over the three steps above.
- `NameFormatOutput::wrap()` — wraps the plain string in the requested markup
  render array.
- `NameFormatHelp` — provides translated token labels shared by the format edit
  form and the `name.formats` help topic.

The `NameFormatParserService` class retains its full public/protected API for
backwards compatibility with subclasses and existing service consumers. See
[Parser Service](../services/parser.md) for usage. See
[Help integration](../integrations/help.md) for the token reference UI.

## Extension points

- Hook: [`hook_name_widget_layouts()`](../hooks/widget-layouts.md)

## Integration with other modules

- [Devel](../integrations/devel.md)
- [User](../integrations/user.md)
- [Taxonomy](../integrations/taxonomy.md)
- [Views](../integrations/views.md)
- [Help](../integrations/help.md)
- [Token](../integrations/token.md)
- [Feeds](../integrations/feeds.md)
- [Diff](../integrations/diff.md)
- [Migrate](../integrations/migrate.md)

## Legacy procedural helpers

Legacy helper functions in `name.module` are retained mainly for compatibility.
New code should use the service layer and utility classes instead.

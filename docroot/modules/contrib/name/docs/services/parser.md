# Name Format Parser Service

## Service identity

- Service ID: `name.format_parser`
- Interface: `Drupal\name\Service\NameFormatParserInterface`
- Class: `Drupal\name\Service\NameFormatParserService`

This service converts name component arrays into formatted, markup-aware output.
Most custom code should call `name.formatter` and let it delegate to the parser
internally. Use the parser directly only when you need to bypass formatter
settings or work with format patterns in isolation.

## Parsing pipeline

`NameFormatParserService::parse()` delegates to a set of stateless utility
classes under `Drupal\name\Utility`:

1. `NameFormatTokens::build()` — converts name components and separator/markup
   settings into a token map keyed by single-character format letters.
2. `NameFormatLexer::tokenize()` — walks the format string and resolves each
   token using the map, applying conditions along the way.
3. `NameFormatModifiers` — handles casing (`L`, `U`, `F`, `G`), initials
   (`I`, `J`, `K`, `M`), and word-boundary modifiers (`B`, `b`).
4. `NameFormatAssembler::assemble()` — joins resolved pieces into a plain
   string.
5. `NameFormatParser` — static facade that chains steps 2–4 in one call.
6. `NameFormatOutput::wrap()` — wraps the assembled string in the
   markup-appropriate Drupal renderable type (`HtmlEscapedText`, `Markup`, or
   `FormattableMarkup`).

All utility classes are marked `@internal`. Interact with the pipeline through
`NameFormatParserService` or the static `NameFormatParser` facade.

## Direct usage

`parse()` returns a renderable value (a `MarkupInterface` object or a plain
string for `markup: none`), not a raw PHP string. Pass the return value directly
to a render context or cast to string only when you know the output is safe.

```php-inline
$parser = \Drupal::service('name.format_parser');

$output = $parser->parse(
  ['given' => 'Jane', 'family' => 'Smith'],
  'g f',
  ['sep1' => ' ', 'markup' => 'none'],
);
// $output is an HtmlEscapedText renderable for 'Jane Smith'.
```

## Primary method

### `parse(array $name_components, string $format = '', array $settings = []): mixed`

Applies the full pipeline and returns a renderable value.

- `$name_components` — keyed array of name parts (see [API Overview](../api/overview.md#core-data-model)).
- `$format` — format pattern string (e.g. `g f`, `t g f`).
- `$settings` — optional overrides:
  - `sep1` (string): first separator (token `i`).
  - `sep2` (string): second separator (token `j`).
  - `sep3` (string): third separator (token `k`).
  - `markup` (string): `none` (default), `simple`, `microdata`, `rdfa`, `raw`.

When `$settings` is empty, the service reads `sep1/sep2/sep3` from
`name.settings` config.

## Format tokens and modifiers

The full token reference — component tokens (`t`, `g`, `m`, `f`, `s`, `c`),
preferred/alternative tokens, initials, modifiers, and conditional operators —
is defined in `NameFormatHelp::tokenHelp()` (translated labels) and is
surfaced at the `name.formats` help topic
(`/admin/help/topic/name.formats`). See also the collapsible "Format string
help" widget on any name format edit form, which renders the same data via
`NameFormatHelp::renderableTokenHelp()`.

## Extending via subclass

`NameFormatParserService` still exposes protected `format()`, `addComponent()`,
and related methods for backwards-compatible subclassing. These methods
delegate to the utility pipeline. New code should call `NameFormatParser`,
`NameFormatTokens`, and `NameFormatHelp` directly rather than overriding
the service.

## When to use parser directly

- Building custom format tooling outside the formatter service.
- Testing format-pattern behavior in isolation.
- Implementing custom format preview or UI.

For application output, prefer [Formatter Service](formatter.md), which
handles format-entity lookup, list formatting, and language negotiation.

## Related docs

- [Formatter Service](formatter.md)
- [Services Overview](index.md)
- [Classes and Interfaces](../classes/index.md)
- [Help integration](../integrations/help.md)

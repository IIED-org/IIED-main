# Name Format Parser Service

## Service identity

- Service ID: `name.format_parser`
- Interface: `Drupal\name\Service\NameFormatParserInterface`
- Class: `Drupal\name\Service\NameFormatParserService`

This service parses format patterns into rendered name strings. Most custom code
should call `name.formatter` and let it delegate to the parser internally.

## Direct usage

```php-inline
$parser = \Drupal::service('name.format_parser');

$result = $parser->parse(
  ['given' => 'Jane', 'family' => 'Smith'],
  'g f',
  ['sep1' => ' ', 'markup' => 'none'],
);
```

## Primary method

### `parse(array $name_components, $format = '', array $settings = [])`

- Applies format tokens (`t`, `g`, `m`, `f`, `s`, `c`)
- Applies token modifiers (for casing, initials, preferred/alternative values)
- Handles separators and markup options

## When to use parser directly

- Building custom format tooling
- Testing format-pattern behavior in isolation
- Implementing custom format UIs

For output in application code, prefer [Formatter Service](formatter.md).

## Related docs

- [Formatter Service](formatter.md)
- [Services Overview](index.md)

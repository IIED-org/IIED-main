# Name Options Provider Service

## Service identity

- Service ID: `name.options_provider`
- Interface: `Drupal\name\Service\NameOptionInterface`
- Class: `Drupal\name\Service\NameOptionService`

Use this service to resolve option lists for components such as `title` and
`generational`.

## Quick use

```php-inline
$optionsProvider = \Drupal::service('name.options_provider');
$titles = $optionsProvider->getOptions($fieldDefinition, 'title');
```

## Primary method

### `getOptions(FieldDefinitionInterface $field, $component)`

Returns a normalized options array for a component:

- reads field-level options (`{component}_options`)
- expands taxonomy references such as `[vocabulary:professional_titles]`
- removes duplicates
- enforces max-length constraints
- normalizes `--` placeholder lines to the `_none` empty option (see below)

## Empty option placeholders (`--`)

A select list for `title` or `generational` can display a prompt (e.g.
"Please select a Title") instead of defaulting to the first real value.
You configure this by prefixing one line in the option list with `--`.

**Field settings convention:** In `title_options` or `generational_options`,
enter one value per line. Prefix a line with `--` to mark it as the empty
prompt text. The field settings textarea documents this convention inline.

```php-inline
// Field settings title_options lines:
// --Please select a Title
// Dr.
// Mr.

$options = $optionsProvider->getOptions($fieldDefinition, 'title');
// Result:
// [
//   '_none' => 'Please select a Title',
//   'Dr.'   => 'Dr.',
//   'Mr.'   => 'Mr.',
// ]
```

`getOptions()` strips the `--` prefix, trims the remainder, and prepends
`['_none' => $label]` to the returned array. If multiple `--` lines are
present, the last one wins (each iteration overwrites the placeholder label).

**Form rendering:** The `Name` element (`Drupal\name\Element\Name`) reads
the `_none` key and maps it to Drupal's select element properties
`#empty_value` (set to `'_none'`) and `#empty_option` (set to the trimmed
label). The placeholder label never appears as a real `#options` entry.

When the user submits without selecting a value, the widget stores `'_none'`
and `NameWidget` normalizes it to an empty string on load.

**Custom `#components` forms:** When you build select options outside the
field widget, pass either a pre-normalized `_none` key or `--`-prefixed
labels in the raw array. Do not include the placeholder text as a real
option key. See the [Widget example](../examples/widget.md) for the
`#components` `options` property.

## Injection example

```php-inline
use Drupal\name\Service\NameOptionInterface;

final class NameOptionConsumer {

  public function __construct(
    private readonly NameOptionInterface $options,
  ) {}
}
```

## Related docs

- [Autocomplete Service](autocomplete.md)
- [Services Overview](index.md)
- [Widget example — per-component properties](../examples/widget.md)
- [Taxonomy integration](../integrations/taxonomy.md)

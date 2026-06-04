# Name Formatter Examples

Use these examples when you need to control how Name field values render in
entity displays or custom code.

## Formatter settings reference

The Name field formatter (`name_default`) supports:

- `format`: Name format machine name (for example, `default`, `full`).
- `list_format`: Name list format machine name, or empty for per-item output.
- `markup`: `none`, `raw`, `simple`, `microdata`, or `rdfa`.
- `link_target`: empty, `_self`, or a link/entity-reference field name.
- `preferred_field_reference`: optional source for preferred component tokens.
- `preferred_field_reference_separator`: separator for multi-value preferred data.
- `alternative_field_reference`: optional source for alternative component tokens.
- `alternative_field_reference_separator`: separator for multi-value alternative
  data.

## Configure formatter on an entity view display

```php-inline
use Drupal\Core\Entity\Entity\EntityViewDisplay;

$display = EntityViewDisplay::load('node.article.default');
if ($display) {
  $display->setComponent('field_author_name', [
    'type' => 'name_default',
    'label' => 'above',
    'settings' => [
      'format' => 'full',
      'list_format' => '',
      'markup' => 'simple',
      'link_target' => '_self',
      'preferred_field_reference' => '',
      'preferred_field_reference_separator' => ', ',
      'alternative_field_reference' => '',
      'alternative_field_reference_separator' => ', ',
    ],
  ])->save();
}
```

## Format one name with the formatter service

```php-inline
$formatter = \Drupal::service('name.formatter');
$formatter->setSetting('markup', 'simple');

$value = [
  'title' => 'Dr.',
  'given' => 'Jane',
  'family' => 'Smith',
];

$output = $formatter->format($value, 'full');
```

## Format multiple names as a list

```php-inline
$formatter = \Drupal::service('name.formatter');
$formatter->setSetting('markup', 'none');

$authors = [
  ['given' => 'John', 'family' => 'Doe'],
  ['given' => 'Jane', 'family' => 'Smith'],
  ['given' => 'Alex', 'family' => 'Brown'],
];

$output = $formatter->formatList($authors, 'full', 'default');
```

## Link targets in formatter output

- `link_target: _self` links the formatted name to the parent entity URL.
- If `link_target` matches a link field, the formatter uses that URL.
- If `link_target` matches an entity reference field, the formatter links to the
  first viewable referenced entity.

## Related docs

- [Code Examples](index.md)
- [Formatter Service](../services/formatter.md)
- [Parser Service](../services/parser.md)

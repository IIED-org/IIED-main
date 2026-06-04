# Name Widget Examples

Use these examples when you are building a custom form and want to reuse the
Name element UI (`#type => 'name'`) outside of normal entity form displays.

## Full widget element example

```php-inline
$form['contact_name'] = [
  '#type' => 'name',
  '#title' => $this->t('Contact name'),
  '#required' => TRUE,
  '#default_value' => [
    'title' => '',
    'given' => 'Jane',
    'middle' => '',
    'family' => 'Smith',
    'generational' => '',
    'credentials' => '',
  ],
  '#minimum_components' => [
    'given' => 'given',
    'family' => 'family',
  ],
  '#allow_family_or_given' => FALSE,
  '#widget_layout' => 'stacked',
  '#wrapper_type' => 'fieldset',
  '#component_layout' => 'default',
  '#show_component_required_marker' => TRUE,
  '#flag_required_input' => TRUE,
  '#credentials_inline' => FALSE,
  '#open' => FALSE,
  '#summary_attributes' => [],
  '#components' => [
    'title' => [
      'type' => 'select',
      'title' => $this->t('Title'),
      'title_display' => 'description',
      'size' => 6,
      'maxlength' => 31,
      'options' => [
        '-- Select --',
        'Mr.',
        'Mrs.',
        'Ms.',
        'Dr.',
      ],
    ],
    'given' => [
      'type' => 'textfield',
      'title' => $this->t('Given'),
      'title_display' => 'title',
      'size' => 20,
      'maxlength' => 63,
    ],
    'middle' => [
      'type' => 'textfield',
      'title' => $this->t('Middle'),
      'title_display' => 'description',
      'size' => 20,
      'maxlength' => 127,
    ],
    'family' => [
      'type' => 'textfield',
      'title' => $this->t('Family'),
      'title_display' => 'title',
      'size' => 20,
      'maxlength' => 63,
    ],
    'generational' => [
      'type' => 'select',
      'title' => $this->t('Generational'),
      'title_display' => 'description',
      'size' => 5,
      'maxlength' => 15,
      'options' => [
        '-- Select --',
        'Jr.',
        'Sr.',
      ],
    ],
    'credentials' => [
      'type' => 'textfield',
      'title' => $this->t('Credentials'),
      'title_display' => 'placeholder',
      'size' => 35,
      'maxlength' => 255,
    ],
  ],
];
```

## Top-level properties

- `#components`: per-component UI definitions.
- `#minimum_components`: which components count as required for a complete name.
- `#allow_family_or_given`: treat one of given/family as satisfying both minimums.
- `#widget_layout`: built-in values include `stacked` and `inline`.
- `#wrapper_type`: `fieldset`, `details`, or `container`.
- `#component_layout`: `default`, `asian`, `eastern`, or `german`.
- `#show_component_required_marker`: add required marker to required component labels.
- `#flag_required_input`: mark required component inputs as required.
- `#credentials_inline`: keep credentials inline instead of forcing a break.
- `#open`: applies when `#wrapper_type` is `details`.

## Per-component properties

Each key in `#components` (`title`, `given`, `middle`, `family`,
`generational`, `credentials`) can define:

- `type`: `textfield` or `select`.
- `title`: label string.
- `title_display`: `title`, `description`, `placeholder`, `attribute`, or `none`.
- `size`: HTML input size.
- `maxlength`: max allowed length.
- `options`: required for `select`. When resolved via `name.options_provider`,
  the array may contain a `_none` key for the empty prompt option; raw arrays
  may use `--`-prefixed labels instead. See
  [Options Provider service](../services/options-provider.md#empty-option-placeholders).
- `autocomplete`: route settings for autocomplete.
- `attributes`: additional input attributes.
- `exclude`: hide a component.

## Built-in autocomplete example

The built-in route (`name.autocomplete`) is useful when your custom form points
at an existing Name field definition.

```php-inline
$form['contact_name']['#components']['credentials']['autocomplete'] = [
  '#autocomplete_route_name' => 'name.autocomplete',
  '#autocomplete_route_parameters' => [
    'field_name' => 'field_author_name',
    'entity_type' => 'node',
    'bundle' => 'article',
    'component' => 'credentials',
  ],
];
```

Notes:

- Route params must identify a real Name field.
- Suggestions are controlled by that field's autocomplete settings.
- For `credentials`, include the `data` source in field settings to suggest
  stored credentials values.

## Custom autocomplete source example

When you need suggestions from another source, use your own route.

```php-inline
$form['contact_name']['#components']['title']['autocomplete'] = [
  '#autocomplete_route_name' => 'my_module.name_title_autocomplete',
];
```

Then return JSON results from your controller in core autocomplete format:

```php-inline
[
  ['value' => 'Dr.', 'label' => 'Dr.'],
  ['value' => 'Prof.', 'label' => 'Prof.'],
]
```

## Related docs

- [Code Examples](index.md)
- [Autocomplete Service](../services/autocomplete.md)
- [Widget Layout Hook](../hooks/widget-layouts.md)

# Extending Name Module

This guide covers common extension points without duplicating full API
reference pages.

## 1) Add custom widget layouts

Use [`hook_name_widget_layouts()`](../hooks/widget-layouts.md) to register new
layout definitions.

```php-inline
function my_module_name_widget_layouts(): array {
  return [
    'compact_inline' => [
      'label' => t('Compact inline'),
      'library' => ['my_module/name-compact-inline'],
      'wrapper_attributes' => [
        'class' => ['name-compact-inline'],
      ],
    ],
  ];
}
```

Define the attached library in `my_module.libraries.yml` and keep styling in
CSS.

## 2) Add format and list-format entities

Create or update `NameFormat` and `NameListFormat` config entities in install
hooks or update hooks.

```php-inline
use Drupal\name\Entity\NameFormat;

if (!NameFormat::load('business_card')) {
  NameFormat::create([
    'id' => 'business_card',
    'label' => 'Business Card',
    'pattern' => 't g f, c',
  ])->save();
}
```

For list behavior, define a `NameListFormat` with delimiter and conjunction
settings.

## 3) Decorate formatter behavior (advanced)

For cross-project formatting rules, decorate `name.formatter` in your
module service definition.

```yaml
services:
  my_module.name_formatter:
    class: Drupal\my_module\CustomNameFormatter
    decorates: name.formatter
    arguments: ['@my_module.name_formatter.inner']
```

Decorator classes should type-hint
`Drupal\name\Service\NameFormatterInterface`.

## 4) Extend options/autocomplete sources

When you need custom title or generational values, configure field options or
taxonomy-backed sources, then let:

- `name.options_provider` resolve options (custom `title_options` and
  `generational_options` lines may include `--` prefixes to define an empty
  prompt; see
  [Options Provider service](../services/options-provider.md#empty-option-placeholders))
- `name.autocomplete` consume those sources for matching

## Keep extensions maintainable

- Prefer service injection over global service lookups.
- Keep widget/layout concerns in hook + library assets.
- Keep formatting rules in formatter service usage or decorators.
- Add automated tests for custom integrations.

## Related docs

- [Hooks: widget layouts](../hooks/widget-layouts.md)
- [Services Overview](../services/index.md)
- [Classes and Interfaces](../classes/index.md)

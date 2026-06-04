# Migrate integration

Name provides Migrate plugins for both Drupal field migration mapping and
string-to-components parsing.

## Requirements

- Name module.
- Migrate ecosystem modules for your migration flow (for example
  `migrate` and `migrate_drupal`).

## What Name provides

### 1) Migrate field plugin

- Class: `Drupal\name\Plugin\migrate\field\NameField`
- Plugin ID: `name`

This plugin maps Drupal 7 Name source values to destination Name components
(`title`, `given`, `middle`, `family`, `generational`, `credentials`) through
an iterator process map.

### 2) Migrate process plugin

- Class: `Drupal\name\Plugin\migrate\process\NameField`
- Plugin ID: `name_field`

This plugin parses a text name string into structured Name components. It can
use explicit plugin configuration or destination field settings
(`title_options`, `generational_options`) to identify values.

## Example process usage

```yaml
process:
  field_name:
    plugin: name_field
    source: full_name
    entity_type: node
    bundle: article
    field_name: field_name
```

Use explicit `title`, `generational`, or `credentials` arrays in plugin
configuration when you need parser behavior that differs from field settings.

## Site builder and migration notes

- The migrate field plugin is aimed at Drupal field migration mapping.
- The process plugin is useful when your source stores names as one text value.
- For single-word input, the parser treats the value as `family`.

## See also

- [API Overview](../api/overview.md#integration-with-other-modules)
- [Classes and Interfaces](../classes/index.md)

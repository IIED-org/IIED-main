# Views integration

The Name module exposes name fields to Views with component-level fields and a
fulltext filter tailored to multi-part names.

## Requirements

- Name module.
- Views module.

## What Name provides

Name adds Views data for each Name field via `hook_field_views_data()`.

For each Name field, it provides:

- A fulltext filter on the base Name field using plugin ID `name_fulltext`.
- Individual subfields for each component (`title`, `given`, `middle`,
  `family`, `generational`, `credentials`).

## Fulltext filter behavior

The fulltext filter plugin class is:

- `Drupal\name\Plugin\views\filter\Fulltext`

Supported operators:

- Contains
- Contains any word
- Contains all words

The filter searches across all six core components as one normalized string.

## Site builder usage

In a View display:

1. Add your Name field or individual component fields.
2. Add the Name fulltext filter for cross-component matching.
3. Use component fields when you need separate columns/sorting behavior.

## See also

- [API Overview](../api/overview.md#integration-with-other-modules)
- [Classes and Interfaces](../classes/index.md)

# Diff integration

The [Diff](https://www.drupal.org/project/diff) module compares revisions of content. Name fields get a dedicated field diff builder so comparisons read clearly instead of as raw field arrays.

## Requirements

Enable the **Name** and **Diff** modules. Diff is not a hard dependency of Name; install it when you need revision diffs for name fields.

## What Name provides

**Class:** `Drupal\name\Plugin\diff\Field\NameFieldBuilder`  
**Plugin ID:** `name_field_diff_builder` (annotation: `@FieldDiffBuilder`)

For each delta, the builder outputs either:

1. **Name format** — Text produced by the name formatter using a format you select (same option set as site name formats, with an empty “-- components --” choice).
2. **Components** — One line per active component: `Label: value`, joined with newlines.

Configure this under Diff’s field-level settings for the name field (compare “Name format” vs “-- components --”).

## Site builder notes

- Choose a **Name format** when you want diffs to match how names appear elsewhere (e.g. “Formal” vs “Given family”).
- Use **-- components --** when you want a side-by-side breakdown by title, given name, family name, etc.

## See also

- [Integration overview](../api/overview.md#integration-with-other-modules)
- [Classes reference — Integration classes](../classes/index.md#integration-classes)

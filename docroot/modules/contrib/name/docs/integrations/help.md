# Help integration

The Name module integrates with Drupal core Help in two places:

- A module help page entry via `hook_help()`.
- A contextual link to a Name help topic from field settings UI.

## Requirements

- Name module.
- Drupal core Help module for the help topic route and UI.

Name works without Help, but Help enables the linked documentation experience.

## What Name provides

### Module help page

`name_help()` handles the `help.page.name` route and returns introductory text
about the Name field and its structured components.

### Field settings help-topic link

In Name field settings, when Help is enabled, Name appends a link to the
`titles` help topic (`/admin/help/topic/name.titles`) in the title options
description.

This link is added conditionally only when the Help module exists.

## Site builder notes

- Use **Help > Name** for a quick module overview.
- In Name field settings, use the linked **titles** topic for guidance on title
  option values and conventions.

## See also

- [API Overview](../api/overview.md#integration-with-other-modules)
- [Taxonomy integration](./taxonomy.md)

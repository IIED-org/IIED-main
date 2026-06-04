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

### Name formats help topic

The **Name formats** help topic (`/admin/help/topic/name.formats`) includes the
full format-token reference — the same token descriptions, with case hints, that
appear in the collapsible "Format string help" widget on the name format edit
form.

This is rendered via `NameFormatHelpTwigExtension`, which registers a
`name_format_token_help()` Twig function. The function returns a render array
powered by `NameFormatHelp::renderableTokenReference()` and the
`name_format_parameter_help` theme. Help topic templates call it with
`render_var()`:

```twig
{{ render_var(name_format_token_help()) }}
```

Token labels are defined once in `NameFormatHelp::tokenHelp()` and shared
between the help topic and the collapsible "Format string help" widget on the
name format edit form (via `NameFormatHelp::renderableTokenHelp()`). Both
surfaces stay in sync automatically.

## Site builder notes

- Use **Help > Name** for a quick module overview.
- In Name field settings, use the linked **titles** topic for guidance on title
  option values and conventions.
- On the **Name formats** help topic, the complete token reference is included
  inline — no need to open a format form to look up token letters.

## See also

- [API Overview](../api/overview.md#integration-with-other-modules)
- [Taxonomy integration](./taxonomy.md)

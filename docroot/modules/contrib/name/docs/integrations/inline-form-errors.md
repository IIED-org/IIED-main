# Inline Form Errors integration

The Name module integrates with Drupal core `inline_form_errors` so validation
messages are shown next to the specific missing Name components.

## Requirements

- Name module.
- Drupal core `inline_form_errors` module.

Name works without `inline_form_errors`, but the error presentation differs.

## Behavior

Name validation runs through the `name.element_validator` service.

When `inline_form_errors` is enabled:

- Missing minimum components on partially filled Name values get
  component-local messages.
- Required empty Name values also get component-local messages.

When `inline_form_errors` is disabled:

- Partial values keep aggregate behavior (one combined message plus child
  element errors).
- Empty required values use the parent field required message.

# `hook_name_widget_layouts()`

Use this hook to provide additional Name widget layouts.

## What a widget layout does

A widget layout controls how Name component inputs are wrapped and presented in
entity edit forms. It does not change how a name is stored or formatted for
display output; it only affects input UI.

Each layout can:

- Provide a label shown in widget settings.
- Attach one or more asset libraries (CSS/JS).
- Add wrapper attributes (such as classes or data attributes) around the Name
  component group.

The Name module ships with built-in options such as `stacked` and `inline`.
This hook lets other modules add more layout options.

## Why create a custom widget layout?

Custom layouts are useful when your editorial UX needs differ from the default
stacked/inline presentation. Common reasons include:

- Matching a design system that requires specific spacing, grid behavior, or
  utility classes.
- Optimizing data entry for high-volume workflows where fewer line breaks and
  clearer grouping reduce input time.
- Improving mobile ergonomics with breakpoint-aware styling for narrow screens.
- Supporting locale-specific expectations for field grouping and reading order.
- Adding JavaScript behaviors tied to wrapper selectors for progressive
  enhancement.

## Hook contract

```php-inline
/**
 * Implements hook_name_widget_layouts().
 */
function my_module_name_widget_layouts(): array {
  return [
    'inline' => [
      'label' => t('Inline'),
      'library' => ['my_module/name-inline'],
      'wrapper_attributes' => [
        'class' => ['name-widget-inline'],
      ],
    ],
  ];
}
```

## Layout definition keys

- `label` (required): human-readable layout label.
- `library` (optional): attached asset libraries.
- `wrapper_attributes` (optional): wrapper element attributes.

## Service relationship

Layouts are discovered and cached via the `name.widget_layouts` service
(`Drupal\name\Service\WidgetLayoutService`). Keep using the hook as the
extension point; the service handles discovery and caching internally.

## Practical tips

- Keep layout machine names stable once deployed.
- Put all CSS/JS in libraries, not inline markup.
- Test on narrow screens and with RTL content.

## Related docs

- [Extending](../extending/index.md)
- [Services Overview](../services/index.md)

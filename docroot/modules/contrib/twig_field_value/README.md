# Twig Field Value

Twig Field Value allows Drupal themers to print field labels and field values individually. It provides two Twig filters, one that print a field label and one that prints field value(s).

Filters:

- `field_label`: Returns the field label value.
- `field_value`: Returns the render array of the field value(s) without
  the field wrappers.
- `field_raw`: Returns raw field properties value(s).
- `field_target_entity`: Returns the referenced entity object(s) of an entity
  reference field.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/twig_field_value).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/twig_field_value).

## Table of contents

- Requirements
- Installation
- Configuration
- Usage
- Important caching notice
- Known restrictions
- Maintainers

## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module has no menu or modifiable settings. There is no configuration.


## Usage

To print the label and value of a field:
```twig:
<strong>{{ content.field_name|field_label }}</strong>:
{{ content.field_name|field_value }}
```

To print the label and values of a field with multiple values:
```twig:
<strong>{{ content.field_name|field_label }}</strong>:
{{ content.field_name|field_value|safe_join(', ') }}
```

To print image link and the alt text of an image:
```twig:
<img src={{ file_url(content.field_image|field_target_entity.uri.value) }}
alt={{ content.field_image|field_raw('alt') }}>
```
To print content of multiple referenced items:
```twig:
<ul>
{% if content.field_tags.1 %}
  {% for item in content.field_tags|field_target_entity %}
    <li>{{ item.id }}</li>
  {% endfor %}
{% else %}
  <li>{{ item.id }}</li>
{% endif %}
</ul>
```

The above examples assume that `content.field_example` is the render array of
the of a field, as for example in a node template.


## Important caching notice

When you print data of referenced entities, the cache data of that entity is
ignored. The cache will not be invalidated when the referenced entity changes.

To compensate, render the field without printing the output. The rendering makes
sure that the cache metadata is captured and applied to the output.

```twig:
{{ content.field_referenced_entity|field_target_entity.label }}
{% set dummy = content.field_referenced_entity|render %}
```


## Known restrictions

The `field_raw` twig filter does not support access control at field item level
for entity reference fields. The render array allows access control to
individual field items by using `#access = FALSE`. But the filter can not apply
this restriction to individual referenced entities.

The `field_target_entity` twig filter does not support access control at field
item level. The render array allows access control to individual field items
by using `#access = FALSE`. But the `field_target_entity` filter can not apply
this restriction to individual referenced entities.


## Maintainers

- Jouke van Leeuwen - [bizar1984](https://www.drupal.org/u/bizar1984)
- Gerben Spil - [gerben-spil](https://www.drupal.org/u/gerben-spil)
- Erik Stielstra - [sutharsan](https://www.drupal.org/u/sutharsan)
- Joshua Sedler - [grevil] (https://www.drupal.org/u/grevil)
- Julian Pustkuchen - [anybody] (https://www.drupal.org/u/anybody)
- Thomas Frobieter - thomas.frobieter (https://www.drupal.org/u/thomasfrobieter)

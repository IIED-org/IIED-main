# DISPLAY SUITE

Display Suite gives you full control over the way content is displayed without
having to maintain dozens of twig files.
[Read more](https://drupal.org/node/644662)

## Getting started

1. Install Display Suite in the usual way [Install Modules](https://www.drupal.org/docs/extending-drupal/installing-modules)
2. Go to Administration > Structure > Display Suite
   (admin/structure/ds/)
3. Click "Manage display" for the entity (e.g., "User") whose display you like
   to change
4. In the vertical tab "Layout for ... in default" choose the desired layout
   template (e.g. "Two column stacked") and click "Apply"
5. Start managing the display by dragging fields to regions
6. Click "Save"
[Read more](https://drupal.org/node/1795282)

## BC settings

- When setting the default field template to e.g. minimal, but only moving a
  field to a region, the default core field template is used, and suggestions
  would be wrong. As soon as you would save the formatter settings, things
  would act normal. This is fixed, but with a BC layer which is set to TRUE
  when you upgrade. New installations can safely ignore this setting, which
  defaults to FALSE then.
  See https://www.drupal.org/project/ds/issues/2865218
- Default field templates have changed to match the classes with the core field
  template and fix leaking of classes in minimal and reset. When upgrading, an
  update hook will toggle a BC setting to use the original templates which are
  available in templates/bc. Fresh installs will use the new version, but you
  can always configure to use the previous templates if you want at
  /admin/structure/ds/settings.
  See https://www.drupal.org/project/ds/issues/3198320 and
  https://www.drupal.org/project/ds/issues/3313688
- Layout suggestions where using the id of the layout but this caused problems
  for some templates. They are now using the theme hook value. This is
  fixed, but with a BC layer which is set to TRUE when you upgrade. New
  installations can safely ignore this setting, which defaults to FALSE then.
  See https://www.drupal.org/project/ds/issues/2887778
- The layouts shipped in Display Suite now also have the icon_map key. You can
  configure to use this option to preview the layout instead of the original
  icons Display Suite ships with. On fresh installs, the icon maps are used.
  This can be configured at /admin/structure/ds/settings.

## Known issues

- When creating custom Display Suite layouts, do not add a 'content' region as
  this region will fail to render.
- Drag and drop sometimes acts weird, especially in combination with Field
  Group. This is most likely a core bug, which is being tracked in
  https://www.drupal.org/project/ds/issues/3087612
- Some settings can't be easily translated on the manage display page, like
  suffix, prefix, label and so on. This is related to the fact that
  configuration translation in Drupal Core does not properly support this yet.
  One way to overcome this, is by overriding the template files and adding
  |trans in the right places.
  See https://www.drupal.org/project/ds/issues/3011528 for more information.
- Some modules implement hook_preprocess_node, e.g. Gutenberg. In that case
  some libraries might be missing. This can be fixed by implementing custom
  code to call the libraries when a DS layout is used for a content type. An
  example can be found at https://www.drupal.org/project/ds/issues/3170429.
  In general, layouts have no idea about the entity rendered, so
  hook_preprocess_hook functions are not called. But since Drupal 9.5, the
  #entity is available, so you could call original preprocess functions so that
  some variables are available again (e.g display_submitted for node).
  See https://www.drupal.org/node/3278487 for more information.
- Contact form manage display saving but not rendering: the key here is to
  install the contact storage module.
  See https://www.drupal.org/project/ds/issues/2832259#comment-15397394

## Links

- [Project page](https://drupal.org/project/ds)
- [Submit bug reports, feature suggestions](https://drupal.org/project/issues/ds)

## Maintainers

- aspilicious - https://drupal.org/u/aspilicious
- swentel - https://drupal.org/u/swentel
- bceyssens - https://www.drupal.org/u/bceyssens

# Components!

The Components module makes it easier for a theme to organize its components. It
allows themes (and modules) to register Twig namespaces and provides some
additional Twig functions and filters for use in Drupal templates.

The Components module allows you to:

- specify a different directory for you component library's Twig files
- allows you to register a unique Twig namespace for those files

For more information (including documentation, bug reports, etc.), visit:
- [Components documentation](https://www.drupal.org/docs/contributed-modules/components)
- [Components project page](https://www.drupal.org/project/components)

## Requirements

This module requires Drupal 10 or Drupal 11.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

Theme and module developers can configure Twig namespaces in their .info.yml
file.

Here's how you would register a "@fusion" Twig namespace where those files are
stored in your theme's "components/fusion" folder:

```yaml
components:
 namespaces:
    fusion:
      - components/fusion
```

For more detailed information, see the ["Registering Twig namespaces" page of
the Components documentation](https://www.drupal.org/docs/contributed-modules/components/registering-twig-namespaces).

## Maintainers

- John Albin Wilkins - [JohnAlbin](https://www.drupal.org/u/johnalbin)
- Rob Loach - [RobLoach](https://www.drupal.org/u/robloach)

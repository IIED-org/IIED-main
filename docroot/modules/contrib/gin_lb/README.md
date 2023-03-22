# Gin Layout Builder

The Gin Layout Builder module brings the gin admin theme to the layout builder.

For a full description of the module, visit the
[Gin Layout Builder](https://www.drupal.org/project/gin_lb).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/gin_lb).


### Conflicts with your frontend theme.

To avoid conflicts with your frontend theme, the module adds a
CSS prefix "glb-" to all layout builder styles.
If your theme uses theme suggestions there could be conflicts
with the module theme suggestions from "gin layout builder".
To avoid these conflicts add the following code to your
hook_theme_suggestions inside your theme.

if (isset($variables['element']['#gin_lb_form'])) {
    return;
}

### Develop

Gin layout builder comes with a webpack configuration to builder SCSS styles.
To use them run:

`yarn install`
`yarn dev`

For a full description of the module, visit the
[project page](https://www.drupal.org/project/gin_lb).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/gin_lb).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires the following modules other than core module:

- [Gin Toolbar](https://www.drupal.org/project/gin_toolbar)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module has no menu or modifiable settings. There is no configuration at the
module level.


## Maintainers

- Christian.wiedemann - [Christian.wiedemann](https://www.drupal.org/u/christianwiedemann)
- Rafael Schally - [sch4lly](https://www.drupal.org/u/sch4lly)
- Stangl David - [Duwid](https://www.drupal.org/u/duwid)

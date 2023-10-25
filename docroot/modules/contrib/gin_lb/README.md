# Gin Layout Builder

## Overview

The Gin Layout Builder module brings the Gin admin theme to the layout builder.

### Conflicts with your frontend theme.

To avoid conflicts with your frontend theme, the module adds a CSS prefix "glb-"
to all layout builder styles.

If your theme uses theme suggestions there could be conflicts with the module
theme suggestions from "gin layout builder".

To avoid these conflicts add the following code to your
hook_theme_suggestions inside your theme.

```php
if (isset($variables['element']['#gin_lb_form'])) {
  return;
}
```

## Requirements

This module requires the following module:

* [Gin Toolbar](https://www.drupal.org/project/gin_toolbar)

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

* Enable the Gin Layout Builder module on your site.
* Go to the configuration page (`/admin/config/gin_lb/settings`) and choose your
  module settings.

## Maintainers

Current maintainers:
* [Christian Wiedemann (Christian.wiedemann)](https://www.drupal.org/user/861002)
* [Rafael Schally (sch4lly)](https://www.drupal.org/user/856550)
* [Stangl David (Duwid)](https://www.drupal.org/user/2693877)
* [Florent Torregrosa (Grimreaper)](https://www.drupal.org/user/2388214)

This project has been sponsored by:
* [keytec GmbH & Co. KG](https://www.keytec.de/)
* [Smile](https://https://www.smile.eu)

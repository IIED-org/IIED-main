# Matomo Analytics

Adds the Matomo tracking system to your website.

The module allows you to add the following statistics features to your site:
* Single/multi domain tracking
* Selectively track/exclude certain users, roles and pages
* Monitor what type of links are tracked (downloads, outgoing and mailto)
* Monitor what files are downloaded from your pages
* Cache the Matomo code on your local server for improved page loading times
* Custom code snippets
* Site Search
* Drupal messages tracking
* Modal dialog tracking (Colorbox)
* Access denied (403) and Page not found (404) tracking
* User ID tracking across devices
* DoNotTrack support
* Asynchronous tracking

For a full description of the module, visit the
[project page](https://www.drupal.org/project/matomo).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/matomo).


## Requirements

This module requires no modules outside of Drupal core.

- a Matomo 3.3.0+ installation
- a Matomo website ID


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. In the settings page (/admin/config/system/matomo), enter your Matomo website
   ID.

2. All pages will now have the required JavaScript added to the HTML footer.

3. You can confirm this by viewing the page source from your browser.


## Page specific tracking

The default is set to "Add to every page except the listed pages". By
  default the following pages are listed for exclusion:

```
/admin
/admin/*
/batch
/node/add*
/node/*/*
/user/*/*
```

These defaults are changeable by the website administrator or any other
user with 'Administer Matomo' permission.

Like the blocks visibility settings in Drupal core, there is a choice for
"Add if the following PHP code returns TRUE." Sample PHP snippets that can be
used in this textarea can be found on the handbook page "Overview-approach to
block visibility" at https://drupal.org/node/64135.


## Custom variables

One example for custom variables tracking is the "User roles" tracking. Enter
the below configuration data into the custom variables settings form under
admin/config/system/matomo.

Slot: 1
Name: User roles
Value: [current-user:matomo-role-names]
Scope: Visit

Slot: 1
Name: User ids
Value: [current-user:matomo-role-ids]
Scope: Visit

More details about custom variables can be found in the Matomo API documentation
at https://matomo.org/docs/javascript-tracking/#toc-custom-variables.


## Advanced Settings

You can include additional JavaScript snippets in the custom javascript
code textarea. These can be found on various blog posts, or on the
official Matomo pages. Support is not provided for any customizations
you include.

To speed up page loading you may also cache the Matomo "matomo.js"
file locally.


## Maintainers

Current maintainers:
- Carsten Logemann - [C_Logemann](https://www.drupal.org/user/218368)
- Shelane French - [shelane](https://www.drupal.org/user/2674989)
- Florent Torregrosa - [Grimreaper](https://www.drupal.org/user/2388214)

Previous maintainers:
- Alexander Hass (hass)

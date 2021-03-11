CKEditor Word Count & Character Count
=====================================

Description
===========
CKEditor Wordcount can be enabled per filter format, and will show you the
paragraph and word count, as well as count spaces as characters or count
HTML as characters if you select those options.  You can also set a maximum
limit on total words or total characters, which will prevent input in
CKEditor after that limit.

Please note that while you can have a maximum limit imposed by this plugin,
do not lean on that for any real character limit validation.

Composer-based installation
===========================
If using composer, make sure to add "w8tcha/ckeditor-wordcount-plugin" to
the list of packages to be installed under "web/libraries" by adding:

```
"extra": {
  "installer-paths": {
    "web/libraries/{$name}": [
      "type:drupal-library",
      "w8tcha/ckeditor-wordcount-plugin"
    ],
  }
}
```

Manual installation
===================
1. Download the plugin from http://ckeditor.com/addon/wordcount
2. Place the plugin in the root libraries folder
   (/libraries/ckeditor-wordcount-plugin).

Finally, enable CKEditor Wordcount module in the Drupal admin.
Each filter format will now have a config tab for this plugin.

Dependencies
============
This module requires the core CKEditor module and CKEditor Notification module.

Uninstallation
==============
1. Uninstall the module from 'Administer >> Modules'.

MAINTAINERS
===========
Kevin Quillen - https://www.drupal.org/u/kevinquillen

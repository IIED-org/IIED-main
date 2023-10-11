# CKEditor Word Count & Character Count

## Description
CKEditor Wordcount can be enabled per filter format, and will show you the
paragraph and word count, as well as count spaces as characters or count
HTML as characters if you select those options.  You can also set a maximum
limit on total words or total characters, which will prevent input in
CKEditor after that limit.

Please note that while you can have a maximum limit imposed by this plugin,
do not lean on that for any real character limit validation.

## Dependencies
This module requires the core CKEditor module and CKEditor Notification module.

## How to install

### Manual installation
1. Download the plugin from http://ckeditor.com/addon/wordcount
2. Place the plugin in the root libraries folder
   (/libraries/ckeditor-wordcount-plugin).

Finally, enable CKEditor Wordcount module in the Drupal admin.
Each filter format will now have a config tab for this plugin.

### Composer-based installation

#### Method 1 - using composer-merge-plugin
This method has the advantage of placing the maintenance of the plugin version
on the hands of the module maintainers. To add the composer-merge-plugin, run
`composer require wikimedia/composer-merge-plugin`.

Then, update the extra section of the root `composer.json` file as follows:

```
    "extra": {
        "merge-plugin": {
            "include": [
                "[web-root]/modules/contrib/ckwordcount/composer.libraries.json"
            ]
        }
    }
```

Replace `[web-root]` with the value of your web root folder (usually `web`).
Run `composer require drupal/ckwordcount w8tcha/ckeditor-wordcount-plugin`,
the WordCount Plugin will be installed to the `libraries` folder automatically.

#### Method 2 - using a custom repository
Copy the following into the root `composer.json` file's `repository` key

```
    "repositories": [
        {
            "type": "package",
            "package": {
                "name": "w8tcha/ckeditor-wordcount-plugin",
                "version": "1.17.8",
                "type": "drupal-library",
                "dist": {
                    "url": "https://github.com/w8tcha/CKEditor-WordCount-Plugin/releases/download/v1.17.8/CKEditor-WordCount-Plugin.zip",
                    "type": "zip"
                }
            }
        }
    ]
```

Run `composer require drupal/ckwordcount w8tcha/ckeditor-wordcount-plugin`,
the WordCount Plugin will be installed to the `libraries` folder automatically as well.

## Uninstallation
1. Uninstall the module from 'Administer >> Modules'.

## MAINTAINERS
Kevin Quillen - https://www.drupal.org/u/kevinquillen

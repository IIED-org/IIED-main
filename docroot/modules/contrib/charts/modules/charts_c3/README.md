#Installation Using Composer (recommended)

If you use Composer to manage dependencies, edit your site's "composer.json"
file as follows.

1. Run `composer require --prefer-dist composer/installers` to ensure that
you have the "composer/installers" package installed. This package
facilitates the installation of packages into directories other than
"/vendor" (e.g. "/libraries") using Composer.

2. Add the following to the "installer-paths" section of "composer.json":

        "libraries/{$name}": ["type:drupal-library"],

3. Add the following to the "repositories" section of "composer.json":

        {
            "type": "package",
            "package": {
                "name": "c3js/c3",
                "version": "v0.7.20",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "c3"
                },
                "dist": {
                    "url": "https://github.com/c3js/c3/archive/v0.7.20.zip",
                    "type": "zip"
                }
            }
        },
        {
            "type": "package",
            "package": {
                "name": "d3/d3",
                "version": "v4.9.1",
                "type": "drupal-library",
                "extra": {
                    "installer-name": "d3"
                },
                "dist": {
                    "url": "https://github.com/d3/d3/archive/v4.9.1.zip",
                    "type": "zip"
                },
                "require": {
                    "composer/installers": "^1.0 || ^2.0"
                }
            }
        }

4. Run `composer require --prefer-dist c3js/c3:0.7.20 d3/d3:4.9.1` -
you should find that new directories have been created under "/libraries"

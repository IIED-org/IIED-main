#Installation using Composer (recommended)

If you use Composer to manage dependencies, edit your site's "composer.json"
file as follows.

1. Add the asset-packagist composer repository to "composer.json".
This allows installing packages (like Chart.js) that are published on NPM.

        "asset-packagist": {
            "type": "composer",
            "url": "https://asset-packagist.org"
        },

You may need to add it in your composer.json file like this (second item in
the array):

        "repositories": [
            {
                "type": "composer",
                "url": "https://packages.drupal.org/8"
            },
            {
                "type": "composer",
                "url": "https://asset-packagist.org"
            },
        ],

2. Run `composer require --prefer-dist oomphinc/composer-installers-extender`
to ensure that you have the "oomphinc/composer-installers-extender" package
installed. This package facilitates the installation of any package into
directories other than the default "/vendor" (e.g. "/libraries") using
Composer.

3. Configure composer to install the Chart.js dependencies into "/libraries"
by adding the following "installer-types" and "installer-paths" to the "extra"
section of "composer.json". If you are not using the "web" directory, then
remove "web/" from the lines below:

        "extra": {
            "installer-types": ["npm-asset"],
            "installer-paths": {
                "web/libraries/chartjs": ["npm-asset/chart.js"],
                "web/libraries/chartjs-adapter-date-fns": ["npm-asset/chartjs-adapter-date-fns"],
            },
        }

4. Run `composer require --prefer-dist npm-asset/chart.js:^3.3
npm-asset/chartjs-adapter-date-fns:^2` - you should find that new directories
have been created under "/libraries".

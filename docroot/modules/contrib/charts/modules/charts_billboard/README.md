#Installation Using Composer (recommended)

If you use Composer to manage dependencies, edit your site's "composer.json"
file as follows.

1. Run `composer require --prefer-dist composer/installers` to ensure that
you have the "composer/installers" package installed. This package facilitates
the installation of packages into directories other than "/vendor" (e.g.
"/libraries") using Composer.

2. Add the following to the "installer-paths" section of "composer.json":

        "libraries/{$name}": ["type:drupal-library"],

3. Add the following to the "repositories" section of "composer.json":

         {
             "type": "package",
             "package": {
                 "name": "billboardjs/billboard",
                 "version": "3.4.1",
                 "type": "drupal-library",
                 "extra": {
                     "installer-name": "billboard"
                 },
                 "dist": {
                     "url": "https://github.com/naver/billboard.js/archive/3.4.1.zip",
                     "type": "zip"
                 }
             }
         },
         {
             "type": "package",
             "package": {
                 "name": "d3/d3",
                 "version": "4.9.1",
                 "type": "drupal-library",
                 "extra": {
                     "installer-name": "d3"
                 },
                 "dist": {
                     "url": "https://cdnjs.cloudflare.com/ajax/libs/d3/4.9.1/d3.js",
                     "type": "file"
                 },
                 "require": {
                     "composer/installers": "^1.0 || ^2.0"
                 }
             }
         }

4. Run
`composer require --prefer-dist billboardjs/billboard:3.4.1 d3/d3:4.9.1`
you should find that new directories have been created under "/libraries"

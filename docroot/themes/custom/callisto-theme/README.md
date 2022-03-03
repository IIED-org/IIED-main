# Callisto starter theme

> Needs testing. Please see the [issue queue](https://gitlab.agile.coop/agile-public/callisto-theme/issues).

## Contents

* [Overview](#overview "Overview")
* [Setup](#setup "Requirements and installation")
* [Usage](#usage "How to use")
  * [SASS and CSS](#sass)
  * [Javascript](#javascript)
  * [Twig templates](#twig-templates)
  * [Assets](#assets)
* [Configuration](#configuration "Configuring the theme")
* [FAQ & Issues](#faq-issues "FAQ and issues")
* [Extend the theme](#extend-the-theme)
* [Maintenance](#maintenance)

## [Overview](#overview)

This project aims to provide a strong foundational starter theme for a Drupal site using atomic folder structure and Tailwind.

It is __not__ a theme that can be used as is and it is __not__ a theme that receives automatic updates post installation.

[Does the theme provide any automated frontend tests?](#does-the-theme-provide-any-automated-frontend-tests)

### Who can use the theme

Is this theme for me?

* Do you want to use Tailwind?
* Do you need a basic starting point for a Drupal site's front end?
* Are you okay with maintaining the theme yourself in the future?

### Project scope

1. Tightly integrated with Agile Collective's Drupal 8 base starter and design processes
2. Provides integration with Tailwind and a base Tailwind configuration
3. Minifies and stylelints JS and CSS in order to maintain code standards
4. Has documentation on how to extend the theme and configure it
5. Provides a base amount of pre-styled components that are repeated across our projects

### Drupal

All components are ran against the SASS lint and then compiled into `css/style.css` . The same goes for all `.js` files found in the `source/components/` folder.

If you have the Drupal [components library](https://www.drupal.org/project/components "Components libraries") module installed you can use patterns directly in Drupal's twig templates with includes, eg. `{% include '@elements/field/field_text.twig' %}` .

### Sites using this theme

Currently two sites are using this theme or earlier versions of it.

* [bisa.ac.uk](https://www.bisa.ac.uk/)
* [conference.bisa.ac.uk](https://conference.bisa.ac.uk/)

## [Setup](#setup)

### Requirements

* node 12+
* php 7+
* composer 1.9+
* yarn 1.13+ _or npm 6.4+_
* Drupal [components library](https://www.drupal.org/project/components "Components libraries") module

### Installation

You'll probably have to run all of these commands inside Lando, eg `lando yarn` or your environment equivalent.

1. Git clone this repository  
 _or_  
 run `composer require agile-collective/callisto-theme` if you are using Zephyr or you have our packages.agile.coop configured in your composer.json file _[documentation tbc]_

2. Install the node packages with `yarn install`

3. You will now need to configure Browsersync, in `config/gulpconfig.js` edit `browsersync` and set the port for your lando setup and the domain if you want to proxy the Drupal site

4. You should now be able to compile using `yarn dev`

5. If you cloned this repo directly make sure you remove the `.git` folder if you're going to commit this theme to another repository.

## [Usage](#usage)

There are several additional commands available.

* `yarn dev` compiles for development

* `yarn build` compiles for production

* `yarn gen` compiles all files for production without running Browsersync

* `yarn ck` compiles the CK editor stylesheet, which do not get compiled in `build` or `dev`

* `yarn icons` compiles the SVG sprite

* `yarn health-check` runs automated tests on the current theme with some assumptions

Folder structure explained:

* `assets` should contain all of your images, fonts and SVGs that are imported into the stylesheet
* `config` will contain the configuration file for the theme and a few other things you can tweak
* `css` contains the final stylesheets
* `js` contains the transpiled javascript files
  * `unique` will contain any javascript files that you want to exclude from the main `script.min.js`
* `source` will contain all the source files for your twig templates, sass and javascript

Components:

* Foundation - typography, mixins, utilities, colours
* Elements - fields, icons, form elements
* Compounds - blocks, paragraph types
* Templates - base and specific node templates, 403/404 and maintenance page

[Here is how you can change the components folder structure.](#change-the-components-folder-structure)

_we need more docs on getting started..._

## [SASS and CSS](#sass-and-css)

This theme uses SASS as the preprocessor for CSS. All of your SASS components are expected to be inside `source/components/` with `source/css/` containing the primary stylesheet with the scaffolding necessary for Tailwind and the glob for pulling in the components. You're welcome to add any additional variables or stylesheets here, however if you want to add mixins or other utilities you should consider adding them inside a foundation component.

You can exclude stylesheets from the compiler by adding an underline at the start of the name like so `_block.scss`.

### CK Editor Stylesheets

Appending you sass file with `ck-` it will compile into a separate CK editor stylesheet and be excluded from the main styles.  
The advantage of having separate CK editor stylesheets is that you can add styling specifically within the context of the editor, whereas your normal typography would be within the context of the entire website. This should let you have workarounds for any issues more easily.  
It also makes the final stylesheet for ckeditor more lightweight.

Run `yarn ck` separately in order to compile this stylesheet.

## [Javascript](#javascript)

We are using [Google closure compiler](https://developers.google.com/closure/compiler) and [ESLint](https://eslint.org/) in order to transpile and minify the javascript files.

You are expected to write your javascript inside the `source/components/` folder, organised in any pattern you wish.

For example writing my icon script code inside `source/components/01-foundation/icons/icons.js` will transpile and minify the javascript into the `js/script.min.js` file.

You can exclude javascript from compilers by adding an underline at the start of the name like so `_text.js`.

By default we support `jQuery` and the `Drupal` object to which you can attach functions and behaviours as well as the `drupalSettings`.

### Unique JS libraries

Sometimes you will want to only include some js files conditionally in Drupal as a separate library. You can do that by appending your filename with `&`.

For example `&lone-wolf.js` would get compiled to `js/libraries/lone-wolf.min.js`.

You can disable this functionality under `gulpconfig.js:js/libraries/enabled` as it can affect compilation performance.

### Node modules

As of 1.3.0 we support node_module resolution using rollup.js which means you can add any npm modules into your javascript.

For example

`yarn add svg4everybody`

Will then allow me to import the module in any of my js files and it works in standalone libraries too.

```js
const svg4everybody = require('svg4everybody');

svg4everybody();
```

Just be wary of the include and function scoping, particularly if you're dealing with self-invoking functions.

### ESLint and configuration

> [ESLint official docs](https://eslint.org/docs/user-guide/configuring)

The linter will attempt to automatically fix issues and only return errors when it can't do so on its own.

The file `.eslintrc.json` contains the configuration for the linter, with specific adjustments derrived from `airbnb/base` which is what Drupal Core also uses.

### Ignore code from linting

If you want the linter to ignore a specific piece of code you need to wrap it in these comments

```js
/* eslint-disable */

// my code...

/* eslint-enable */
```

[See more options.](https://eslint.org/docs/user-guide/configuring#disabling-rules-with-inline-comments)

### I want to declare a global variable in javascript

The javascript linter will automatically convert global variables out, however you can whitelist them under the `"globals":` in the `.eslintrc.json` configuration file.

### External variables eg. Drupal

Our compiler by default will shorten the names of your functions and variables. That behaviour can be disabled here. However you can also add your variable or function to be whitelisted by declaring them as variables inside the `source/js/external.js` file.

You do have to do this for internal properties as well eg `Drupal.behaviours.myBehaviour` or just declare your behaviour as `var myBehaviour;`.

### jQuery

In order to use jQuery with the `$` short hand notation you need to wrap your code in a self-invoking function. This code passes down the jQuery object as `$` you can then easily use.

```js
(function ($) {
  // my code
  // eg. console.log($(document))
})(jQuery);
```

You can pass down multiple variables into self-invoking functions, here is how you add Drupal behaviours in addition to jQuery.

```js
(function ($, Drupal) {
  Drupal.behaviors.myModuleBehavior = {
    attach: function (context, settings) {
      // my code
    }
  };
})(jQuery, Drupal);
```

### Customising the JS compiler

The closure compiler has a few customisation options that we've exposed in the `gulpconfig.js` file:

* compilationLevel
  * `'ADVANCED'` _(default)_
  * `'SIMPLE'`

* outputWrapper
  * `'(function(){\n%output%\n}).call(this)'` _(default)_ // Change how the final compiled file is wrapped, using `%output%` for contents

* warningLevel
  * `'QUIET'` _(default)_
  * `'VERBOSE'`

* externs // Array of js files that contains external declarations

Can all be customised separately for standalone libraries and the main script file.  
[Full list of options](https://github.com/google/closure-compiler/wiki/Flags-and-Options)  
[Compilation levels explained](https://developers.google.com/closure/compiler/docs/compilation_levels)

## [Twig templates](#twig-templates)

There are two directories we have to be mindful of.
`templates/` contains all the specific `.html.twig` templates that Drupal understands, these are template overrides which we'd encourage you to use `{% include '' %}` with in order to use the templates found in `source/components/`.

The benefit of keeping component templates and the Drupal templates separate is that in theory it allows you to re-use your templates more easily by using the include or extend method.

## [Assets](#assets)

We automatically compile SVGs found in `source/icons/` to sprites found in `assets/icons/`, you can then configure this process under `gulpconfig.js:icons`. By default two separate sprites are generated, one to be used in CSS and another to be used in Twig, see below for more details.

### SVG accessibility

We encourage you to add `<title>` and `<desc>` tags to your icons where appropriate to improve the accessibility of the SVGs, by default all SVGs we provide have these tags.

You can also add metadata to the `source/icons/icon_data.yml` as per the example where the identifier of the SVG is the filename without the extension. This data will then be compiled in the final sprite.

```yml
ac_logo:
  title: "Agile Collective logo"
```

### SVGs in markup

Using `{% include '@elements/icons/icon.twig' %}` we can easily include our SVG icons from `source/icons/` directly into our twig template. You will need to pass down the icon_id as a string which matches to the filename of the source icon and the size or height and width in tailwind sizing.

For example, where the width and height will map to the height and width in tailwind.config.js

```twig
{% include '@elements/icons/icon.twig' with {
  icon_id: 'ac_logo',
  width: 128,
  height: 24,
} %}
```

Note that you may have to explicitly whitelist the classes from the purge as they're dynamic, you can do that in a twig comment too.

```twig
{#
Purge whitelist:
w-128
h-24
#}
```

### SVGs in CSS

`source/components/01-foundation/00-sass-utilities/icons-sprite.scss` contains the generated classes for using the SVG sprite in your SASS directly, you can then extend these classes in your own code.

### Other assets in CSS

We provide a SASS utility for importing images and fonts, but you can extend that to cover other folders.

* `font('filename')` returns a URL for `assets/fonts/`

* `image('filename')` returns a URL for `assets/images/`

Example for importing a font

```sass
@font-face {
  font-family: 'Roboto';
  src: font('roboto/Roboto-Black.eot');
  src: local('Roboto Black'), local('Roboto-Black'),
      font('roboto/Roboto-Black.eot?#iefix') format('embedded-opentype'),
}
```

We use this in our Roboto font-family implementation.

### Fonts

Fonts can sit in `assets/fonts/` and we recommend using a tool such as [Transfonter](https://transfonter.org/) with `Family support` and `Add local rule` and all file types enabled, this will provide the best coverage for your users and the browser will only use the font file it needs to.

You will be given a stylesheet from the tool which will let you copy the @font-face declarations directly and you will only have to replace the directory it sits in, which can be easily done with Find & Replace in your IDE.

We provide an example font for `Roboto` being set up, which you can remove entirely. You will notice the font family for Roboto is also declared in the Tailwind configuration.

### Favicons

There isn't an automated favicon generator integrated into the theme, however [RealFaviconGenerator](https://realfavicongenerator.net/) works great and can help you manage and create favicons and meta data to cover most modern devices, including a webmanifest file.

`assets/favicon/` is the most appropriate place for the favicon files to be in and you will want to add them to the `components/04-templates/layout/html.twig` in the `<head>` wrapper. The generator will give you the HTML you need to add and you just need to replace the directory with a relative to web root URL path to the favicons folder. eg. `themes/custom/callisto-theme/assets/favicon/favicon.ico`.

We provide an example `favicon.ico`.

### Images

We currently do not do any optimisations on images because the use-case hasn't come up so far as Drupal already has core functionality for processing images.

## Configuration

In `config/gulpconfig.js` you'll find some of the configuration for various plugins and tooling used within Gulp. The defaults are sensible enough for most websites but you will want to change the Browsersync config for your setup.

We also have specific configuration files:

* `.browserslistrc` is for determining the rules of browser support, PostCSS and a few other modules use this to determine what to process
* `.stylelintrc` determines the stylelint configuration not set in gulpconfig.js
* `.eslintrc.json` configures the JS linter
* `tailwind.config.js` will contain the Tailwind configuration

## [FAQ & Issues](#faq-issues)

> [Gitlab issue queue](https://gitlab.agile.coop/agile-public/callisto-theme/issues)

### Does the theme provide any automated frontend tests?

Short answer, no.

We made a decision that the Drupal setup is the one that should be handling most of the automated testing regarding regression, accessibility or performance and that these tests should be in place regardless of what theme you decide to use.

See [Zephyr](https://gitlab.agile.coop/agile-public/zephyr), our base starter featuring these.

### My class is not compiling into the css stylesheet

_Your class is in the .scss file but not in the compiled stylesheet but other classes are._

We use the PurgeCSS that comes with Tailwind to remove unused classes, however sometimes this can remove dynamic classes added through JS/PHP.

You can add `/* purgecss start ignore */` and `/* purgecss end ignore */` comment blocks to your styling.

Alternatively, in `tailwind.config.js` you'll find `whitelist` and `whitelistPatterns` under the `purge` config, in these arrays you can add the class or a regex for multiple classes to be whitelisted.

You also have the option to add the class inside a comment in your template or JS file and it will be picked up by PurgeCSS next time you run the compiler again.

[Purge in Tailwind](https://tailwindcss.com/docs/controlling-file-size)

### Why are there separate builds for production and development

Due to Tailwind running PurgeCSS, if you add a new class to a template it will still be purged because the plugin does not rescan the files every time, so to make local development less painful we don't want to run PurgeCSS as well.

Some node modules may also behave differently between the two environments.

### The javascript compiler is throwing 'Undefined variable' errors

This is a common occurence when adding external libraries or if your code is not properly namespaced. If you are using an external library and you need a particular variable or object to not be renamed by the compiler you should add it to the externs.js file where you just want to declare the name of your variable, a simple `var myCustomVariable;` will work and the compiler will not rename it.

### Help! The compiler keeps throwing errors

The compiler may throw errors when it doesn't know what types of arguments you're passing down or if you're misusing your functions. You can ignore specific warnings by [adding annotations](https://github.com/google/closure-compiler/wiki/@suppress-annotations).

### More FAQs to be added

Add issues in the issue queue if you have questions.

## [Extend the theme](#extend-the-theme)

### [Configuring Tailwind](#configuring-tailwind)

See [official docs](https://tailwindcss.com/docs/configuration/) for full details.

All of our Tailwind configuration sits inside `tailwind.config.js`. We have sensible defaults there but you might want to extend and add to it.
Note that changing the name of colours or attributes may break existing components.

### Change the components folder structure

_We don't recommend this as it might break some functionality._

You can change the structure of the folders inside `source/components/` only. For example if you wanted to transition to an atomic structure you can rename the folders to atoms, molecules and so on. This should have no effect on the compilation but it will have an effect on the twig namespacing from the components module.

What you must do is rename all the twig includes `{% include '' %}` to point to the new and correct folders and in `callisto_theme.info.yml` under `component-libraries:` you will need to rename those namespaces to the correct folder and change the folder directory.

Example:

```yml
foundation:
    paths:
      - source/components/01-foundation
```

becomes

```yml
atoms:
    paths:
      - source/components/01-atoms
```

### Exposing Tailwind values into SCSS

> Please note that Tailwind provides functions you can use directly in your theming without the need for the following code.
_See [Functions & Directives](https://tailwindcss.com/docs/functions-and-directives/)_

In some rare circumstances it will be easier if you get Tailwind config as SASS variables, so adjust the code to your needs.

For this code to work you will need to import the tailwindConfig and the `fs` library from Node.

```js
  fs = require('fs'),
  tailwindConfig = require('./tailwind.config'), // Import Tailwind config so that we can parse the breakpoints and colors
```

The code below will write the breakpoints and the colors in the Tailwind config file into a .scss file if you need them.  
Add this code to your gulpfile.js and then simply call the `writeTailwindToSass()` function in any gulp task.

```js
/**
 * Breakpoints are simple enough to parse so it doesn't need any complicated logic
*/
function getTailwindScreens() {
  let sass = `// This file is automatically generated, do not edit \n`;
  for (let key in tailwindConfig.theme.screens) {
    if (tailwindConfig.theme.screens.hasOwnProperty(key)) {
      let value = tailwindConfig.theme.screens[key];

      sass += `$${key}: ${value}; \n`;
    }
  }
  return sass;
}

/**
 * Colours are an example of an object that can contain objects underneath it
 * so we check with .hasOwnProperty()
*/
function getTailwindColours() {
  let sass = `// This file is automatically generated, do not edit \n`;
  for (let key in tailwindConfig.theme.colors) {
    if (tailwindConfig.theme.colors.hasOwnProperty(key)) {
      let property = tailwindConfig.theme.colors[key];

      if (typeof property === 'object' && property !== null) {
        for (let subkey in property) {
          if (property.hasOwnProperty(subkey)) {
            let subvalue = property[subkey];
            sass += `$${key}-${subkey}: ${subvalue}; \n`;
          }
        }
      } else {
        sass += `$${key}: ${property}; \n`;
      }

    }
  }
  return sass;
}

/**
 * Our previous functions will return strings we can write directly into files using Node's 'fs'
*/
async function writeTailwindToSass() {
    let screenData = getTailwindScreens();
    fs.writeFile('source/components/01-foundation/02-tailwind/tailwind-screens.scss', screenData, function (err, file) {
      if (err) throw err;
    })

    let coloursData = getTailwindColours();
    fs.writeFile('source/components/01-foundation/02-tailwind/tailwind-colours.scss', coloursData, function (err, file) {
      if (err) throw err;
    })
}
```

## [Maintenance](#maintenance)

We need more guides on how to maintain the theme:

* CSS and JS compilation

* How Tailwind is integrated

* All the plugins and modules that we use

I've tried to comment the various functions and decisions I've made but there will be gaps in documentation, please [open an issue](https://gitlab.agile.coop/agile-public/callisto-theme/-/issues) if you find any.

### Automated tests

We have begun writing some automated tests with [Mocha](https://mochajs.org/) and [Chai.js](https://www.chaijs.com/) to help with regressions and make sure that our builds of the theme continue to be healthy.

You can run these tests using `yarn health-check`.

There is also a post-installation script that checks a few variables, mostly helpful for first time installations, but it does run on `yarn add` or `npm install` as well.

We will continue to expand these tests for more coverage, but they take a lot of time. These modules are not added as dev dependencies as they can be used for maintaining the theme long term too.  
The health check only verifies the existence of certain files and the folder structure of the theme, currently it doesn't verify that gulp tasks work properly.

### Versioning

The versioning follows the structure of `major-release.minor-release.bugfix-release`.

A major release would mean foundational changes to the theme, integrations and tooling. More radical changes that might fundamentally affect how the theme works.

A minor release would involve adding new components or node modules, updating their code or updating documentation.

Then a bug fix release should solve any issues that prevent the theme from working.

A new minor release should include all the bugfixes from the previous minor release.

### Applying bug fixes to existing setups

> [Issue](https://gitlab.agile.coop/agile-public/callisto-theme/-/issues/19)

We've made it clear that this is a starter theme and not a base theme. Generally speaking maintaining a theme is low effort long term, things move fast but you can always lock down the versions of your node version or modules.

However there may be cases where you might need to apply a fix to an existing theme, if there are security issues or critical bugs. We don't yet have a solution to this.

### .lando.yml

This is the .lando.yml file that I used when maintaining the theme. You don't have to use this, you can spin up something else.

```yml
# Documentation: https://dev.docs.agile.coop/docs/environment/intro

name: callistotheme
recipe: drupal8
services:
  node:
    type: 'node:12'
    overrides:
      image: 'agilecollective/lando-node:2.0.2-node12'
      ports:
        - '3050:3050' # You should change this port to something random and then update gulpconfig.js in the theme to match
tooling:
  npm:
    service: node
  yarn:
    service: node
  node:
    service: node
  gulp:
    service: node
```

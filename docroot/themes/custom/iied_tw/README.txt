DESCRIPTION
-----------

IIED Tailwind Theme for Drupal 9.

USAGE
-----

```
cd docroot/themes/custom/iied_tw
```

To re-compile the CSS:

```
lando npm install
lando npm run watch
```

BUILD COMMANDS
--------------

- Use `lando npm run dev` to generate the un-minified, expanded CSS.
- This is intended for local development. You can also use `lando npm run watch` to re-compile automatically when changes are made.
- Use `lando npm run prod` to generate the minified, compressed CSS.
  These are the versions intended for use on production.
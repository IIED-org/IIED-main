# Taxonomy Menu

Transforms your taxonomy vocabularies into menus with ease!

For a full description of the module, visit the
[project page](https://www.drupal.org/project/taxonomy_menu).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/taxonomy_menu).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Navigate to Administration > Extend and enable the module.
2. Navigate to Administration > Structure > Taxonomy menu to add a new taxonomy menu.
3. From the appropriate dropdown, assign a vocabulary.
4. From the appropriate dropdown, assign a menu.
5. Save.
6. Clear caches.

Modify the menu:
Please note - once the taxonomy menu is created, the menu items are decoupled
from the taxonomy.

You can adjust the weight/order of the menu items, the ability to expand, and if
the item is enabled or disabled.

We have built some constraints to ensure the menu items resemble it's associated
taxonomy. First, you cannot adjust the parents. This ensures the original
taxonomy tree stays somewhat in tact. Second, you cannot change the title or
description for taxonomy-generated menu items. This is rendered dynamically from
the original taxonomy.

Caching:
Menu items are heavily cached. Upon making changes to menus and/or taxonomy,
please clear the cache before submitting an issue.


## Maintainers

- David Stoline - [dstol](https://www.drupal.org/u/dstol)
- Andrey Troeglazov - [andrey.troeglazov](https://www.drupal.org/u/andreytroeglazov)
- Ashraf Abed - [ashrafabed](https://www.drupal.org/u/ashrafabed)
- Benni Mack - [bmack](https://www.drupal.org/u/bmack)
- Damien McKenna - [DamienMcKenna](https://www.drupal.org/u/damienmckenna)
- Adam Bergstein - [nerdstein](https://www.drupal.org/u/nerdstein)
- Nick Dickinson-Wilde - [NickDickinsonWilde](https://www.drupal.org/u/nickdickinsonwilde)
- Russel Anthony - [rwanth](https://www.drupal.org/u/rwanth)

Supporting organization:
- [Hook 42](https://www.drupal.org/hook-42)

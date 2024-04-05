# View Unpublished

The View Unpublished module allows the user to grant access for specific user
roles to view unpublished nodes of a specific type. Access control is quite
granular in this regard.

Additionally, using this module does not require any modifications to the
existing URL structure.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/view_unpublished).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/view_unpublished).


## Table of contents

- Requirements
- Recommended Modules
- Installation
- Configuration
- Troubleshooting
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Recommended Modules

To give specific roles the ability to publish/unpublish certain node types
without giving those roles administrative access to all nodes.

- [Override node options](https://www.drupal.org/project/override_node_options)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Modules](https://www.drupal.org/docs/extending-drupal/installing-modules).


## Configuration

1. Enable the module at Administration > Extend.
2. Navigate to Administration > People > Permissions and assign the
appropriate permissions to the roles you wish to be able to view
unpublished nodes.

This module also integrates with the core Content overview screen at
Administration > Content. If you choose the "not published" filter, Drupal will
show the user unpublished content they're allowed to see.

## Troubleshooting

The wrong views filter:

- Using View Unpublished with Views
Use the `"Published status or admin user"` filter, NOT `"published = yes"`.
Views will then respect the custom permissions.

I can not access the node:
- If for some reason this module seems to not work, try rebuilding the node
permissions: Administration > Reports > Status > Rebuild. Note that this
can take significant time on larger installs and it is HIGHLY recommended
that you back up the site first.


## Maintainers

- Domenic Santangelo - [entendu](https://www.drupal.org/u/entendu)
- Agnes Chisholm - [amaria](https://www.drupal.org/u/amaria)
- Brad Bowman - [beeradb](https://www.drupal.org/u/beeradb)
- Erik Levinson - [elevins](https://www.drupal.org/u/elevins)

**Additional credits:**
- Brad Bowman/beeradb - Aten Design Group
- Domenic Santangelo/dsantangelo - WorkHabit
   (7.x) for this feature.
- The drupal community

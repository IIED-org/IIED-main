# Block Class

> **Add classes to any block** through the block's configuration admin page.

[[_TOC_]]

## Requirements

Requires the core [Block][1] module to be installed.

<img src="https://www.drupal.org/files/project-images/block-class-config.png"
width="50%" align="right" style="margin-left:15px;" alt="Block Class:
configuration of CSS classes on a block configuration form.">

## Installation

Install as you would normally install a contributed Drupal module.\
For further information, see: [Installing Modules][2].

## Configuration

* Visit the block configuration page at: `/admin/structure/block`\
Under: `Administration » Structure » Block Layout`\
and click on the `Configure` link for a block.

* Enter the classes in the field provided and save the block.

See an example with the `Breadcrumbs` block configuration form on the screenshot
on the right:

## Related modules

* Layout Builder Component Attributes allows editors to add HTML attributes to
Layout Builder components.\
The following attributes are supported:
  * ID
  * Class(es)
  * Style
  * `Data-*` Attributes

For more information see: [Layout Builder Component Attributes project page][3].

## Support and maintenance

Releases of the module can be requested or will generally be created based on
the state of the development branch or the priority of committed patches.

Feel free to follow up in the [issue queue][4] for any contributions, bug
reports, feature requests:

* [Submit a ticket in module's issue tracker][5] to describe the problem
encountered,
* Document a feature request,
* [Create a merge request][6],
* Comments, testing, etc...

Any contribution is greatly appreciated!

[1]: https://www.drupal.org/docs/core-modules-and-themes/core-modules/block-module/managing-blocks
[2]: https://www.drupal.org/docs/extending-drupal/installing-modules
[3]: https://www.drupal.org/project/layout_builder_component_attributes
[4]: https://www.drupal.org/project/issues/block_class?version=all_3.0.*
[5]: https://www.drupal.org/node/add/project-issue/block_class?version=3.0.x-dev
[6]: https://www.drupal.org/docs/develop/git/using-gitlab-to-contribute-to-drupal/creating-merge-requests

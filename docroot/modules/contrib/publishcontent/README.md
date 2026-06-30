# Publish Content

This module enables granular publish and unpublish permissions which allows you
to grant roles on your site the ability to publish or unpublish specific content types
without having to give them the "administer content" permission.
This is a lightweight solution to managing your content workflows.

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

- Configure permissions:
Home >> Administration >> People
(/admin/people/permissions/module/publishcontent)

- Configure Publish Content:
Home >> Administration >> Configuration >> Workflow
(/admin/config/workflow/publishcontent)

## Features

* Global un/publish any content permissions
* Global un/publish 'editable' content permissions
* Per "content type" un/publish content permissions
* Per "user role" un/publish own content permissions
* Per "user role" un/publish any content permissions
* Provides "Publish/unpublish" option/checkbox on content editing page
* Action links or button for one-click un/publishing.
* Exposes publish/unpublish links to your views, making it easy to streamline workflows for reviewers, editors and publishers.
* Provides Organic Group permissions allowing group members the specific ability to publish or unpublish content.
* Provides developer API hooks for using code to allow or deny publishing access to content for site builders with specific needs


## Recommended modules

[View Unpublished](https://www.drupal.org/project/view_unpublished):
This module allows you to grant access for specific user roles to view unpublished nodes
of a specific type. Access control is quite granular in this regard.

## Other modules

Drupal 11.3 [adds a permission to control the published status of nodes](https://www.drupal.org/node/3528500).
This module offers more by controlling the permission per content type and 
some UI additions like a tab to toggle the publishing status.

[Override Node Options](https://www.drupal.org/project/override_node_options):
The Override Node Options module allows permissions to be set to each field
within the Authoring information and Publishing options field sets on the node form.
It also allows selected field sets to be set as collapsed and / or collapsible.

[Workbench Moderation](https://www.drupal.org/project/workbench_moderation):
Workbench Moderation adds arbitrary moderation states to Drupal core's "unpublished" and
"published" node states, and affects the behavior of node revisions when nodes are published.


## Maintainers

- Aaron Bauman - [aaronbauman](https://www.drupal.org/u/aaronbauman)
- Jacob Roufa - [jacobroufa](https://www.drupal.org/u/jacobroufa)
- malaussene - [malaussene](https://www.drupal.org/user/79249)
- Simon Georges - [simon georges](https://www.drupal.org/u/simon-georges)
- Rodrigo Aguilera - [rodrigoaguilera](https://www.drupal.org/u/rodrigoaguilera)
- John Ennew - [johnennew](https://www.drupal.org/u/johnennew)
- Viktor Holovachek - [astonvictor](https://www.drupal.org/u/astonvictor)

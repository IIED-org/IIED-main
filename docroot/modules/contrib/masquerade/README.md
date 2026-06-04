# Masquerade

The Masquerade module allows site administrators (or anyone with enough
permissions) to switch users and surf the site as that user (no password
required). That person can switch back to their own user account at any time.

This is helpful for site developers when trying to determine what a client,
logged in as themselves, might see when logged into the site.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/masquerade).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/masquerade).


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

To Masquerade as another user, either click "Masquerade as [user name]" under
the user's account, or go to `/admin/people` and select "Masquerade as"
under the "Edit" drop-down.

To stop masquerading as another user, click the "Unmasquerade" link in the
"User account menu" block.

Optionally, set permissions under
`/admin/people/permissions/module/masquerade`.

If you use the LDAP Single Sign On module, you need to disable "Redirect users
on logout" in LDAP Single Sign On settings.

## Security

Masquerade's built-in access control mechanism has been designed to be simple,
smart, and secure by default:

- Users without the masquerade permission are not allowed to masquerade.
- Uid 1 may masquerade as anyone.  No one can masquerade as uid 1.
- If you have the identical permissions as the target user (or additional
  permissions), you are allowed to masquerade.
- Otherwise, access to masquerade as the target user is denied.

This means that Masquerade's built-in access control does not allow any kind
of privilege escalation.  It is safe to grant the masquerade permission to
user roles.  Users are never able to exceed their privileges by masquerading
as someone else.

More fine-grained access control (e.g., role-per-role, per-user, exclude-list)
may be supplied by separate add-on modules for Masquerade.


## Features and integration

The Masquerade module provides and aims for a deep integration with the
built-in user interface of Drupal core and popular contributed administration
interface modules:

- Contextual links (core)
- Toolbar (core)
- [Administration menu](http://drupal.org/project/admin_menu)

Aside from its user permission, the Masquerade module aims for a smart and
intuitive integration that does not require any configuration.  Its design and
architecture tries to meet the expectations of these user stories:

- This is helpful. Even though I don't know whether I'll actually need it.
- This is secure. 100% test coverage for the limited functionality
  it provides.
- This isn't bloat. Super small, dead simple, focused on the >80% task.
- This is friendly. Available when I need it, close to "zero-conf", and has
  absolutely no other UI implications.

Masquerading as Anonymous user is intentionally not supported. Likewise, more
granular user permissions and other custom plumbing needs to be implemented in
separate modules instead.


## Maintainers 

- Andrey Postnikov - [andypost](https://www.drupal.org/u/andypost)
- David N - [deekayen](https://www.drupal.org/u/deekayen)
- Gurpartap Singh - [Gurpartap Singh](https://www.drupal.org/u/gurpartap-singh)
- Mark Shropshire - [shrop](https://www.drupal.org/u/shrop)
- Andrew Berry - [deviantintegral](https://www.drupal.org/u/deviantintegral)
- Daniel Kudwien - [sun](https://www.drupal.org/u/sun)
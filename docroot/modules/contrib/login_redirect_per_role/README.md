# Login And Logout Redirect Per Role

The module allows to:

 - Redirect user (to specific URL) on login
 - Redirect user (to specific URL) on logout
 - Set specific redirect URL for each role
 - Set roles redirect priority
 - Use tokens in the redirect URL value
 - CAS integration

For a full description of the module, visit the
[project page](https://www.drupal.org/project/login_redirect_per_role).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/login_redirect_per_role).


## Table of contents

- Requirements
- Installation
- Configuration
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

- Navigate to the page /admin/people/login-and-logout-redirect-per-role.

- Configure the "Login redirect" and "Logout redirect" values in the 
  "Redirect URL" section. Adjust the roles' priority (order in the table) 
  to set up redirects for user login or logout. Leave the "Redirect URL" 
  values empty if you don't need to redirect.

- Click the "Save configuration" button.


## Maintainers

- Anton Ivanov - [Antonnavi](https://www.drupal.org/u/antonnavi)
- Jeroen Tubex - [JeroenT](https://www.drupal.org/u/jeroent)
- Pratik Mehta - [pratik.mehta19](https://www.drupal.org/u/pratikmehta19)

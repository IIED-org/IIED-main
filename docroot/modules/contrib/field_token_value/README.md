CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module creates a Field API type and widget which allows a field to be created with the value containing tokens. A simple field formatter is also provided to allow for the field value to be output on the page.

The value and tokens for the field are set within the instance settings with the field value being set in a presave.

An example of when this module could be useful is if you are wanting to combine the output of multiple fields for use in Views, or providing tokens within an additional string like "This page was last updated [node:changed]". This field is then available within the manage display of the entity bundle.

Note: The input field with the default value in the create/edit form is hidden. You can tell whether it is working, as the field value should display once saved. If it doesn't then it is likely that the token couldn't be replaced which is likely because either the token itself isn't available at the time of saving or the token was not added correctly and can't be found.

REQUIREMENTS
------------

This module requires the following modules:

* [Token](https://www.drupal.org/project/token)


INSTALLATION
------------

Install the Field Token Value module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

* Add a field type of "Field Token Value" to any bundle. Configuration of the field
  value can be handled in the field settings. A token browser is available for simpler
  selection of tokens. No field input is displayed on the edit forms, the value is
  automatically processed and saved. Display of the field can be handled via the
  display settings for the bundle.


MAINTAINERS
-----------

* Hayden Tapp - https://www.drupal.org/u/haydent

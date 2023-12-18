INTRODUCTION
------------

The Pager Serializer module extends the default Serializer to include
pagination data. This module is intended to be used with rest and the Rest
export View with a decoupled UI. Including the pagination data lets the front
end know total record count, and other useful infomation. This eliminates the
need to make another rest endpoint to get the total number of records.

All pagination properties are customizable. Allowing it to easily match what
the front end is expecting

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/pager_serializer

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/pager_serializer


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

 * Markdown filter (https://www.drupal.org/project/markdown):
   When enabled, display of the project's README.md help will be rendered
   with markdown


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit
   https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

 * Inside a REST export view, set the Format to "Pager Serializer".

 * Pagination property names are customizable.
   -  Administration » Configuration » Web services » Pager Serializer



MAINTAINERS
-----------

Current maintainers:
 * Steven Ayers (bluegeek9) - https://www.drupal.org/user/1286304

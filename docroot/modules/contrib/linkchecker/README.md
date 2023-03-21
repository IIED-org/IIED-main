CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Link checker module extracts links from your content when saved and
periodically tries to detect broken hypertext links by checking the remote
sites and evaluating the HTTP response codes. It shows all broken links under
Administration > Reports > Broken links.

  * For a full description of the module visit:
    https://www.drupal.org/project/linkchecker

  * To submit bug reports and feature suggestions, or to track changes:
    https://drupal.org/project/issues/linkchecker

  * See also the Linkchecker summary mail module, which can extend the module:
    https://www.drupal.org/project/linkchecker_summary_mail


REQUIREMENTS
------------

This module requires the following module:

  * Dynamic Entity Reference (https://www.drupal.org/project/dynamic_entity_reference)

For internal URL extraction you need to make sure that Cron always get called
with your real public site URL (for e.g. https://example.com/cron.php). Make
sure it's never executed with https://localhost/cron.php or any other
hostnames or ports, not available from public. Otherwise all links may be
reported as broken and cannot verified as they should be.


INSTALLATION
------------

  * Install as you would normally install a contributed Drupal module. Visit
    https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

  1. Navigate to Administration > Extend and enable the module
  2. Navigate to Administration > Structure > Content types and enable the
  fields to scan under each content type, by enabling "Scan broken links"
  under "Link checker settings". Also, set the "Extractor"
  3. Navigate to Administration > Configuration > Content authoring >
  Link checker for configuration
  4. Under "Link extraction" check all HTML tags that should be scanned
  5. Adjust the other settings if the defaults don't suit your needs
  6. Save configuration
  7. Wait for cron to check all your links... this may take some time! :-)

If links are broken they appear under Administration > Reports > Broken links.

If not, make sure cron is configured and running properly on your Drupal
installation. The Link checker module also logs somewhat useful info about its
activity under Reports -> Recent log messages.


MAINTAINERS
-----------

  * Carsten Logemann - https://www.drupal.org/u/c_logemann
  * Eirik Morland - https://www.drupal.org/u/eiriksm

Supporting organization:

  * Nodegard - https://www.drupal.org/nodegard

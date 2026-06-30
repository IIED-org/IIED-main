This module purges the URLs or paths of files through the Purge module. The purge is done when the files are either updated or deleted. This functionality is useful when your site allows replacing files maintaining the same URL and is using an external cache for anonymous users like Varnish, CloudFront, or Acquia purge.
How to use

HOW THE MODULE WORKS
--------------------

First, ensure that your site:

    Have enabled the purge module.
    Have at least one purger enabled that supports URLs - "url" or "wildcardurl", or "path" invalidation plugins.
    Have at least one purge processor enabled.

Then, go to 'Configuration > Development > Performance > Purge file' and set the purge processor that you want to be used, depending on your site requirements.

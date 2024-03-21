INTRODUCTION
------------

This module gives potential donors a couple easy ways to give to an
organization or person:

 * By credit card with Stripe (2.9% + 30 cents a transaction)
 * PLANNED: https://www.drupal.org/node/2744007 By bank transfer (low
   transaction fees)
 * By pledging to pay by check


REQUIREMENTS
------------

 * Minimal HTML (https://www.drupal.org/project/minimalhtml)
 * WYSIYG Linebreaks (https://www.drupal.org/project/wysiwyg_linebreaks)


INSTALLATION
------------

 1. Install as you would normally install a contributed Drupal module. Visit
    https://drupal.org/documentation/install/modules-themes/modules-8 for
    further information.

    Recommended installation approach is to use Composer:

     * `composer require drupal/give`

 2. To have full problem logging of issues people run into while trying to
    donate, you must configure your web server to allow requests to
    give_problem_log.php at the location at which your module was installed.

    At the default installation location with an Apache web server, that means
    editing your .htaccess file (in the web root of your site) to add a line:

    ```
    RewriteCond %{REQUEST_URI} !/modules/contrib/give/give_problem_log.php$
    ```

    The section which you add it to will look like this:

    ```
    # Allow access to Statistics module's custom front controller.
    # Copy and adapt this rule to directly execute PHP files in contributed or
    # custom modules or to run another PHP application in the same directory.
    RewriteCond %{REQUEST_URI} !/core/modules/statistics/statistics.php$
    RewriteCond %{REQUEST_URI} !/modules/contrib/give/give_problem_log.php$
    # Deny access to any other PHP files that do not match the rules above.
    # Specifically, disallow autoload.php from being served directly.
    RewriteRule "^(.+/.*|autoload)\.php($|/)" - [F]
    ```

    Be sure to verify where the give module was installed, and adapt the path
    used accordingly.

CONFIGURATION
-------------

 1. Navigate to Administration » Extend (admin/modules) and enable the Give
    module.
 2. Navigate to Administration » Configuration » Give donation settings
    (admin/config/services/give).
 3. From Stripe.com, enter your Stripe publishable API key and Stripe secret
    API key.  (You can use the test API keys for testing purposes.)


MAINTAINERS
-----------

 * Benjamin Melançon (mlncn) - https://www.drupal.org/u/mlncn
 * David Valdez (gnuget) - https://www.drupal.org/u/gnuget

Sponsored by Agaric, http://agaric.com/ and some excellent clients:
 * Portside - https://portside.org/
 * MASS Design Group - https://massdesigngroup.org/

Supported by Drutopia, https://drutopia.org/

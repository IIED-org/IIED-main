The Login Destination module provides a way to customize the destination that a
user is redirected to after logging in, registering to the site, using a
one-time login link or logging out.

The configuration consists of specifying so called login destination rules that
are evaluated when the login or logout takes place. Those rules are evaluated
against certain conditions and the user is taken to the destination specified
by the first matching rule. If the destination is empty, no redirect is
performed aka user is taken to the default destination. You can define pages
from which a user logs in/out to be a matching criterion. You can also select
certain user roles that are matched against those of a user. Note that only one
role has to match in order for the redirect to take place. If no roles are
selected the redirect is performed regardless of user roles.

The destination you specify can be an internal page or an external URL.
Remember to precede the url with http://. You can also use the <front> tag to
redirect to the front page. In case of
login/register form the page from which the user entered the form is treated as
the current page. Note that if you provide your own login/logout links you have
to add the 'current' GET parameter to them so Login Destination knows where
your users come from.

It also possible to set some advanced parameters on the setting page. Every
time in Drupal you can specify the 'destination' GET parameter in url to
redirect the user to a custom page. If you check the option
'Preserve the destination parameter' Login Destination will give priority to
this parameter over its own module settings. However with this option enabled
the redirect from the login block will not work. In some rare cases you can
also redirect the user just after using the one-time login link, before given
the possibility to change their password. Do this by checking the
'Redirect immediately after using one-time login link' option.

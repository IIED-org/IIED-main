Login And Logout Redirect Per Role
---------------------

About
---------------------
Module provides ability:

 * Redirect user (to specific URL) on Log in
 * Redirect user (to specific URL) on Log out
 * Set specific redirect URL for each role
 * Set roles redirect priority
 * Use Tokens in Redirect URL value
 * CAS integration

Roles order in list (configuration form) is their priorities:
higher in list - higher priority. For example: You set roles ordering as:

+ Admin
+ Manager
+ Authenticated

It means that when some user log in (in case of "Login redirect" table,
configuration form) or log out (in case of "Logout redirect" table,
configuration form) module will check:

Does this user have Admin role?

 * Yes and Redirect URL is not empty: Redirect to related URL
 * No or Redirect URL is empty:

Does this user have Manager role?

 * Yes and Redirect URL is not empty: Redirect to related URL
 * No or Redirect URL is empty:

Does this user have Authenticated role?

 * Yes and Redirect URL is not empty: Redirect to related URL
 * No or Redirect URL is empty: Use default Drupal action

Installation
---------------------
 1. Install the module to modules/contrib or modules folder
 2. Enable Login And Logout Redirect Per Role module

Configuration
---------------------

 * In menu go to: Configuration -> System -> Login and Logout Redirect per role
   (or /admin/people/login-and-logout-redirect-per-role)

 * Set "Login redirect" table "Redirect URL" values and roles priority
   (order in table) to setup redirect user on Log in action. Or leave
   "Redirect URL" values empty if you don't need redirect on user Log in.

 * Set "Logout redirect" table "Redirect URL" values and roles priority
   (order in table) to setup redirect user on Log out action. Or leave
   "Redirect URL" values empty if you don't need redirect on user Log in.

 * Click "Save configuration" button.

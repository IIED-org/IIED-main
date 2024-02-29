*Introduction:*
---------------
Leaks and hacks we’ve read about in recent years make it clear that passwords alone don't provide enough security to protect your online bank account or social media accounts. Two-factor authentication (2FA or MFA, for multifactor authentication) adds another layer of protection, and PCMag writers frequently exhort our audience to use it. Authenticator apps, such as Authy, Google Authenticator, or Microsoft Authenticator, enable one of the more-secure forms of 2FA. Using one of these apps can even help protect you against stealthy attacks like stalkerware.

*Module:*
---------
This module when enabled provides the security option to users to setup 2FA via Authenticator apps. The module relies on Google2FA QRCode library. Use composer to install the module and the library will automatically install as required dependency.

*Installation:*
---------------
1. Install the module via composer which will install the required dependency.
    composer require drupal/alogin
2. Login via admin user and goto /admin/modules.
3. Search Authenticator Login and enable it.
4. Thats it!

*Drush*
-------
There is a drush command available to reset Authenticator MFA for a given uid, drush mfa-reset [uid].
e.g. drush mfa-reset 1

*Dependency:*
------------
You can install the dependency manually by composer.
    composer require sonata-project/google-authenticator

*Notes:*
--------
* Module requires the library https://github.com/antonioribeiro/google2fa-qrcode.
* If you install via composer you do not need to worry about it.
* Otherwise install this library using composer.

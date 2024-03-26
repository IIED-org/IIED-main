Better Passwords
================

Better Passwords attempts to help users create better passwords by adhering to 
current recommendations from the US National Institute of Standards and 
Technology (NIST). This agency, part of the United States Department of 
Commerce, periodically publishes recommendations that have been extremely 
influential in determining standards for information security. The most recent 
recommendations on management of passwords is in NIST Special Publication 
800-63B, "Digital Identity Guidelines," section 5.1.1.2, "Memorized Secret 
Authenticators." (https://pages.nist.gov/800-63-3/sp800-63b.html#sec5)

Drupal core already meets or exceeds many of the NIST standards for creating 
and maintaining safe passwords; this module aims to get the rest of the way.

It should be noted that creation of an effective password, and safe storage 
thereof, is usually **only part** of an appropriate solution for authentication 
of digital identity; many organizations will need to use some form of two-factor 
authentication to fully adhere to NIST recommendations.

Installation
============

Better Passwords requires the zxcvbn-php library available at 
https://github.com/bjeavons/zxcvbn-php.

The easiest way to install this module is to use composer, which will also 
install zxcvbn-php as a dependency:
  
    composer require drupal/better_passwords

Please see the issue queue for questions about installation without composer.

Enable the module as you would any other Drupal module.

Configuration
=============

Better Passwords should not require configuration for most sites. A few options 
and a lot of information will be available at admin/config/people/passwords.

Differences from the Password Policy module
===========================================

The Password Policy module allows and even encourages site administrators to 
employ configurations that have proven to result in less secure passwords. 
Its modular architecture invites site administrators to develop a password 
policy through a creative process of choosing and configuring options from 
among a wide range of plugins that constrain users' choices.

Better Passwords is designed to implement only recommended security practices. 
Just as site administrators cannot choose to store passwords in plain text, they 
should not be able to force users to reset their passwords periodically, because 
that choice also results in less secure passwords.

Architecture
============

The Better Password module alters the password_confirm element to provide a 
verification step, upon which it checks the password for length and uses the 
zxcvbn-php library to determine its strength. If the password does not meet 
minimum requirements, the user is requested to choose a different password.

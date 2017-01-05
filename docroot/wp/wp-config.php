<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table 
Prefix,
 * Secret Keys, WordPress Language, and ABSPATH. You can find more 
information
 * by visiting {@link http://codex.wordpress.org/Editing_wp-config.php 
Editing
 * wp-config.php} Codex page. You can get the MySQL settings from your 
web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy 
this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'wp_irf2015');

/** MySQL database username */
define('DB_USER', 'irf_user');

/** MySQL database password */
define('DB_PASSWORD', 'ir2205_ft');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link 
https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key 
service}
 * You can change these at any point in time to invalidate all existing 
cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         
'O|N&>BMw2-X[Fwfd,Vu{;BU@G,t|RhSd8j{@L_~8?y$O&62nIlhs|E/.*C.BBl(e');
define('SECURE_AUTH_KEY',  
'^T<H}Hu2=vjHJm4WiVBF*!^pURZ||F8-0vsLySYTRq+{C./?klE+8f@|x-:NhtLL');
define('LOGGED_IN_KEY',    'Q&%~U& 
hSod1Ksd.#--^4K^4e?4(T0aF+VqAYJ{{<+}f)@2=`~lZ4I`lZ/5TU;aR');
define('NONCE_KEY',        
'Lt%^uS%0fddMtgk-:Vn|b].;&N!Mlt:191<;KGQ6uDA}6/WwgTD1[9_!E.6FFZ]A');
define('AUTH_SALT',        'MJO)):MDBs;Rqqe_M)h7M/fx]e+p&T2 
-Pggb2LB~0)/1h!wXG{qls5[yKmFr=.}');
define('SECURE_AUTH_SALT', 
'i49#ID_Cmu3s#>+cY>r$OkG|p?{[b_{F[LMR~H;7V0Lw)4+=f9CB1;3G|%Ie{uf3');
define('LOGGED_IN_SALT',   
'9l0ea5BF-HJ-//=M`tN|:ihIYH3KAy#w=<u,+~sevjB>wE<J)~EJ3486S#HZ~?oK');
define('NONCE_SALT',       
'h}1w%/]dkJl`9R|)BLKsO8#qD_8ixH^TjH,!rm|qEUD?NJN# J^46D`f}:Vo0OY7');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each 
a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * WordPress Localized Language, defaults to English.
 *
 * Change this to localize WordPress. A corresponding MO file for the 
chosen
 * language must be installed to wp-content/languages. For example, 
install
 * de_DE.mo to wp-content/languages and set WPLANG to 'de_DE' to enable 
German
 * language support.
 */
define('WPLANG', '');

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during 
development.
 * It is strongly recommended that plugin and theme developers use 
WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');


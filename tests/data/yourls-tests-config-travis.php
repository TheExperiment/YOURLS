<?php

/**
 * YOURLS Config for Travis
 *
 * @link https://travis-ci.org/YOURLS/YOURLS
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

if( getenv( 'TRAVIS' ) !== true ) {
    die( 'Not in Travis' );
}

/*** Code base and URL of that code base */
define( 'YOURLS_ABSPATH', '/home/travis/build/YOURLS/YOURLS' );
    
define( 'YOURLS_SITE', 'http://localhost/YOURLS' );

/*** MySQL settings */
define( 'YOURLS_DB_USER', 'root' );
define( 'YOURLS_DB_PASS', '' );
define( 'YOURLS_DB_NAME', 'yourls_tests' );
define( 'YOURLS_DB_HOST', 'localhost' );

/*** Site options */
define( 'YOURLS_LANG', 'fr_FR' ); 
    
define( 'YOURLS_PHP_BIN', 'php' );

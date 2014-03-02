<?php

/**
 * Current Environment Analyzer
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Configuration;

class Environment {

    /**
     * Get current version & db version as stored in the options DB. Prior to 1.4 there's no option table.
     *
     */
    public function current_version_from_sql() {
        $currentver = get_option( 'version' );
        $currentsql = get_option( 'db_version' );

        // Values if version is 1.3
        if( !$currentver )
            $currentver = '1.3';
        if( !$currentsql )
            $currentsql = '100';

        return array( $currentver, $currentsql);
    }

    /**
     * Check if the server seems to be running on Windows. Not exactly sure how reliable this is.
     *
     */
    public function is_windows() {
        return defined( 'DIRECTORY_SEPARATOR' ) && DIRECTORY_SEPARATOR == '\\';
    }

    /**
     * Check if server is an Apache
     *
     */
    public function is_apache() {
        if( !array_key_exists( 'SERVER_SOFTWARE', $_SERVER ) )

            return false;
        return (
           strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false
        || strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== false
        );
    }

    /**
     * Check if server is running IIS
     *
     */
    public function is_iis() {
        return ( array_key_exists( 'SERVER_SOFTWARE', $_SERVER ) ? ( strpos( $_SERVER['SERVER_SOFTWARE'], 'IIS' ) !== false ) : false );
    }

    /**
     * Get DB version
     *
     * The regex removes everything that's not a number at the start of the string, or remove anything that's not a number and what
     * follows after that.
     *   'omgmysql-5.5-ubuntu-4.20' => '5.5'
     *   'mysql5.5-ubuntu-4.20'     => '5.5'
     *   '5.5-ubuntu-4.20'          => '5.5'
     *   '5.5-beta2'                => '5.5'
     *   '5.5'                      => '5.5'
     *
     * @since 1.7
     * @return string sanitized DB version
     */
    public function database_version() {
        global $ydb;

        return preg_replace( '/(^[^0-9]*)|[^0-9.].*/', '', $ydb->mysql_version() );
    }

    /**
     * Check an PHP version
     *
     * @param string $version PHP version wanted
     */
    public function check_php_version( $vesion ) {
        return ( version_compare( $vesion, phpversion() ) <= 0 );
    }

    /**
     * Check an Apache version
     *
     * @param string $version Apache version wanted
     */
    public function check_apache_version( $vesion ) {
        return ( version_compare( $vesion, apache_get_version() ) <= 0 );
    }

}

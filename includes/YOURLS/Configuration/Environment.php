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
     * Get current version & db version as stored in the options DB.
     *
     */
    public static function current_version() {
        $current_ver = Option::version;
        $current_sql = Option::db_version;

        return array( $current_ver, $current_sql);
    }

    /**
     * Check if the server seems to be running on Windows
     *
     * @return bool True if the server run Windows
     */
    public static function is_windows() {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Check if server is running Apache
     *
     * @return bool True if the server run Apache
     */
    public static function is_apache() {
        if( !array_key_exists( 'SERVER_SOFTWARE', $_SERVER ) ) {
            return false;
        }

        return strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false
            || strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== false;
    }

    /**
     * Check if server is running IIS
     *
     * @return bool True if the server run IIS
     */
    public static function is_iis() {
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
     * @todo New databases model
     */
    public static function database_version() {
        global $ydb;

        return preg_replace( '/(^[^0-9]*)|[^0-9.].*/', '', $ydb->mysql_version() );
    }

    /**
     * Check an PHP version
     *
     * @param string $version PHP version wanted
     * @return bool True if the current PHP version is correct
     */
    public static function check_php_version( $vesion ) {
        return version_compare( $vesion, phpversion() ) <= 0;
    }

    /**
     * Check an Apache version
     *
     * @param string $version Apache version wanted
     * @return bool True if the current Apache version is correct
     */
    public static function check_apache_version( $vesion ) {
        return version_compare( $vesion, apache_get_version() ) <= 0;
    }

}

<?php

/**
 * Current Configuration Analyzer
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Configuration;

use YOURLS\Extensions\Filters;

class Configuration {

    /**
     * Get current version & db version as stored in the options DB.
     *
     */
    public static function current_version() {
        $current_ver = Options::get( 'version' );
        $current_sql = Options::get( 'db_version' );

        return array( $current_ver, $current_sql);
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

    /**
     *
     * @since 2.0
     * @todo Review and PHPDoc
     */
    public static function is( $config ) {
        if ( Options::is_set( $config ) && is_bool( Options::get( $config ) ) ) {
            return Options::get( $config );
        }
        if ( method_exists( $this, 'is_' . $config ) ) {
            return call_user_func(array( $this, 'is_' . $config ) );
        }

        return false;
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
     * Check if an upgrade is needed
     *
     */
    public static function is_upgrade_needed() {
        // check YOURLS_DB_VERSION exist && match values stored in YOURLS_DB_TABLE_OPTIONS
        list( $current_ver, $current_sql ) = Environment::current_version();

        return $current_sql < YOURLS_DB_VERSION;
    }

    /**
     * Determine if the current page is private
     *
     */
    public static function is_privated() {
        $private = false;

        if ( defined( 'YOURLS_PRIVATE' ) && YOURLS_PRIVATE == true ) {

            // Allow overruling for particular pages:

            // API
            if( self::is( 'api' ) ) {
                if( !defined( 'YOURLS_PRIVATE_API' ) || YOURLS_PRIVATE_API != false )
                    $private = true;

                // Infos
            } elseif( self::is( 'infos' ) ) {
                if( !defined( 'YOURLS_PRIVATE_INFOS' ) || YOURLS_PRIVATE_INFOS !== false )
                    $private = true;

                // Others
            } else {
                $private = true;
            }

        }

        return Filters::apply_filter( 'is_private', $private );
    }

    /**
     * Check if YOURLS is installed
     *
     * @todo Review completely this function with a try catch when connecting database
     */
    public static function is_installed() {
        global $ydb;
        $is_installed = ( property_exists( $ydb, 'installed' ) && $ydb->installed == true );

        return $is_installed;
    }

    /**
     * Check if we'll need interface display function (i.e. not API or redirection)
     *
     */
    public static function is_interface() {
        return self::is( 'api' ) || self::is( 'go' );
    }

    /**
     * Check if current session is valid and secure as configured
     *
     */
    public static function is_public_or_logged() {
        return !is_privated() || defined( 'YOURLS_USER' );
    }

    /**
     * Allow several short URLs for the same long URL ?
     *
     */
    public static function allow_duplicate_longurls() {
        // special treatment if API to check for WordPress plugin requests
        if( self::is( 'api' ) ) {
            if ( isset( $_REQUEST[ 'source' ] ) && $_REQUEST[ 'source' ] == 'plugin' ) {
                return false;
            }
        }

        return defined( 'UNIQUE_URLS' ) && UNIQUE_URLS == false;
    }

    /**
     * Check for maintenance mode. If yes, die. See maintenance_mode(). Stolen from WP.
     *
     * @todo Rewrite and fix die
     */
    public function check_maintenance_mode() {

        $file = YOURLS_ABSPATH . '/.maintenance' ;
        if ( !file_exists( $file ) || is_upgrading() || is_installing() )
            return;

        global $maintenance_start;

        include_once( $file );
        // If the $maintenance_start timestamp is older than 10 minutes, don't die.
        if ( ( time() - $maintenance_start ) >= 600 )
            return;

        // Use any /user/maintenance.php file
        if( file_exists( YOURLS_USERDIR.'/maintenance.php' ) ) {
            include_once( YOURLS_USERDIR.'/maintenance.php' );
            die();
        }

        // https://www.youtube.com/watch?v=Xw-m4jEY-Ns
        $title   = _( 'Service temporarily unavailable' );
        $message = _( 'Our service is currently undergoing scheduled maintenance.' ) . "</p><p>" .
        _( 'Things should not last very long, thank you for your patience and please excuse the inconvenience' );
        die( $message/*, $title , 503 */);

    }

    /**
     * Check if SSL is used, returns bool. Stolen from WP.
     *
     */
    public function is_ssl() {
        $is_ssl = false;
        if ( isset( $_SERVER['HTTPS'] ) ) {
            if ( 'on' == strtolower( $_SERVER['HTTPS'] ) )
                $is_ssl = true;
            if ( '1' == $_SERVER['HTTPS'] )
                $is_ssl = true;
        } elseif ( isset( $_SERVER['SERVER_PORT'] ) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
            $is_ssl = true;
        }

        return Filters::apply_filter( 'is_ssl', $is_ssl );
    }

}

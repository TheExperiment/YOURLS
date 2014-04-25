<?php

/**
 * Current Configuration Analyzer
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Configuration;

use YOURLS\Extensions\Filters;

class Configuration {

    /**
     *
     * @since 2.0
     * @todo Review and PHPDoc
     */
    public static function is( $config ) {
        if ( defined( 'YOURLS_' . strtoupper( $config ) )
            && is_bool( constant( 'YOURLS_' . strtoupper( $config ) ) ) ) {
            return constant( 'YOURLS_' . strtoupper( $config ) );
        }
        if ( method_exists( $this, 'is_' . $config ) ) {
            return call_user_func(array( $this, 'is_' . $config ) );
        }

        return false;
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

}

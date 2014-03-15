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

class Configuration {

    /**
     * Check if an upgrade is needed
     *
     */
    public function upgrade_is_needed() {
        // check YOURLS_DB_VERSION exist && match values stored in YOURLS_DB_TABLE_OPTIONS
        list( $currentver, $currentsql ) = get_current_version_from_sql();
        if( $currentsql < YOURLS_DB_VERSION )

            return true;

        return false;
    }

    /**
     * Determine if the current page is private
     *
     */
    public function is_private() {
        $private = false;

        if ( defined('YOURLS_PRIVATE') && YOURLS_PRIVATE == true ) {

            // Allow overruling for particular pages:

            // API
            if( is_API() ) {
                if( !defined('YOURLS_PRIVATE_API') || YOURLS_PRIVATE_API != false )
                    $private = true;

                // Infos
            } elseif( is_infos() ) {
                if( !defined('YOURLS_PRIVATE_INFOS') || YOURLS_PRIVATE_INFOS !== false )
                    $private = true;

                // Others
            } else {
                $private = true;
            }

        }

        return Filters::apply_filter( 'is_private', $private );
    }

    /**
     * Allow several short URLs for the same long URL ?
     *
     */
    public function allow_duplicate_longurls() {
        // special treatment if API to check for WordPress plugin requests
        if( is_API() ) {
            if ( isset($_REQUEST['source']) && $_REQUEST['source'] == 'plugin' )
                return false;
        }

        return ( defined( 'UNIQUE_URLS' ) && UNIQUE_URLS == false );
    }

    /**
     * Check if YOURLS is installing
     *
     * @return bool
     * @since 1.6
     */
    public function is_installing() {
        $installing = defined( 'INSTALLING' ) && INSTALLING == true;

        return $installing;
    }

    /**
     * Check if YOURLS is upgrading
     *
     * @return bool
     * @since 1.6
     */
    public function is_upgrading() {
        $upgrading = defined( 'UPGRADING' ) && UPGRADING == true;

        return Filters::apply_filter( 'is_upgrading', $upgrading );
    }

    /**
     * Check if YOURLS is installed
     *
     * Checks property $ydb->installed that is created by get_all_options()
     */
    public function is_installed() {
        global $ydb;
        $is_installed = ( property_exists( $ydb, 'installed' ) && $ydb->installed == true );

        return $is_installed;
    }

    /**
     * Check if we're in API mode. Returns bool
     *
     */
    public function is_API() {
        if ( defined( 'API' ) && API == true )
            return true;
        return false;
    }

    /**
     * Check if we're in Ajax mode. Returns bool
     *
     */
    public function is_Ajax() {
        if ( defined( 'AJAX' ) && AJAX == true )
            return true;
        return false;
    }

    /**
     * Check if we're in GO mode (yourls-go.php). Returns bool
     *
     */
    public function is_GO() {
        if ( defined( 'GO' ) && GO == true )
            return true;
        return false;
    }

    /**
     * Check if we're displaying stats infos (yourls-infos.php). Returns bool
     *
     */
    public function is_infos() {
        if ( defined( 'INFOS' ) && INFOS == true )
            return true;
        return false;
    }

    /**
     * Check if we'll need interface display function (ie not API or redirection)
     *
     */
    public function has_interface() {
        if( is_API() or is_GO() )

            return false;
        return true;
    }

    /**
     * Check if we're in the admin area. Returns bool
     *
     */
    public function is_admin() {
        if ( defined( 'YOURLS_ADMIN' ) && YOURLS_ADMIN == true )
            return true;
        return false;
    }

    /**
     * Check if current session is valid and secure as configurated
     *
     */
    public function is_public_or_logged() {
        if ( !is_private() )
            return true;
        else
            return defined( 'YOURLS_USER' );
    }

    /**
     * Check if SSL is required. Returns bool.
     *
     */
    public function needs_ssl() {
        if ( defined( 'YOURLS_ADMIN_SSL' ) && YOURLS_ADMIN_SSL == true )
            return true;
        return false;
    }

    /**
     * Check for maintenance mode. If yes, die. See maintenance_mode(). Stolen from WP.
     *
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

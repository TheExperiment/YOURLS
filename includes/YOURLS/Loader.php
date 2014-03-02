<?php

/**
 * YOURLS Loader
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS;

/**
 * YOURLS version
 */
const VERSION = '2.0-alpha';

use YOURLS\Configuration\Configuration;
use YOURLS\Configuration\Options;
use YOURLS\HTTP\Redirect;
use YOURLS\Extensions\Filters;

/**
 * Summary of Loader
 */
class Loader {

    /**
     * Summary of __construct
     * @param mixed $config
     */
    public function __construct( $config = null ) {
        if ( file_exists( str_replace( '\\', '/', $config ) ) ) {
            define( 'YOURLS_CONFIGFILE', str_replace( '\\', '/', $config ) );
        } elseif ( file_exists( dirname( dirname( __DIR__ ) ) . '/user/config.php' ) ) {
            // config.php in /user/
            define( 'YOURLS_CONFIGFILE', str_replace( '\\', '/', dirname( dirname( __DIR__ ) ) ) . '/user/config.php' );
        } elseif ( file_exists( dirname( __DIR__ ) . '/config.php' ) ) {
            // config.php in /includes/
            define( 'YOURLS_CONFIGFILE', str_replace( '\\', '/', dirname( __DIR__ ) ) . '/config.php' );
        } else {
            // config.php not found :(
            throw new YOURLS_Exception( '<p class="error">Cannot find <code>config.php</code>.</p><p>Please read the <a href="../docs/#install">documentation</a> to learn how to install YOURLS</p>' );
        }
        require_once YOURLS_CONFIGFILE;

        $config = new Configuration();

        // Check if config.php was properly updated for 1.4
        if( !defined( 'YOURLS_DB_PREFIX' ) )
            throw new YOURLS_Exception( '<p class="error">Your <code>config.php</code> does not contain all the required constant definitions.</p><p>Please check <code>config-sample.php</code> and update your config accordingly, there are new stuffs!</p>' );

        // Define core constants that have not been user defined in config.php
        $yourls_definitions = array(
            // physical path of YOURLS root
            'YOURLS_ABSPATH'             => str_replace( '\\', '/', dirname( dirname( __DIR__ ) ) ),
            // physical path of includes directory
            'YOURLS_INC'                 => array( 'YOURLS_ABSPATH', '/includes' ),

            // physical path and url of asset directory
            'YOURLS_ASSETDIR'            => array( 'YOURLS_ABSPATH', '/assets' ),
            'YOURLS_ASSETURL'            => array( 'YOURLS_SITE', '/assets' ),

            // physical path and url of user directory
            'YOURLS_USERDIR'             => array( 'YOURLS_ABSPATH', '/user' ),
            'YOURLS_USERURL'             => array( 'YOURLS_SITE', '/user' ),
            // physical path of translations directory
            'YOURLS_LANG_DIR'            => array( 'YOURLS_USERDIR', '/languages' ),
            // physical path and url of plugins directory
            'YOURLS_PLUGINDIR'           => array( 'YOURLS_USERDIR', '/plugins' ),
            'YOURLS_PLUGINURL'           => array( 'YOURLS_USERURL', '/plugins' ),
            // physical path and url of themes directory
            'YOURLS_THEMEDIR'            => array( 'YOURLS_USERDIR', '/themes' ),
            'YOURLS_THEMEURL'            => array( 'YOURLS_USERURL', '/themes' ),
            // physical path of pages directory
            'YOURLS_PAGEDIR'             => array( 'YOURLS_USERDIR', '/pages' ),

            // admin pages location
            'YOURLS_ADMIN_LOCATION'           => 'admin',

            // table to store URLs
            'YOURLS_DB_TABLE_URL'        => array( 'YOURLS_DB_PREFIX', 'url' ),
            // table to store options
            'YOURLS_DB_TABLE_OPTIONS'    => array( 'YOURLS_DB_PREFIX', 'options' ),
            // table to store hits, for stats
            'YOURLS_DB_TABLE_LOG'        => array( 'YOURLS_DB_PREFIX', 'log' ),

            // minimum delay in sec before a same IP can add another URL. Note: logged in users are not throttled down.
            'YOURLS_FLOOD_DELAY_SECONDS' => 15,
            // comma separated list of IPs that can bypass flood check.
            'YOURLS_FLOOD_IP_WHITELIST'  => '',
            'YOURLS_COOKIE_LIFE'         => 60*60*24*7,
            // life span of a nonce in seconds
            'YOURLS_NONCE_LIFE'          => 43200, // 3600 *,12

            // if set to true, disable stat logging (no use for it, too busy servers, ...)
            'YOURLS_NOSTATS'             => false,
            // if set to true, force https:// in the admin area
            'YOURLS_ADMIN_SSL'           => false,
            // if set to true, verbose debug infos. Will break things. Don't enable.
            'YOURLS_DEBUG'               => false,
        );

        foreach ( $yourls_definitions as $const_name => $const_default_value ) {
            if( !defined( $const_name ) ) {
                if ( is_array( $const_default_value ) ) {
                    define( $const_name, constant( $const_default_value[0] ) . $const_default_value[1] );
                } else {
                    define( $const_name, $const_default_value );
                }
            }
        }

        // Error reporting
        if( defined( 'YOURLS_DEBUG' ) && YOURLS_DEBUG == true ) {
            error_reporting( -1 );
        } else {
            error_reporting( E_ERROR | E_PARSE );
        }

        // Check if we are in maintenance mode - if yes, it will die here.
        $config->check_maintenance_mode();

        // Fix REQUEST_URI for IIS
        //$funct->fix_request_uri();

        // If request for an admin page is http:// and SSL is required, redirect
        if( $config->is_admin() && $config->needs_ssl() && !$config->is_ssl() ) {
            if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
                Redirect::redirect( preg_replace( '|^http://|', 'https://', $_SERVER['REQUEST_URI'] ) );
                exit();
            } else {
                Redirect::redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
                exit();
            }
        }

        // Create the YOURLS object $ydb that will contain everything we globally need
        global $ydb;

        // Allow drop-in replacement for the DB engine
        if( file_exists( YOURLS_USERDIR . '/db.php' ) ) {
            require_once YOURLS_USERDIR . '/db.php';
        } else {
            $db = new Database\Database;
            $db->connect();
        }

        // Allow early inclusion of a cache layer
        if( file_exists( YOURLS_USERDIR . '/cache.php' ) )
            require_once YOURLS_USERDIR . '/cache.php';

        // Read options right from start
        $options = new Options;

        $filters = new Filters;
        // Core now loaded
        $filters->do_action( 'init' ); // plugins can't see this, not loaded yet

        // Check if need to redirect to install procedure
        if( !$config->is_installed() && !$config->is_installing() ) {
            Redirect::redirect( YOURLS_SITE .'/yourls-install.php', 302 );
        }

        // Check if upgrade is needed (bypassed if upgrading or installing)
        if ( !$config->is_upgrading() && !$config->is_installing() ) {
            if ( $config->upgrade_is_needed() ) {
                Redirect::redirect( new HTTP\URL( admin_url( 'upgrade' ), 302 ));
            }
        }

        $plug = new Extensions\Plugins;
        // Init all plugins
        $plug->load_plugins();
        $filters->do_action( 'plugins_loaded' );

        // Init themes if applicable
        if( yourls_has_interface() ) {
            yourls_init_theme();
            yourls_do_action( 'init_theme' );
        }

        // Is there a new version of YOURLS ?
        if( yourls_is_installed() && !yourls_is_upgrading() ) {
            yourls_new_core_version_notice();
        }

        if( yourls_is_admin() ) {
            yourls_do_action( 'admin_init' );
        }
    }
    
    /**
     * Summary of run
     */
    public function run() {
        // Get request in YOURLS base (eg in 'http://site.com/yourls/abcd' get 'abdc')
        $request = yourls_get_request();

        // Admin:
        if( preg_match( "@^".YOURLS_ADMIN_LOCATION."/(([a-zA-Z\-]+)(\.php)?)?$@", $request, $matches ) ) {
            $page = YOURLS_INC.'/admin/';
            $page .= ( isset( $matches[2] ) && $matches[2] ) ? $matches[2].'.php' : 'index.php';
            if ( file_exists( $page ) ) {
                require_once( $page );
                exit;
            }
        }

        // Make valid regexp pattern from authorized charset in keywords
        $pattern = yourls_make_regexp_pattern( yourls_get_shorturl_charset() );

        // Now load required template and exit

        yourls_do_action( 'pre_load_template', $request );

        // At this point, $request is not sanitized. Sanitize in loaded template.

        // Redirection:
        if( preg_match( "@^([$pattern]+)/?$@", $request, $matches ) ) {
            $go = new HTTP\Redirect;
            $keyword = isset( $matches[1] ) ? $matches[1] : '';
            $keyword = yourls_sanitize_keyword( $keyword );
            yourls_do_action( 'load_template_go', $keyword );
            require_once( YOURLS_ABSPATH.'/yourls-go.php' );
            exit;
        }

        // Stats:
        if( preg_match( "@^([$pattern]+)\+(all)?/?$@", $request, $matches ) ) {
            $keyword = isset( $matches[1] ) ? $matches[1] : '';
            $keyword = yourls_sanitize_keyword( $keyword );
            $aggregate = isset( $matches[2] ) ? (bool)$matches[2] && yourls_allow_duplicate_longurls() : false;
            yourls_do_action( 'load_template_infos', $keyword );
            require_once( YOURLS_ABSPATH.'/yourls-infos.php' );
            exit;
        }

        // Prefix-n-Shorten sends to bookmarklet (doesn't work on Windows)
        if( preg_match( "@^[a-zA-Z]+://.+@", $request, $matches ) ) {
            $url = yourls_sanitize_url( $matches[0] );
            if( $parse = yourls_get_protocol_slashes_and_rest( $url, array( 'up', 'us', 'ur' ) ) ) {
                yourls_do_action( 'load_template_redirect_admin', $url );
                $parse = array_map( 'rawurlencode', $parse );
                // Redirect to /admin/index.php?up=<url protocol>&us=<url slashes>&ur=<url rest>
                yourls_redirect( yourls_add_query_arg( $parse , yourls_admin_url( 'index.php' ) ), 302 );
                exit;
            }
        }

        // Past this point this is a request the loader could not understand
        yourls_do_action( 'loader_failed', $request );
        yourls_redirect( YOURLS_SITE, 302 );
        exit;
    }

    /**
     * Shutdown function, runs just before PHP shuts down execution.
     *
     */
    public function __destruct() {
        //do_action( 'shutdown' );
    }

}

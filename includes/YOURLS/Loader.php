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

use YOURLS\Configuration\Options;
use YOURLS\Network\Redirect;
use YOURLS\Extensions\Filters;
use YOURLS\Database\Database;

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

        new Options( $config );

        // Error reporting
        if( Configuration::is( 'debug' ) ) {
            error_reporting( -1 );
        } else {
            error_reporting( E_ERROR | E_PARSE );
        }

        // Check if we are in maintenance mode - if yes, it will die here.
        Configuration::check_maintenance_mode();

        // Fix REQUEST_URI for IIS
        Functions::fix_request_uri();

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
            Database::connect();
        }

        // Allow early inclusion of a cache layer
        if( file_exists( YOURLS_USERDIR . '/cache.php' ) )
            require_once YOURLS_USERDIR . '/cache.php';

        $filters = new Filters;
        // Core now loaded
        Filters::do_action( 'init' ); // plugins can't see this, not loaded yet

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

        // Init all plugins
        Extensions\Plugins::load();
        Filters::do_action( 'plugins_loaded' );

        // Init themes if applicable
        if( yourls_has_interface() ) {
            yourls_init_theme();
            Filters::do_action( 'init_theme' );
        }

        // Is there a new version of YOURLS ?
        if( yourls_is_installed() && !yourls_is_upgrading() ) {
            yourls_new_core_version_notice();
        }

        if( yourls_is_admin() ) {
            Filters::do_action( 'admin_init' );
        }
    }

    /**
     * Summary of run
     */
    public function run() {
        // Get request in YOURLS base (eg in 'http://site.com/yourls/abcd' get 'abdc')
        $request = yourls_get_request();

        // API
        if ( preg_match( "@^".YOURLS_API_LOCATION."/(.*)?$@", $request, $matches ) ) {
            new API\Request;
        }

        // Admin:
        if( preg_match( "@^".YOURLS_ADMIN_LOCATION."/(([a-zA-Z\-]+)(\.php)?)?$@", $request, $matches ) ) {
            $page = YOURLS_INC.'/admin/';
            $page .= ( isset( $matches[2] ) && $matches[2] ) ? $matches[2].'.php' : 'index.php';
            if ( file_exists( $page ) ) {
                require_once( $page );
            }
        }

        // Make valid regexp pattern from authorized charset in keywords
        $pattern = yourls_make_regexp_pattern( yourls_get_shorturl_charset() );

        // Now load required template and exit

        Filters::do_action( 'pre_load_template', $request );

        // At this point, $request is not sanitized. Sanitize in loaded template.

        // Redirection:
        if( preg_match( "@^([$pattern]+)/?$@", $request, $matches ) ) {
            $keyword = isset( $matches[1] ) ? $matches[1] : '';
            $keyword = yourls_sanitize_keyword( $keyword );
            Filters::do_action( 'load_template_go', $keyword );
            $this->pass();
        }

        // Stats:
        if( preg_match( "@^([$pattern]+)\+(all)?/?$@", $request, $matches ) ) {
            $keyword = isset( $matches[1] ) ? $matches[1] : '';
            $keyword = yourls_sanitize_keyword( $keyword );
            $aggregate = isset( $matches[2] ) ? (bool)$matches[2] && yourls_allow_duplicate_longurls() : false;
            Filters::do_action( 'load_template_infos', $keyword );
            require_once( YOURLS_ABSPATH.'/yourls-infos.php' );
        }

        // Prefix-n-Shorten sends to bookmarklet (doesn't work on Windows)
        if( preg_match( "@^[a-zA-Z]+://.+@", $request, $matches ) ) {
            $url = yourls_sanitize_url( $matches[0] );
            if( $parse = yourls_get_protocol_slashes_and_rest( $url, array( 'up', 'us', 'ur' ) ) ) {
                Filters::do_action( 'load_template_redirect_admin', $url );
                $parse = array_map( 'rawurlencode', $parse );
                // Redirect to /admin/index.php?up=<url protocol>&us=<url slashes>&ur=<url rest>
                Redirect::redirect( yourls_add_query_arg( $parse , yourls_admin_url( 'index.php' ) ), 302 );
            }
        }

        // Past this point this is a request the loader could not understand
        Filters::do_action( 'loader_failed', $request );
        Redirect::redirect( YOURLS_SITE, 302 );
    }

    public function pass() {
        // First possible exit:
        if ( !isset( $keyword ) ) {
            yourls_do_action( 'redirect_no_keyword' );
            yourls_redirect( YOURLS_SITE, 301 );
        }

        // Get URL From Database
        $url = yourls_get_keyword_longurl( $keyword );

        // URL found
        if ( !empty( $url ) ) {
            yourls_do_action( 'redirect_shorturl', $url, $keyword );

            // Update click count in main table
            $update_clicks = yourls_update_clicks( $keyword );

            // Update detailed log for stats
            $log_redirect = yourls_log_redirect( $keyword );

            yourls_redirect( $url, 301 );

        // URL not found. Either reserved, or page, or doesn't exist
        } else {

            // Do we have a page?
            if ( file_exists( YOURLS_PAGEDIR . "/$keyword.php" ) ) {
                yourls_page( $keyword );

            // Either reserved id, or no such id
            } else {
                yourls_do_action( 'redirect_keyword_not_found', $keyword );
                yourls_redirect( YOURLS_SITE, 302 ); // no 404 to tell browser this might change, and also to not pollute logs
            }
        }
    }

    /**
     * Shutdown function, runs just before PHP shuts down execution.
     *
     */
    public function __destruct() {
        Filters::do_action( 'shutdown' );
    }

}

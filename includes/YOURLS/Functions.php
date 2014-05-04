<?php

/**
 * Functions Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS;

/**
 * Summary of Functions
 *
 * @deprecated Too much methods!
 * @todo We have to separate methods!
 */
class Functions {

    /**
     * Is a URL a short URL? Accept either 'http://sho.rt/abc' or 'abc'
     *
     */
    public function is_shorturl( $shorturl ) {
        // TODO: make sure this function evolves with the feature set.

        $is_short = false;

        // Is $shorturl a URL (http://sho.rt/abc) or a keyword (abc) ?
        if( get_protocol( $shorturl ) ) {
            $keyword = get_relative_url( $shorturl );
        } else {
            $keyword = $shorturl;
        }

        // Check if it's a valid && used keyword
        if( $keyword && $keyword == sanitize_string( $keyword ) && keyword_is_taken( $keyword ) ) {
            $is_short = true;
        }

        return Filters::apply_filter( 'is_shorturl', $is_short, $shorturl );
    }



    /**
     * Return a time-dependent string for nonce creation
     *
     */
    public function tick() {
        return ceil( time() / YOURLS_NONCE_LIFE );
    }


    /**
     * Fix $_SERVER['REQUEST_URI'] variable for various setups. Stolen from WP.
     *
     */
    public static function fix_request_uri() {

        $default_server_values = array(
            'SERVER_SOFTWARE' => '',
            'REQUEST_URI' => '',
        );
        $_SERVER = array_merge( $default_server_values, $_SERVER );

        // Fix for IIS when running with PHP ISAPI
        if ( empty( $_SERVER['REQUEST_URI'] ) || ( php_sapi_name() != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {

            // IIS Mod-Rewrite
            if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
            }
            // IIS Isapi_Rewrite
            else if ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
                $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
            } else {
                // Use ORIG_PATH_INFO if there is no PATH_INFO
                if ( !isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) )
                    $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

                // Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
                if ( isset( $_SERVER['PATH_INFO'] ) ) {
                    if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
                        $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                    else
                        $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
                }

                // Append the query string if it exists and isn't null
                if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
                    $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
                }
            }
        }
    }

    /**
     * Auto detect custom favicon in /user directory, fallback to YOURLS favicon, and echo/return its URL
     *
     */
    public function favicon( $echo = true ) {
        static $favicon = null;
        if( $favicon !== null )

            return $favicon;

        // search for favicon.(ico|png|gif)
        foreach( array( 'png', 'ico', 'gif' ) as $ext ) {
            if( file_exists( YOURLS_USERDIR. '/favicon.' . $ext ) ) {
                $favicon = site_url( false, YOURLS_USERURL . '/favicon.' . $ext );
                break;
            }
        }
        if ( $favicon === null )
            $favicon = site_url( false, YOURLS_ASSETURL . '/img/favicon.ico' );

        if( $echo )
            echo '<link rel="shortcut icon" href="'. $favicon . '">';
        else
            return $favicon;
    }

    /**
     * Return current admin page, or null if not an admin page
     *
     * @return mixed string if admin page, null if not an admin page
     * @since 1.6
     */
    public function current_admin_page() {
        if( is_admin() ) {
            $current = substr( get_request(), 6 );
            if( $current === false )
                $current = 'index'; // if current page is http://sho.rt/admin/ instead of http://sho.rt/admin/index

            return $current;
        }

        return null;
    }

    /**
     * Marks a function as deprecated and informs when it has been used. Stolen from WP.
     *
     * There is a hook deprecated_function that will be called that can be used
     * to get the backtrace up to what file and function called the deprecated
     * function.
     *
     * The current behavior is to trigger a user error if YOURLS_DEBUG is true.
     *
     * This function is to be used in every function that is deprecated.
     *
     * @since 1.6
     * @uses Filters::do_action() Calls 'deprecated_function' and passes the function name, what to use instead,
     *   and the version the function was deprecated in.
     * @uses Filters::apply_filters() Calls 'deprecated_function_trigger_error' and expects boolean value of true to do
     *   trigger or false to not trigger error.
     *
     * @param string $function The function that was called
     * @param string $version The version of WordPress that deprecated the function
     * @param string $replacement Optional. The function that should have been called
     */
    public function deprecated_function( $function, $version, $replacement = null ) {

        Filters::do_action( 'deprecated_function', $function, $replacement, $version );

        // Allow plugin to filter the output error trigger
        if ( YOURLS_DEBUG && Filters::apply_filters( 'deprecated_function_trigger_error', true ) ) {
            if ( ! is_null( $replacement ) )
                trigger_error( sprintf( _( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ), $function, $version, $replacement ) );
            else
                trigger_error( sprintf( _( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ), $function, $version ) );
        }
    }

}

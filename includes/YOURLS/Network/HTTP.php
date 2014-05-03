<?php

/**
 * HTTP Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Network;

/**
 * Class that relate to HTTP requests
 *
 * On functions using the 3rd party library Requests:
 * Thir goal here is to provide convenient wrapper functions to the Requests library. There are
 * 2 types of functions for each METHOD, where METHOD is 'get' or 'post' (implement more as needed)
 *     - METHOD() :
 *         Return a complete Response object (with ->body, ->headers, ->status_code, etc...) or
 *         a simple string (error message)
 *     - METHOD_body() :
 *         Return a string (response body) or null if there was an error
 *
 * @since 1.7
 */
class HTTP extends Request {

    /**
     * Perform a GET request, return body or null if there was an error
     *
     * @since 1.7
     * @see request
     * @return mixed String (page body) or null if error
     */
    public function get_body( $url, $headers = array(), $data = array(), $options = array() ) {
        $return = $this->get( $url, $headers, $data, $options );

        return isset( $return->body ) ? $return->body : null;
    }

    /**
     * Perform a POST request, return body
     *
     * Wrapper for request()
     *
     * @since 1.7
     * @see request
     * @return mixed String (page body) or null if error
     */
    public function post_body( $url, $headers = array(), $data = array(), $options = array() ) {
        $return = $this->post( $url, $headers, $data, $options );

        return isset( $return->body ) ? $return->body : null;
    }

    /**
     * Check if a proxy is defined for HTTP requests
     *
     * @uses PROXY
     * @since 1.7
     * @return bool true if a proxy is defined, false otherwise
     */
    public function proxy_is_defined() {
        return Filters::apply_filter( 'http_proxy_is_defined', defined( 'PROXY' ) );
    }

    /**
     * Default HTTP requests options for YOURLS
     *
     * For a list of all available options, see function request() in /includes/Requests/Requests.php
     *
     * @uses PROXY
     * @uses PROXY_YOURLS_USERNAME
     * @uses PROXY_PASSWORD
     * @since 1.7
     * @return array Options
     */
    public function default_options() {
        $options = array(
            'timeout'          => Filters::apply_filter( 'http_default_options_timeout', 3 ),
            'useragent'        => $this->user_agent(),
            'follow_redirects' => true,
            'redirects'        => 3,
        );

        if( $this->proxy_is_defined() ) {
            if( defined( 'PROXY_YOURLS_USERNAME' ) && defined( 'PROXY_PASSWORD' ) ) {
                $options['proxy'] = array( PROXY, PROXY_YOURLS_USERNAME, PROXY_PASSWORD );
            } else {
                $options['proxy'] = PROXY;
            }
        }

        return Filters::apply_filter( 'http_default_options', $options );
    }

    /**
     * Whether URL should be sent through the proxy server.
     *
     * Concept stolen from WordPress. The idea is to allow some URLs, including localhost and the YOURLS install itself,
     * to be requested directly and bypassing any defined proxy.
     *
     * @uses PROXY
     * @uses PROXY_BYPASS_HOSTS
     * @since 1.7
     * @param string $url URL to check
     * @return bool true to request through proxy, false to request directly
     */
    public function send_through_proxy( $url ) {

        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_send_through_proxy', null, $url );
        if ( null !== $pre )
            return $pre;

        $check = @parse_url( $url );

        // Malformed URL, can not process, but this could mean ssl, so let through anyway.
        if ( $check === false )
            return true;

        // Self and loopback URLs are considered local (':' is parse_url() host on '::1')
        $home = parse_url( SITE );
        $local = array( 'localhost', '127.0.0.1', '127.1', '[::1]', ':', $home['host'] );

        if( in_array( $check['host'], $local ) )

            return false;

        if ( !defined( 'PROXY_BYPASS_HOSTS' ) )
            return true;

        // Check PROXY_BYPASS_HOSTS
        static $bypass_hosts;
        static $wildcard_regex = false;
        if ( null == $bypass_hosts ) {
            $bypass_hosts = preg_split( '|,\s*|', PROXY_BYPASS_HOSTS );

            if ( false !== strpos( PROXY_BYPASS_HOSTS, '*' ) ) {
                $wildcard_regex = array();
                foreach ( $bypass_hosts as $host )
                    $wildcard_regex[] = str_replace( '\*', '.+', preg_quote( $host, '/' ) );
                $wildcard_regex = '/^(' . implode( '|', $wildcard_regex ) . ')$/i';
            }
        }

        if ( !empty( $wildcard_regex ) )
            return !preg_match( $wildcard_regex, $check['host'] );
        else
            return !in_array( $check['host'], $bypass_hosts );
    }

    /**
     * Perform a HTTP request, return response object
     *
     * @since 1.7
     * @param string $type HTTP request type (GET, POST)
     * @param string $url URL to request
     * @param array $headers Extra headers to send with the request
     * @param array $data Data to send either as a query string for GET requests, or in the body for POST requests
     * @param array $options Options for the request (see /includes/Requests/Requests.php:request())
     * @return object Requests_Response object
     */
    public function request( $type, $url, $headers, $data, $options ) {
        $options = array_merge( $this->default_options(), $options );

        if( $this->proxy_is_defined() && !send_through_proxy( $url ) )
            unset( $options['proxy'] );

        try {
            $result = self::request( $url, $headers, $data, $type, $options );
        }
        catch( Requests_Exception $e ) {
            $result = debug_log( $e->getMessage() . ' (' . $type . ' on ' . $url . ')' );
        }

        return $result;
    }

    /**
     * Return funky user agent string
     *
     * @since 1.5
     * @return string UA string
     */
    public function user_agent() {
        return Filters::apply_filter( 'http_user_agent', 'YOURLS v'.VERSION.' +http://yourls.org/ (running on '.SITE.')' );
    }

    /**
     * Check api.yourls.org if there's a newer version of YOURLS
     *
     * This function collects various stats to help us improve YOURLS. See the blog post about it:
     * http://blog.yourls.org/2014/01/on-yourls-1-7-and-api-yourls-org/
     * Results of requests sent to api.yourls.org are stored in option 'core_version_checks' and is an object
     * with the following properties:
     *    - failed_attempts : number of consecutive failed attempts
     *    - last_attempt    : time() of last attempt
     *    - last_result     : content retrieved from api.yourls.org during previous check
     *    - version_checked : installed YOURLS version that was last checked
     *
     * @since 1.7
     * @return mixed JSON data if api.yourls.org successfully requested, false otherwise
     */
    public function check_core_version() {

        global $ydb, $user_passwords;

        $checks = get_option( 'core_version_checks' );

        // Invalidate check data when YOURLS version changes
        if ( is_object( $checks ) && VERSION != $checks->version_checked ) {
            $checks = false;
        }

        if( !is_object( $checks ) ) {
            $checks = new stdClass;
            $checks->failed_attempts = 0;
            $checks->last_attempt    = 0;
            $checks->last_result     = '';
            $checks->version_checked = VERSION;
        }

        // Config file location ('u' for '/user' or 'i' for '/includes')
        $conf_loc = str_replace( YOURLS_ABSPATH, '', CONFIGFILE );
        $conf_loc = str_replace( '/config.php', '', $conf_loc );
        $conf_loc = ( $conf_loc == '/user' ? 'u' : 'i' );

        // The collection of stuff to report
        $stuff = array(
            // Globally uniquish site identifier
            'md5'                => md5( SITE . YOURLS_ABSPATH ),

            // Install information
            'failed_attempts'    => $checks->failed_attempts,
            'site'        => defined( 'SITE' ) ? SITE : 'unknown',
            'version'     => defined( 'VERSION' ) ? VERSION : 'unknown',
            'php_version'        => phpversion(),
            'mysql_version'      => $ydb->mysql_version(),
            'locale'             => get_locale(),

            // custom DB driver if any, and useful common PHP extensions
            'db_driver'          => defined( 'YOURLS_DB_DRIVER' ) ? YOURLS_DB_DRIVER : 'unset',
            'db_ext_pdo'         => extension_loaded( 'pdo_mysql' ) ? 1 : 0,
            'db_ext_mysql'       => extension_loaded( 'mysql' )     ? 1 : 0,
            'db_ext_mysqli'      => extension_loaded( 'mysqli' )    ? 1 : 0,
            'ext_curl'           => extension_loaded( 'curl' )      ? 1 : 0,

            // Config information
            'num_users'          => count( $user_passwords ),
            'config_location'    => $conf_loc,
            'private'     => defined( 'YOURLS_PRIVATE' ) && YOURLS_PRIVATE ? 1 : 0,
            'unique'      => defined( 'UNIQUE_URLS' ) && UNIQUE_URLS ? 1 : 0,
            'url_convert' => defined( 'URL_CONVERT' ) ? URL_CONVERT : 'unknown',
            'num_active_plugins' => has_active_plugins(),
            'num_pages'          => defined( 'YOURLS_PAGEDIR' ) ? count( (array) glob( YOURLS_PAGEDIR .'/*.php') ) : 0,
        );

        $stuff = Filters::apply_filter( 'version_check_stuff', $stuff );

        // Send it in
        $url = 'http://api.yourls.org/core/version/1.0/';
        if( $this->can_over_ssl() )
            $url = set_url_scheme( $url, 'https' );
        $req = $this->post( $url, array(), $stuff );

        $checks->last_attempt = time();
        $checks->version_checked = VERSION;

        // Unexpected results ?
        if( is_string( $req ) or !$req->success ) {
            $checks->failed_attempts = $checks->failed_attempts + 1;
            update_option( 'core_version_checks', $checks );

            return false;
        }

        // Parse response
        $json = json_decode( trim( $req->body ) );

        if( isset( $json->latest ) && isset( $json->zipurl ) ) {
            // All went OK - mark this down
            $checks->failed_attempts = 0;
            $checks->last_result     = $json;
            update_option( 'core_version_checks', $checks );

            return $json;
        }

        // Request returned actual result, but not what we expected
        return false;
    }

    /**
     * Determine if we want to check for a newer YOURLS version (and check if applicable)
     *
     * Currently checks are performed every 24h and only when someone is visiting an admin page.
     * In the future (1.8?) maybe check with cronjob emulation instead.
     *
     * @since 1.7
     * @return bool true if a check was needed and successfully performed, false otherwise
     */
    public function maybe_check_core_version() {

        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_maybe_check_core_version', null );
        if ( null !== $pre )
            return $pre;

        if( defined( 'NO_VERSION_CHECK' ) && NO_VERSION_CHECK )

            return false;

        if( !is_admin() )

            return false;

        $checks = get_option( 'core_version_checks' );

        /* We don't want to check if :
        - last_result is set (a previous check was performed)
        - and it was less than 24h ago (or less than 2h ago if it wasn't successful)
        - and version checked matched version running
        Otherwise, we want to check.
         */
        if( !empty( $checks->last_result )
            AND
            (
                ( $checks->failed_attempts == 0 && ( ( time() - $checks->last_attempt ) < 24 * 3600 ) )
                OR
                ( $checks->failed_attempts > 0  && ( ( time() - $checks->last_attempt ) <  2 * 3600 ) )
            )
            AND ( $checks->version_checked == VERSION )
        )

            return false;

        // We want to check if there's a new version
        $new_check = check_core_version();

        // Could not check for a new version, and we don't have ancient data
        if( false == $new_check && !isset( $checks->last_result->latest ) )

            return false;

        return true;
    }

    /**
     * Check if server can perform HTTPS requests, return bool
     *
     * @since 1.7.1
     * @return bool whether the server can perform HTTP requests over SSL
     */
    public function can_over_ssl() {
        $ssl_curl = $ssl_socket = false;

        if( function_exists( 'curl_exec' ) ) {
            $curl_version  = curl_version();
            $ssl_curl = ( $curl_version['features'] & CURL_VERSION_SSL );
        }

        if( function_exists( 'stream_socket_client' ) ) {
            $ssl_socket = extension_loaded( 'openssl' ) && function_exists( 'openssl_x509_parse' );
        }

        return ( $ssl_curl OR $ssl_socket );
    }

}

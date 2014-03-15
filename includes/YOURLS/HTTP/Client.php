<?php

/**
 * HTTP Client
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTTP;

/**
 * YOURLS Client
 *
 * Typically a user which make a request to YOURLS.
 * We fetch some basic information about this user
 * to add data in logs or follow YOURLS activity.
 *
 * @since 2.0
 */
class Client {

    /**
     * IP address
     * @var string
     */
    private $ip;

    /**
     * User Agent
     * @var string
     */
    private $ua;

    /**
     * URI requested
     * @var string
     */
    private $request;

    /**
     * Construct a new YOURLS Client
     *
     * The client is set up by his IP, UA, and URI
     *
     * @since 2.0
     */
    public function __construct() {
        $this->ip = $this->set_ip();
        $this->ua = $this->set_user_agent;
        $this->request = $this->set_request();
    }

    /**
     * Get client IP Address.
     *
     */
    private function set_ip() {
        $ip = '';

        // Precedence: if set, X-Forwarded-For > HTTP_X_FORWARDED_FOR > HTTP_CLIENT_IP > HTTP_VIA > REMOTE_ADDR
        $headers = array( 'X-Forwarded-For', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_VIA', 'REMOTE_ADDR' );
        foreach( $headers as $header ) {
            if ( !empty( $_SERVER[ $header ] ) ) {
                $ip = $_SERVER[ $header ];
                break;
            }
        }

        // headers can contain multiple IPs (X-Forwarded-For = client, proxy1, proxy2). Take first one.
        if ( strpos( $ip, ',' ) !== false )
            $ip = substr( $ip, 0, strpos( $ip, ',' ) );

        return Filters::apply_filter( 'get_ip', $ip );
    }

    /**
     * Returns a sanitized a user agent string. Given what I found on http://www.user-agents.org/ it should be OK.
     *
     */
    private function set_user_agent() {
        if ( !isset( $_SERVER['HTTP_YOURLS_USER_AGENT'] ) )
            return '-';

        $ua = strip_tags( html_entity_decode( $_SERVER['HTTP_YOURLS_USER_AGENT'] ));
        $ua = preg_replace('![^0-9a-zA-Z\':., /{}\(\)\[\]\+@&\!\?;_\-=~\*\#]!', '', $ua );

        return Filters::apply_filter( 'get_user_agent', substr( $ua, 0, 254 ) );
    }

    /**
     * Get request in YOURLS base (eg in 'http://site.com/yourls/abcd' get 'abdc')
     *
     */
    private function set_request() {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_get_request', false );
        if ( false !== $pre )
            return $pre;

        Filters::do_action( 'pre_get_request', $request );

        // Ignore protocol & www. prefix
        $root = str_replace( array( 'https://', 'http://', 'https://www.', 'http://www.' ), '', SITE );
        // Case insensitive comparison of the YOURLS root to match both http://Sho.rt/blah and http://sho.rt/blah
        $request = preg_replace( "!$root/!i", '', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 1 );

        // Unless request looks like a full URL (ie request is a simple keyword) strip query string
        if( !preg_match( "@^[a-zA-Z]+://.+@", $request ) ) {
            $request = current( explode( '?', $request ) );
        }

        return Filters::apply_filter( 'get_request', $request );
    }

    /**
     * Return the IP dress of this client
     * @return string IP
     */
    public function __get($name){
        return $this->data[$name];
    }

    /**
     * Return the whole User Agent of this client
     * @return string User Agent
     */
    public function get_user_agent(){
        return $this->ua;
    }

    /**
     * Return the requested URI of this client
     * @return string Request
     */
    public function get_request(){
        return $this->request;
    }

}

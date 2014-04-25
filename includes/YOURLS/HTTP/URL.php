<?php

/**
 * URL Manager
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTTP;

class URL extends Purl\Url {

    public function __construct( $url = null ) {
        if ( $url ) {
            parent::__construct( $url );
        } else {
            $this->site();
        }
    }

    /**
     * Return admin link, with SSL preference if applicable.
     *
     */
    public function admin_url( $page = '' ) {
        $admin = SITE . '/' . YOURLS_ADMIN_LOCATION . '/' . $page;
        if( is_ssl() or needs_ssl() )
            $admin = set_url_scheme( $admin, 'https' );

        return Filters::apply_filter( 'admin_url', $admin, $page );
    }

    /**
     * Return SITE or URL under YOURLS setup, with SSL preference
     *
     */
    public function site() {
        parent::__construct( SITE );

        // Check current usage so it won't force SSL on non-admin pages
        if( Configuration::is( 'ssl' ) )
            $this->set( 'scheme', 'https' );
        $this = Filters::apply_filter( 'site_url' );
    }

    /**
     * Check if a URL protocol is allowed
     *
     * Checks a URL against a list of whitelisted protocols. Protocols must be defined with
     * their complete scheme name, ie 'stuff:' or 'stuff://' (for instance, 'mailto:' is a valid
     * protocol, 'mailto://' isn't, and 'http:' with no double slashed isn't either
     *
     * @since 1.6
     *
     * @param string $url URL to be check
     * @param array $protocols Optional. Array of protocols, defaults to global $allowedprotocols
     * @return boolean true if protocol allowed, false otherwise
     */
    public function is_allowed_protocol( $protocols = array() ) {
        if( ! $protocols ) {
            global $allowedprotocols;
            $protocols = $allowedprotocols;
        }

        $protocol = $this->scheme;

        return Filters::apply_filter( 'is_allowed_protocol', in_array( $protocol, $protocols ), $this->getUrl(), $protocols );
    }

    /**
     * Explode a URL in an array of ( 'protocol' , 'slashes if any', 'rest of the URL' )
     *
     * Some hosts trip up when a query string contains 'http://' - see http://git.io/j1FlJg
     * The idea is that instead of passing the whole URL to a bookmarklet, eg index.php?u=http://blah.com,
     * we pass it by pieces to fool the server, eg index.php?proto=http:&slashes=//&rest=blah.com
     *
     * Known limitation: this won't work if the rest of the URL itself contains 'http://', for example
     * if rest = blah.com/file.php?url=http://foo.com
     *
     * Sample returns:
     *
     *   with 'mailto:jsmith@example.com?subject=hey' :
     *   array( 'protocol' => 'mailto:', 'slashes' => '', 'rest' => 'jsmith@example.com?subject=hey' )
     *
     *   with 'http://example.com/blah.html' :
     *   array( 'protocol' => 'http:', 'slashes' => '//', 'rest' => 'example.com/blah.html' )
     *
     * @since 1.7
     * @param string $url URL to be parsed
     * @param array $array Optional, array of key names to be used in returned array
     * @return mixed false if no protocol found, array of ('protocol' , 'slashes', 'rest') otherwise
     */
    public function get_protocol_slashes_and_rest( $url, $array = array( 'protocol', 'slashes', 'rest' ) ) {
        $proto = get_protocol( $url );

        if( !$proto or count( $array ) != 3 )

            return false;

        list( $null, $rest ) = explode( $proto, $url, 2 );
        list( $proto, $slashes ) = explode( ':', $proto );

        return array( $array[0] => $proto . ':', $array[1] => $slashes, $array[2] => $rest );
    }

}

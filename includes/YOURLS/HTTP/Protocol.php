<?php

/**
 * Protocol Manager
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTTP;

class Protocol {

    /**
     * Change protocol to match current scheme used (http or https)
     *
     */
    public function match_current_protocol( $url, $normal = 'http://', $ssl = 'https://' ) {
        if( is_ssl() )
            $url = str_replace( $normal, $ssl, $url );

        return Filters::apply_filter( 'match_current_protocol', $url );
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
    public function is_allowed_protocol( $url, $protocols = array() ) {
        if( ! $protocols ) {
            global $allowedprotocols;
            $protocols = $allowedprotocols;
        }

        $protocol = get_protocol( $url );

        return Filters::apply_filter( 'is_allowed_protocol', in_array( $protocol, $protocols ), $url, $protocols );
    }

    /**
     * Get protocol from a URL (eg mailto:, http:// ...)
     *
     * @since 1.6
     *
     * @param string $url URL to be check
     * @return string Protocol, with slash slash if applicable. Empty string if no protocol
     */
    public function get_protocol( $url ) {
        preg_match( '!^[a-zA-Z0-9\+\.-]+:(//)?!', $url, $matches );
        /*
        http://en.wikipedia.org/wiki/URI_scheme#Generic_syntax
        The scheme name consists of a sequence of characters beginning with a letter and followed by any
        combination of letters, digits, plus ("+"), period ("."), or hyphen ("-"). Although schemes are
        case-insensitive, the canonical form is lowercase and documents that specify schemes must do so
        with lowercase letters. It is followed by a colon (":").
         */
        $protocol = ( isset( $matches[0] ) ? $matches[0] : '' );

        return Filters::apply_filter( 'get_protocol', $protocol, $url );
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

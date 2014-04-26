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
        parent::__construct( $url );
        if ( !$url ) {
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
        $this->set( 'host', SITE );

        // Check current usage so it won't force SSL on non-admin pages
        if( Configuration::is( 'ssl' ) )
            $this->set( 'scheme', 'https' );
        $this->url = Filters::apply_filter( 'site_url', $this->getUrl() );
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

        return Filters::apply_filter( 'is_allowed_protocol', in_array( $this->scheme, $protocols ), $this->getUrl(), $protocols );
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
    
    /**
     * Redirect to another page
     *
     */
    public function redirect( $code = 301 ) {
        Filters::do_action( 'pre_redirect', $this->getUrl(), $code );
        $location = Filters::apply_filter( 'redirect_location', $this->getUrl(), $code );
        // Redirect, either properly if possible, or via Javascript otherwise
        if( headers_sent() ) {
            $this->redirect_js( $location );
        } else {
            $code = Filters::apply_filter( 'redirect_code', $code, $location );
            Header::status( $code );
            new Header( 'Location', $location );
        }
   }

    /**
     * Redirect to another page using Javascript. 
     *
     * @param $dontwait bool False to force manual redirection
     */
    public function redirect_js( $dontwait = true ) {
        Filters::do_action( 'pre_redirect_javascript', $this->getUrl(), $dontwait );
        $location = Filters::apply_filter( 'redirect_javascript', $this->getUrl(), $dontwait );
        if( $dontwait ) {
            $message = s( 'if you are not redirected after 10 seconds, please <a href="%s">click here</a>', $location );
            echo <<<REDIR
        <script type="text/javascript">
        window.location="$location";
        </script>
        <small>($message)</small>
REDIR;
        } else {
            echo '<p>' . s( 'Please <a href="%s">click here</a>', $location ) . '</p>';
        }
        Filters::do_action( 'post_redirect_javascript', $location );
    }

    /**
     * Checks and cleans a URL before printing it. Stolen from WP.
     *
     * A number of characters are removed from the URL. If the URL is for displaying
     * (the default behavior) ampersands are also replaced.
     *
     * @since 1.6
     */
    public function escape() {
        // make sure there's only one 'http://' at the beginning (prevents pasting a URL right after the default 'http://')
        $url = str_replace(
            array( 'http://http://', 'http://https://' ),
            array( 'http://',        'https://'        ),
            $url
        );

        if ( '' == $url )
            return $url;

        // make sure there's a protocol, add http:// if not
        if ( ! $http->get_protocol( $url ) )
            $url = 'http://'.$url;

        $original_url = $url;

        // force scheme and domain to lowercase - see issues 591 and 1630
        // We're not using parse_url() here because its opposite, http_build_url(), requires PECL. Plus, who doesn't love a neat Regexp? :)
        if( preg_match( '!^([a-zA-Z0-9\+\.-]+:)(//)?(.*?@)?([^/#?]+)(.*)$!', $url, $matches ) ) {
            list( $all, $scheme, $slashes, $userinfo, $domain, $rest ) = $matches;
            $scheme = strtolower( $scheme );
            // Domain to lowercase. On URN eg "urn:example:animal:ferret:nose" don't lowercase anything else
            if( $slashes == '//' )
                $domain = strtolower( $domain );
            $url = $scheme . $slashes . $userinfo . $domain . $rest;
        }

        $url = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url );
        // Previous regexp in YOURLS was '|[^a-z0-9-~+_.?\[\]\^#=!&;,/:%@$\|*`\'<>"()\\x80-\\xff\{\}]|i'
        // TODO: check if that was it too destructive
        $strip = array( '%0d', '%0a', '%0D', '%0A' );
        $url = $this->deep_replace( $strip, $url );
        $url = str_replace( ';//', '://', $url );

        if ( ! is_array( $protocols ) or ! $protocols ) {
            global $allowedprotocols;
            $protocols = Filters::apply_filter( 'esc_url_protocols', $allowedprotocols );
            // Note: $allowedprotocols is also globally filterable in functions-kses.php/kses_init()
        }

        if ( !$http->is_allowed_protocol( $url, $protocols ) )
            return '';

        // I didn't use KSES function kses_bad_protocol() because it doesn't work the way I liked (returns //blah from illegal://blah)

        $url = substr( $url, 0, 1999 );

        return Filters::apply_filter( 'esc_url', $url, $original_url, $context );
    }
    
    /**
     * Checks and cleans a URL before printing it. Stolen from WP.
     *
     * A number of characters are removed from the URL. If the URL is for displaying
     * (the default behaviour) ampersands are also replaced.
     *
     * @since 1.6
     *
     * @return string The cleaned $url
     */
    public function display() {
        $kses = new KSES();
        $url = $kses->normalize_entities( $url->getUrl() );
        $url = str_replace( '&amp;', '&#038;', $url->getUrl() );
        $url = str_replace( "'", '&#039;', $url->getUrl() );
        return $url;
    }

}

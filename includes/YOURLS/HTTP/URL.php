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

class URL {

    /**
     * Converts keyword into short link (prepend with YOURLS base URL)
     *
     */
    public function link( $keyword = '' ) {
        $link = SITE . '/' . sanitize_keyword( $keyword );

        return apply_filter( 'link', $link, $keyword );
    }

    /**
     * Converts keyword into stat link (prepend with YOURLS base URL, append +)
     *
     */
    public function statlink( $keyword = '' ) {
        $link = SITE . '/' . sanitize_keyword( $keyword ) . '+';
        if( is_ssl() )
            $link = set_url_scheme( $link, 'https' );

        return apply_filter( 'statlink', $link, $keyword );
    }

    /**
     * Return admin link, with SSL preference if applicable.
     *
     */
    public function admin_url( $page = '' ) {
        $admin = SITE . '/' . YOURLS_ADMIN_LOCATION . '/' . $page;
        if( is_ssl() or needs_ssl() )
            $admin = set_url_scheme( $admin, 'https' );

        return apply_filter( 'admin_url', $admin, $page );
    }

    /**
     * Return SITE or URL under YOURLS setup, with SSL preference
     *
     */
    public function site_url( $echo = true, $url = '' ) {
        $url = get_relative_url( $url );
        $url = trim( SITE . '/' . $url, '/' );

        // Do not enforce (checking need_ssl() ) but check current usage so it won't force SSL on non-admin pages
        if( is_ssl() )
            $url = set_url_scheme( $url, 'https' );
        $url = apply_filter( 'site_url', $url );
        if( $echo )
            echo $url;

        return $url;
    }

    /**
     * Get relative URL (eg 'abc' from 'http://sho.rt/abc')
     *
     * Treat indifferently http & https. If a URL isn't relative to the YOURLS install, return it as is
     * or return empty string if $strict is true
     *
     * @since 1.6
     * @param string $url URL to relativize
     * @param bool $strict if true and if URL isn't relative to YOURLS install, return empty string
     * @return string URL
     */
    public function get_relative_url( $url, $strict = true ) {
        $url = sanitize_url( $url );

        // Remove protocols to make it easier
        $noproto_url  = str_replace( 'https:', 'http:', $url );
        $noproto_site = str_replace( 'https:', 'http:', SITE );

        // Trim URL from YOURLS root URL : if no modification made, URL wasn't relative
        $_url = str_replace( $noproto_site . '/', '', $noproto_url );
        if( $_url == $noproto_url )
            $_url = ( $strict ? '' : $url );

        return apply_filter( 'get_relative_url', $_url, $url );
    }

    /**
     * Set URL scheme (to HTTP or HTTPS)
     *
     * @since 1.7.1
     * @param string $url URL
     * @param string $scheme scheme, either 'http' or 'https'
     * @return string URL with chosen scheme
     */
    public function set_url_scheme( $url, $scheme = false ) {
        if( $scheme != 'http' && $scheme != 'https' ) {
            return $url;
        }

        return preg_replace( '!^[a-zA-Z0-9\+\.-]+://!', $scheme . '://', $url );
    }

}

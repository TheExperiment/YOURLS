<?php

/**
 * Shortener Engine
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Shortener;

class URL /*extends \HTTP\URL*/ {

    /**
     * Url expression
     * @var string
     */
    private $url;

    /**
     * Title expression
     * @var string
     */
    private $title;
    /**
     * Check if a URL already exists in the DB. Return NULL (doesn't exist) or an object with URL informations.
     *
     */

    public function exists() {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_url_exists', false, $this->url );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $table = YOURLS_DB_TABLE_URL;
        $url->sanitize->escape();
        $url_exists = $ydb->get_row( "SELECT * FROM `$table` WHERE `url` = '".$this->url."';" );

        return Filters::apply_filter( 'url_exists', $url_exists, $this->url );
    }

    /**
     * Get a remote page title
     *
     * This function returns a string: either the page title as defined in HTML, or the URL if not found
     * The function tries to convert funky characters found in titles to UTF8, from the detected charset.
     * Charset in use is guessed from HTML meta tag, or if not found, from server's 'content-type' response.
     *
     * @param string $url URL
     * @return string Title (sanitized) or the URL if no title found
     */
    public function get_remote_title( $url ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_get_remote_title', false, $url );
        if ( false !== $pre )
            return $pre;

        $url = sanitize_url( $url );

        // Only deal with http(s)://
        if( !in_array( get_protocol( $url ), array( 'http://', 'https://' ) ) )

            return $url;

        $title = $charset = false;

        $response = http_get( $url ); // can be a Request object or an error string
        if( is_string( $response ) ) {
            return $url;
        }

        // Page content. No content? Return the URL
        $content = $response->body;
        if( !$content )

            return $url;

        // look for <title>. No title found? Return the URL
        if ( preg_match('/<title>(.*?)<\/title>/is', $content, $found ) ) {
            $title = $found[1];
            unset( $found );
        }
        if( !$title )

            return $url;

        // Now we have a title. We'll try to get proper utf8 from it.

        // Get charset as (and if) defined by the HTML meta tag. We should match
        // <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        // or <meta charset='utf-8'> and all possible variations: see https://gist.github.com/ozh/7951236
        if ( preg_match( '/<meta[^>]*charset\s*=["\' ]*([a-zA-Z0-9\-_]+)/is', $content, $found ) ) {
            $charset = $found[1];
            unset( $found );
        } else {
            // No charset found in HTML. Get charset as (and if) defined by the server response
            $_charset = current( $response->headers->getValues( 'content-type' ) );
            if( preg_match( '/charset=(\S+)/', $_charset, $found ) ) {
                $charset = trim( $found[1], ';' );
                unset( $found );
            }
        }

        // Conversion to utf-8 if what we have is not utf8 already
        if( strtolower( $charset ) != 'utf-8' && function_exists( 'mb_convert_encoding' ) ) {
            // We use @ to remove warnings because mb_ functions are easily bitching about illegal chars
            if( $charset ) {
                $title = @mb_convert_encoding( $title, 'UTF-8', $charset );
            } else {
                $title = @mb_convert_encoding( $title, 'UTF-8' );
            }
        }

        // Remove HTML entities
        $title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );

        // Strip out evil things
        $title = sanitize_title( $title );

        return Filters::apply_filter( 'get_remote_title', $title, $url );
    }

    /**
     * Return array of keywords that redirect to the submitted long URL
     *
     * @since 1.7
     * @param string $longurl long url
     * @param string $sort Optional ORDER BY order (can be 'keyword', 'title', 'timestamp' or'clicks')
     * @param string $order Optional SORT order (can be 'ASC' or 'DESC')
     * @return array array of keywords
     */
    public function get_keywords( $longurl, $sort = 'none', $order = 'ASC' ) {
        global $ydb;
        $longurl = escape( sanitize_url( $longurl ) );
        $table   = YOURLS_DB_TABLE_URL;
        $query   = "SELECT `keyword` FROM `$table` WHERE `url` = '$longurl'";

        // Ensure sort is a column in database (@TODO: update verification array if database changes)
        if ( in_array( $sort, array('keyword','title','timestamp','clicks') ) ) {
            $query .= " ORDER BY '".$sort."'";
            if ( in_array( $order, array( 'ASC','DESC' ) ) ) {
                $query .= " ".$order;
            }
        }

        return Filters::apply_filter( 'get_longurl_keywords', $ydb->get_col( $query ), $longurl );
    }

}

<?php

/**
 * Headers Manager
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Shortener;

use YOURLS\HTTP\URL;

class Keyword {

    /**
     * Keyword expression
     * @var string
     */
    private $keyword;

    const CHARSET_32 = '0123456789abcdefghijklmnopqrstuvwxyz';
    const CHARSET_62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @param string $keyword
     */
    public function __construct( string $keyword ) {
        $this->keyword = str_replace( SITE . '/' , '', $keyword );
    }

    /**
     * Sanitize the keyword string
     *
     * Make sure a link keyword (ie "1fv" as in "site.com/1fv") is valid.
     */
    public function sanitize() {
        $pattern = self::make_regexp_pattern( self::charset() );
        $valid = substr( preg_replace( '![^'.$pattern.']!', '', $this->keyword ), 0, 199 );
        $this->keyword = Filters::apply_filter( 'sanitize_keyword', $valid, $this->keyword );
    }

    public function __toString() {
        return $this->keyword;
    }

    /**
     * Determine the allowed character set in short URLs
     *
     */
    public static function charset() {
        if( defined( 'URL_CONVERT' ) && in_array( URL_CONVERT, array( 62, 64 ) ) ) {
            $charset = CHARSET_62;
        } else {
            // defined to 36, or wrongly defined
            $charset = CHARSET_32;
        }

        return Filters::apply_filter( 'get_shorturl_charset', $charset );
    }

    /**
     * Converts keyword into short link (prepend with YOURLS base URL)
     *
     */
    public function link() {
        $this->sanitize();
        $link = new URL();
        $link->path->add( $this );

        return Filters::apply_filter( 'link', $link, $this->keyword );
    }

    public function long() {
        return new URL();
    }

    /**
     * Converts keyword into stat link (prepend with YOURLS base URL, append +)
     *
     */
    public function stat_link() {
        $this->sanitize();
        $link = new URL();
        $link->path->add( $this . '+' );

        return Filters::apply_filter( 'statlink', $link, $keyword );
    }

    /**
     * Check to see if a given keyword is reserved (ie reserved URL or an existing page). Returns bool
     *
     */
    public function is_reserved() {
        global $reserved_URL;
        $this->sanitize();
        $reserved = ( in_array( $this->keyword, $reserved_URL)
            or file_exists( YOURLS_PAGEDIR ."/$this->keyword.php" )
            or is_dir( YOURLS_ABSPATH ."/$this->keyword" )
            or ( substr( $this->keyword, 0, strlen( YOURLS_ADMIN_LOCATION ) + 1 ) === YOURLS_ADMIN_LOCATION."/" )
        );

        return Filters::apply_filter( 'keyword_is_reserved', $reserved, $this->keyword );
    }

    /**
     * Check if keyword id is free (ie not already taken, and not reserved). Return bool.
     *
     */
    public function is_free() {
        $free = !$this->is_reserved() && !$this->is_taken();

        return Filters::apply_filter( 'keyword_is_free', $free, $this->keyword );
    }

    /**
     * Check if a keyword is taken (ie there is already a short URL with this id). Return bool.
     *
     */
    public function is_taken() {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_keyword_is_taken', false, $this->keyword );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $this->sanitize()->escape();
        $taken = false;
        $table = YOURLS_DB_TABLE_URL;
        if ( $ydb->get_var( "SELECT COUNT(`keyword`) FROM `$table` WHERE `keyword` = '$keyword';" ) )
            $taken = true;

        return Filters::apply_filter( 'keyword_is_taken', $taken, $this->keyword );
    }

    /**
     * Return array of all information associated with keyword. Returns false if keyword not found. Set optional $use_cache to false to force fetching from DB
     *
     */
    public function get_infos( $use_cache = true ) {
        global $ydb;
        $keyword = escape( sanitize_string( $keyword ) );

        Filters::do_action( 'pre_get_keyword', $keyword, $use_cache );

        if( isset( $ydb->infos[$keyword] ) && $use_cache == true ) {
            return Filters::apply_filter( 'get_keyword_infos', $ydb->infos[$keyword], $keyword );
        }

        Filters::do_action( 'get_keyword_not_cached', $keyword );

        $table = YOURLS_DB_TABLE_URL;
        $infos = $ydb->get_row( "SELECT * FROM `$table` WHERE `keyword` = '$keyword'" );

        if( $infos ) {
            $infos = (array)$infos;
            $ydb->infos[ $keyword ] = $infos;
        } else {
            $ydb->infos[ $keyword ] = false;
        }

        return Filters::apply_filter( 'get_keyword_infos', $ydb->infos[$keyword], $keyword );
    }

    /**
     * Update click count on a short URL. Return 0/1 for error/success.
     *
     */
    public function update_clicks( $clicks = false ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_update_clicks', false, $keyword, $clicks );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $keyword = escape( sanitize_string( $keyword ) );
        $table = YOURLS_DB_TABLE_URL;
        if ( $clicks !== false && is_int( $clicks ) && $clicks >= 0 )
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = $clicks WHERE `keyword` = '$keyword'" );
        else
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = clicks + 1 WHERE `keyword` = '$keyword'" );

        Filters::do_action( 'update_clicks', $keyword, $update, $clicks );

        return $update;
    }

    /**
     * Return (string) selected information associated with a keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_info( $field, $notfound = false ) {

        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_get_keyword_info', false, $keyword, $field, $notfound );
        if ( false !== $pre )
            return $pre;

        $keyword = sanitize_string( $keyword );
        $infos = get_keyword_infos( $keyword );

        $return = $notfound;
        if ( isset( $infos[ $field ] ) && $infos[ $field ] !== false )
            $return = $infos[ $field ];

        return Filters::apply_filter( 'get_keyword_info', $return, $keyword, $field, $notfound );
    }

    /**
     * Update click count on a short URL. Return 0/1 for error/success.
     *
     */
    public function update_clicks( $clicks = false ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_update_clicks', false, $keyword, $clicks );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $keyword = escape( sanitize_string( $keyword ) );
        $table = YOURLS_DB_TABLE_URL;
        if ( $clicks !== false && is_int( $clicks ) && $clicks >= 0 )
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = $clicks WHERE `keyword` = '$keyword'" );
        else
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = clicks + 1 WHERE `keyword` = '$keyword'" );

        Filters::do_action( 'update_clicks', $keyword, $update, $clicks );

        return $update;
    }

}

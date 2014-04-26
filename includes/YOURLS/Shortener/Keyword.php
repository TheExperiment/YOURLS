<?php

/**
 * Headers Manager
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Shortener;

use YOURLS\Utilities\Format;
use YOURLS\HTTP\URL;

class Keyword {

    /**
     * Keyword expression
     * @var string
     */
    private $keyword;

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
        $pattern = self::make_regexp_pattern( get_shorturl_charset() );
        $valid = substr( preg_replace( '![^'.$pattern.']!', '', $this->keyword ), 0, 199 );

        $this->keyword = Filters::apply_filter( 'sanitize_keyword', $valid, $this->keyword );
    }

    public function __toString() {
        return $this->keyword;
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

}

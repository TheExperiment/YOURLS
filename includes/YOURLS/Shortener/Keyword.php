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
     */
    public static function sanitize() {
        Format::sanitize_string( $this->keyword );
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

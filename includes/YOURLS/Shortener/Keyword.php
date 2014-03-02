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
        $this->keyword = $keyword;
    }
}

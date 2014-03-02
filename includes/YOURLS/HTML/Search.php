<?php

/**
 * Search Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTML;

/**
 * Here we prepare HTML output
 */
class Search {

    /**
     * Get search text from query string variables search_protocol, search_slashes and search
     *
     * Some servers don't like query strings containing "(ht|f)tp(s)://". A javascript bit
     * explodes the search text into protocol, slashes and the rest (see JS function
     * split_search_text_before_search()) and this function glues pieces back together
     * See issue https://github.com/YOURLS/YOURLS/issues/1576
     *
     * @since 1.7
     * @return string Search string
     */
    public function get_search_text() {
        $search = '';
        if( isset( $_GET['search_protocol'] ) )
            $search .= $_GET['search_protocol'];
        if( isset( $_GET['search_slashes'] ) )
            $search .= $_GET['search_slashes'];
        if( isset( $_GET['search'] ) )
            $search .= $_GET['search'];

        return htmlspecialchars( trim( $search ) );
    }

}

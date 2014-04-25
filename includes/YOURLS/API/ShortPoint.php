<?php

/**
 * Answer Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\API;

class ShortPoint extends Answer implements EndPoint {

    /**
     * API function wrapper: Shorten a URL
     *
     * @since 1.6
     * @return array Result of API call
     */
    public function __construct() {
        $title   = isset( $_REQUEST['title'] ) ? $_REQUEST['title'] : '';
        $url     = new URL( isset( $_REQUEST['url'] ) ? $_REQUEST['url'] : '' );
        $keyword = new Keyword( isset( $_REQUEST['keyword'] ) ? $_REQUEST['keyword'] : '' );

        parent::__construct( add_new_link( $url, $keyword, $title ) );
        // This one will be used in case output mode is 'simple'
        $this->simple = isset( $this->shorturl ) ? $this->shorturl : '';
        // in API mode, no need for our internal HTML output
        unset( $this->html );

        return Filters::apply_filter( 'api_shorturl', $this->answer );
    }

}

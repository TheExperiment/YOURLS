<?php

/**
 * Response Wrapper
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\API;

class KeywordStatsPoint extends Response implements EndPoint {

    /**
     * Return array for API stat requests
     *
     */
    public function __construct( Keyword $shorturl ) {
        parent::__construct( get_link_stats( $keyword ) );

        return Filters::apply_filter( 'api_url_stats', $this->response, $shorturl );
    }

}

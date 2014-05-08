<?php

/**
 * Response Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\API;

class StatsPoint extends Response implements EndPoint {

    /**
     * Expand short url to long url
     *
     */
    public function __construct( $shorturl ) {
        $filter = isset( $_REQUEST['filter'] ) ? $_REQUEST['filter'] : '';
        $limit  = isset( $_REQUEST['limit'] )  ? $_REQUEST['limit']  : '';
        $start  = isset( $_REQUEST['start'] )  ? $_REQUEST['start']  : '';

        parent::__construct( get_stats( $filter, $limit, $start ) );

        return Filters::apply_filter( 'api_stats', $this->response, $filter, $limit, $start );
    }

}

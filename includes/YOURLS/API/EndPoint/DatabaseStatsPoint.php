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

class DatabaseStatsPoint extends Response implements EndPoint {

    /**
     * Return array for counts of shorturls and clicks
     *
     */
    public function __construct() {
        parent::__construct( array(
            'db-stats'   => get_db_stats()
        ) );

        return Filters::apply_filter( 'api_db_stats', $this->response );
    }

}

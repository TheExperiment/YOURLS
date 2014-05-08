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

class Request {
    
    public $actions = array(
        'db-stats'  => 'DatabaseStatsPoint',
        'expand'    => 'ExpendPoint',
        'url-stats' => 'KeywordStatsPoint',
        'short'     => 'ShortPoint',
        'stats'     => 'StatsPoint'
    );

    public function __construct() {
        $this->actions = Filters::apply_filters( 'api_actions', $this->actions );
        
        $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : null;
        Filters::do_action( 'api', $action );
        
        try {
            $response = new $this->actions[$action];
        } catch (Exception $e) {
            $response = new Response( array(
                'status_code' => 400,
                'message'   => 'Unknown or missing "action" parameter',
                'simple'    => 'Unknown or missing "action" parameter',
            ) );
        }
        echo $response;
    }

}

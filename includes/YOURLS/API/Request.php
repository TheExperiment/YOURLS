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

class Request {
    
    public $actions = array();

    public function __construct() {
        Filters::apply_filters( 'api_actions', $this->actions );
        
        $action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : null;
        Filters::do_action( 'api', $action );
        
        try {
            new Answer( new $this->actions[$action] );
        } catch (Exception $e) {
            new Answer( array(
                'status_code' => 400,
                'message'   => 'Unknown or missing "action" parameter',
                'simple'    => 'Unknown or missing "action" parameter',
            ) );
        }
     
    }

}

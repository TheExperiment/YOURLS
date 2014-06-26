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

class Request {

    private $actions = array(
        'db-stats'  => 'DatabaseStatsPoint',
        'expand'    => 'ExpendPoint',
        'url-stats' => 'KeywordStatsPoint',
        'short'     => 'ShortPoint',
        'stats'     => 'StatsPoint'
    );

    public function __construct() {
        $this->actions = Filters::apply_filters( 'api_actions', $this->actions );

        Filters::do_action( 'api', $this->action );

        try {
            $reflection = new ReflectionMethod( $this->actions[$action], '__construct' );
            $params = $reflection->getParameters();
            foreach ($params as $param) {
                $f_param = $this->$param->getName();
            }
            $response = new $this->actions[$action]( $f_param );
        } catch (Exception $e) {
            $response = new Response( array(
                'status_code' => 400,
                'message'   => 'Unknown or missing "action" parameter',
                'simple'    => 'Unknown or missing "action" parameter',
            ) );
        }
        echo $response;
    }

    public function __get( $param ) {
        return isset( $_REQUEST[ $param ] ) ? $_REQUEST[ $param ] : null;
    }

}

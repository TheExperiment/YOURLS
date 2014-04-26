<?php

/**
 * YOURLS Logger
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Utilities;

use Monolog\Handler\StreamHandler;

/**
 * Construct a logger engine
 */
class Logger extends \Monolog\Logger {

    /**
     * @param string $channel The name of the channel
     */
    public function __construct( $channel ) {
        parent::__construct( 'YOURLS.' . $channel );
        if ( defined( 'YOURLS_DEBUG' ) && YOURLS_DEBUG == true ) {
            $this->pushHandler( new StreamHandler( YOURLS_USERDIR . '/yourls.log' ) );
        }
    }
}

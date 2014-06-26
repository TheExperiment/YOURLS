<?php

/**
 * Request
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Network\Client;

class Request extends Keyword /* or URl ??? */implements Data {

    private $value;

    public function set() {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_get_request', false );
        if ( false !== $pre )
            $this->value = $pre;

        Filters::do_action( 'pre_get_request', $this->value );

        $root = new URL();
        $this->value = new URL( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

        // @TODO Continue URL managing...
        // Unless request looks like a full URL (ie request is a simple keyword) strip query string
        if( !preg_match( "@^[a-zA-Z]+://.+@", $this->value ) ) {
            $this->value = current( explode( '?', $this->value ) );
        }

        $this->value = Filters::apply_filter( 'get_request', $this->value );
    }

}

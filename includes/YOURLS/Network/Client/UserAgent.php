<?php

/**
 * UserAgent
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Network\Client;

class UserAgent implements Data {

    private $value;

    public function set() {
        if ( !isset( $_SERVER['HTTP_YOURLS_USER_AGENT'] ) )
            return '-';

        $this->value = strip_tags( html_entity_decode( $_SERVER['HTTP_YOURLS_USER_AGENT'] ));
        $this->value = Filters::apply_filter( 'get_user_agent', substr( $this->value, 0, 254 ) );
    }

    public function sanitize() {
        $this->value = preg_replace('![^0-9a-zA-Z\':., /{}\(\)\[\]\+@&\!\?;_\-=~\*\#]!', '', $this->value );
    }

}

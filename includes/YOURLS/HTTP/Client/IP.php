<?php

/**
 * ClientIP
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */
 
namespace YOURLS\HTTP\Client;

class IP implements Data {

    private $value;
    
    public function set() {
        // Precedence: if set, X-Forwarded-For > HTTP_X_FORWARDED_FOR > HTTP_CLIENT_IP > HTTP_VIA > REMOTE_ADDR
        $headers = array( 'X-Forwarded-For', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_VIA', 'REMOTE_ADDR' );
        foreach( $headers as $header ) {
            if ( !empty( $_SERVER[ $header ] ) ) {
                $this->value = $_SERVER[ $header ];
                break;
            }
        }

        // headers can contain multiple IPs (X-Forwarded-For = client, proxy1, proxy2). Take first one.
        if ( strpos( $this->value, ',' ) !== false )
            $this->value = substr( $this->ip, 0, strpos( $this->value, ',' ) );

        $this->value = Filters::apply_filter( 'get_ip', $this->value );

    }
    
    /**
     * Sanitize an IP address
     *
     */
    public function sanitize() {
        $this->value = preg_replace( '/[^0-9a-fA-F:., ]/', '', $this->value );
    }

    
}

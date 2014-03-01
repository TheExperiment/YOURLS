<?php

/**
 * HTTP Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTTP;

class Headers {

    /**
     * Send a filerable content type header
     *
     * @since 1.7
     * @param string $type content type ('text/html', 'application/json', ...)
     * @return bool whether header was sent
     */
    public function content_type_header( $type ) {
        if( !headers_sent() ) {
            $charset = apply_filters( 'content_type_header_charset', 'utf-8' );
            header( "Content-Type: $type; charset=$charset" );

            return true;
        }

        return false;
    }

}

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

class ExpendPoint extends Answer implements EndPoint {

    /**
     * Expand short url to long url
     *
     */
    public function __construct( $shorturl ) {
        $shorturl = new Keyword( $shorturl );
        $shorturl->sanitize();

        $longurl = $shorturl->long();

        if( $longurl ) {
            parent::__construct( array(
                'keyword'   => $shorturl,
                'shorturl'  => SITE . "/$shorturl",
                'longurl'   => $longurl,
                'simple'    => $longurl,
                'message'   => 'success',
                'status_code' => 200,
            ) );
        } else {
            parent::__construct( array(
                'keyword'   => $shorturl,
                'simple'    => 'not found',
                'message'   => 'Error: short URL not found',
                'error_code' => 404,
            ) );
        }

        return Filters::apply_filter( 'api_expand', $return, $shorturl );
    }

}

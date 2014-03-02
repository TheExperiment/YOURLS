<?php

/**
 * Nonce Creator
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Administration;

class Nonce {

    /**
     * Create a time limited, action limited and user limited token
     *
     */
    public function create_nonce( $action, $user = false ) {
        if( false == $user )
            $user = defined( 'YOURLS_USER' ) ? YOURLS_USER : '-1';
        $tick = tick();

        return substr( salt($tick . $action . $user), 0, 10 );
    }

    /**
     * Create a nonce field for inclusion into a form
     *
     */
    public function nonce_field( $action, $name = 'nonce', $user = false, $echo = true ) {
        $field = '<input type="hidden" id="'.$name.'" name="'.$name.'" value="'.create_nonce( $action, $user ).'" />';
        if( $echo )
            echo $field;

        return $field;
    }

    /**
     * Add a nonce to a URL. If URL omitted, adds nonce to current URL
     *
     */
    public function nonce_url( $action, $url = false, $name = 'nonce', $user = false ) {
        $nonce = create_nonce( $action, $user );

        return add_query_arg( $name, $nonce, $url );
    }

    /**
     * Check validity of a nonce (ie time span, user and action match).
     *
     * Returns true if valid, dies otherwise (die() or die($return) if defined)
     * if $nonce is false or unspecified, it will use $_REQUEST['nonce']
     *
     */
    public function verify_nonce( $action, $nonce = false, $user = false, $return = '' ) {
        // get user
        if( false == $user )
            $user = defined( 'YOURLS_USER' ) ? YOURLS_USER : '-1';

        // get current nonce value
        if( false == $nonce && isset( $_REQUEST['nonce'] ) )
            $nonce = $_REQUEST['nonce'];

        // what nonce should be
        $valid = create_nonce( $action, $user );

        if( $nonce == $valid ) {
            return true;
        } else {
            if( $return )
                die( $return );
            die( _( 'Unauthorized action or expired link' )/*, _( 'Error' ), 403 */);
        }
    }
}

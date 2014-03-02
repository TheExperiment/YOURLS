<?php

/**
 * Redirection Creator
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTTP;

class Redirect {

    /**
     * Redirect to another page
     *
     */
    public function redirect( $location, $code = 301 ) {
        do_action( 'pre_redirect', $location, $code );
        $location = apply_filter( 'redirect_location', $location, $code );
        $code     = apply_filter( 'redirect_code', $code, $location );
        // Redirect, either properly if possible, or via Javascript otherwise
        if( !headers_sent() ) {
            status_header( $code );
            header( "Location: $location" );
        } else {
            redirect_javascript( $location );
        }
        die();
    }
    /**
     * Redirect to another page using Javascript. Set optional (bool)$dontwait to false to force manual redirection (make sure a message has been read by user)
     *
     */
    public function redirect_javascript( $location, $dontwait = true ) {
        do_action( 'pre_redirect_javascript', $location, $dontwait );
        $location = apply_filter( 'redirect_javascript', $location, $dontwait );
        if( $dontwait ) {
            $message = s( 'if you are not redirected after 10 seconds, please <a href="%s">click here</a>', $location );
            echo <<<REDIR
        <script type="text/javascript">
        window.location="$location";
        </script>
        <small>($message)</small>
REDIR;
        } else {
            echo '<p>' . s( 'Please <a href="%s">click here</a>', $location ) . '</p>';
        }
        do_action( 'post_redirect_javascript', $location );
    }

}

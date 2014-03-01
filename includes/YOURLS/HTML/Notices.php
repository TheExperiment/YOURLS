<?php

/**
 * HTML Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTML;

/**
 * Here we prepare HTML output
 */
class Notices {

    /**
     * Wrapper function to display admin notice
     *
     * @param string $message The message showed
     * @param string $style notice / error / info / warning / success
     */
    public function add_notice( $message, $style = 'notice' ) {
        // Escape single quotes in $message to avoid breaking the anonymous function
        $message = notice_box( strtr( $message, array( "'" => "\'" ) ), $style );
        add_action( 'admin_notice', create_function( '', "echo '$message';" ) );
    }

    /**
     * Return a formatted notice
     *
     * @param string $message The message showed
     * @param string $style notice / error / info / warning / success
     */
    public function notice_box( $message, $style = 'notice' ) {
        return '<div class="alert alert-' . $style . '"><a class="close" data-dismiss="alert" href="#">&times;</a>' . $message . '</div>';
    }

    /**
     * Display a notice if there is a newer version of YOURLS available
     *
     * @since 1.7
     */
    public function new_core_version_notice() {

        debug_log( 'Check for new version: ' . ( maybe_check_core_version() ? 'yes' : 'no' ) );

        $checks = get_option( 'core_version_checks' );

        if( isset( $checks->last_result->latest ) AND version_compare( $checks->last_result->latest, VERSION, '>' ) ) {
            $msg = s( '<a href="%s">YOURLS version %s</a> is available. Please update!', 'http://yourls.org/download', $checks->last_result->latest );
            add_notice( $msg );
        }
    }

    /**
     * Display custom message based on query string parameter 'login_msg'
     *
     * @since 1.7
     */
    public function display_login_message() {
        if( !isset( $_GET['login_msg'] ) )

            return;

        switch( $_GET['login_msg'] ) {
            case 'pwdclear':
                $message  = $this->htag( _( 'Warning' ), 4, null, null, false );
                $message .= '<p>' . _( 'Your password is stored as clear text in your <code>config.php</code>' );
                $message .= '<br />' . _( 'Did you know you can easily improve the security of your YOURLS install by <strong>encrypting</strong> your password?' );
                $message .= '<br />' . _( 'See <a href="http://yourls.org/userpassword">UsernamePassword</a> for details.' ) . '</p>';
                add_notice( $message, 'notice' );
                break;
        }
    }

}

<?php

/**
 * Authentication Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Administration;

use Hautelook\Phpass\PasswordHash;

class User extends PasswordHash {

    private $authenticated = false;

    /**
     * Check if an authentication is needed
     * and show login form if required
     */
    public function __construct() {
        if( !Configuration::is( 'privated' ) ) {
            Filters::do_action( 'require_no_auth' );
        } else {
            Filters::do_action( 'require_auth' );

            if( $this->valid ) {

                // API mode,
                if ( Configuration::is( 'api' ) ) {
                    $callback = isset( $_REQUEST['callback'] ) ? $_REQUEST['callback'] : '';
                    new Answer( array(
                        'simple' => $auth[1],
                        'message' => $auth[0],
                        'error_code' => 403,
                        'callback' => $callback,
                    ) );

                    // Regular mode
                } else {
                    // @TODO Old function
                    yourls_login_screen( $auth );
                }
            }

            Filters::do_action( 'auth_successful' );
        }
    }

    /**
     * Check for valid user via login form or stored cookie. Returns true or an error message
     *
     * @todo Dispash this function and clean up
     */
    public static function is_valid() {
        if( $this->valid )

            return $this->valid;

        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_is_valid_user', null );
        if ( null !== $pre ) {
            $this->valid = ( $pre === true ) ;

            return $this->valid;
        }

        // $unfiltered_valid : are credentials valid? Boolean value. It's "unfiltered" to allow plugins to eventually filter it.
        $unfiltered_valid = false;

        // Logout request
        if( isset( $_GET['action'] ) && $_GET['action'] == 'logout' ) {
            Filters::do_action( 'logout' );
            $this->store_cookie( null );

            return array( _( 'Logged out successfully' ), 'success' );
        }

        // Check cookies or login request. Login form has precedence.

        Filters::do_action( 'pre_login' );

        // Determine auth method and check credentials
        if
            // API only: Secure (no login or pwd) and time limited token
            // ?timestamp=12345678&signature=md5(totoblah12345678)
            ( Configuration::is( 'api' ) &&
              isset( $_REQUEST['timestamp'] ) && !empty($_REQUEST['timestamp'] ) &&
              isset( $_REQUEST['signature'] ) && !empty($_REQUEST['signature'] )
            )
        {
            Filters::do_action( 'pre_login_signature_timestamp' );
            $unfiltered_valid = $this->check_signature_timestamp();
        }

        elseif
            // API only: Secure (no login or pwd)
            // ?signature=md5(totoblah)
            ( Configuration::is( 'api' ) &&
              !isset( $_REQUEST['timestamp'] ) &&
              isset( $_REQUEST['signature'] ) && !empty( $_REQUEST['signature'] )
            )
        {
            Filters::do_action( 'pre_login_signature' );
            $unfiltered_valid = $this->check_signature();
        }

        elseif
            // API or normal: login with username & pwd
            ( isset( $_REQUEST['username'] ) && isset( $_REQUEST['password'] )
              && !empty( $_REQUEST['username'] ) && !empty( $_REQUEST['password']  ) )
        {
            Filters::do_action( 'pre_login_username_password' );
            $unfiltered_valid = $this->check_username_password();
        }

        elseif
            // Normal only: cookies
            ( !Configuration::is( 'api' ) &&
              isset( $_YOURLS_COOKIE['username'] ) )
        {
            Filters::do_action( 'pre_login_cookie' );
            $unfiltered_valid = $this->check_auth_cookie();
        }

        // Regardless of validity, allow plugins to filter the boolean and have final word
        $valid = Filters::apply_filter( 'is_valid_user', $unfiltered_valid );

        // Login for the win!
        if ( $valid ) {
            Filters::do_action( 'login' );

            // (Re)store encrypted cookie if needed
            if ( !Configuration::is( 'api' ) ) {
                $this->store_cookie( YOURLS_USER );

                // Login form : redirect to requested URL to avoid re-submitting the login form on page reload
                if( isset( $_REQUEST['username'] ) && isset( $_REQUEST['password'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {
                    $url = $_SERVER['REQUEST_URI'];
                    redirect( $url );
                }
            }

            // Login successful
            return true;
        }

        // Login failed
        Filters::do_action( 'login_failed' );

        if ( isset( $_REQUEST['username'] ) || isset( $_REQUEST['password'] ) ) {
            return array( _( 'Invalid username or password' ), 'error' );
        } else {
            return array( _( 'Please log in' ), 'warning' );
        }
    }

    /**
     * Check auth against list of login=>pwd. Sets user if applicable, returns bool
     *
     */
    public function check_username_password() {
        if( isset( $user_passwords[ $_REQUEST['username'] ] ) && $this->check_password_hash( $_REQUEST['username'], $_REQUEST['password'] ) ) {
            $this->set( $_REQUEST['username'] );

            return true;
        }

        return false;
    }

    /**
     * Check auth against encrypted YOURLS_COOKIE data. Sets user if applicable, returns bool
     *
     */
    public function check_auth_cookie() {
        global $user_passwords;
        foreach( $user_passwords as $valid_user => $valid_password ) {
            if ( salt( $valid_user ) == $_YOURLS_COOKIE['username'] ) {
                $this->set_user( $valid_user );

                return true;
            }
        }

        return false;
    }

    /**
     * Check auth against signature and timestamp. Sets user if applicable, returns bool
     *
     */
    public function check_signature_timestamp() {
        // Timestamp in PHP : time()
        // Timestamp in JS: parseInt(new Date().getTime() / 1000)
        global $user_passwords;
        foreach( $user_passwords as $valid_user => $valid_password ) {
            if (
                (
                    md5( $_REQUEST['timestamp'].$this->auth_signature( $valid_user ) ) == $_REQUEST['signature']
                    or
                    md5( $this->auth_signature( $valid_user ).$_REQUEST['timestamp'] ) == $_REQUEST['signature']
                )
                &&
                $this->check_timestamp( $_REQUEST['timestamp'] )
                ) {
                $this->set_user( $valid_user );

                return true;
            }
        }

        return false;
    }

    /**
     * Check auth against signature. Sets user if applicable, returns bool
     *
     */
    public function check_signature() {
        global $user_passwords;
        foreach( $user_passwords as $valid_user => $valid_password ) {
            if ( $this->auth_signature( $valid_user ) == $_REQUEST['signature'] ) {
                $this->set_user( $valid_user );

                return true;
            }
        }

        return false;
    }

    /**
     * Generate secret signature hash
     *
     */
    public function auth_signature( $username = false ) {
        if( !$username && defined('YOURLS_USER') ) {
            $username = YOURLS_USER;
        }

        return ( $username ? substr( salt( $username ), 0, 10 ) : 'Cannot generate auth signature: no username' );
    }

    /**
     * Check if timestamp is not too old
     *
     */
    public function check_timestamp( $time ) {
        $now = time();
        // Allow timestamp to be a little in the future or the past -- see Issue 766
        return Filters::apply_filter( 'check_timestamp', abs( $now - $time ) < YOURLS_NONCE_LIFE, $time );
    }

    /**
     * Store new cookie. No $user will delete the cookie.
     *
     */
    public function store_cookie( $user = null ) {
        if( !$user ) {
            $pass = null;
            $time = time() - 3600;
        } else {
            global $user_passwords;
            if( isset($user_passwords[$user]) ) {
                $pass = $user_passwords[$user];
            } else {
                die( 'Stealing cookies?' ); // This should never happen
            }
            $time = time() + YOURLS_COOKIE_LIFE;
        }

        $domain   = Filters::apply_filter( 'setcookie_domain',   parse_url( SITE, 1 ) );
        $secure   = Filters::apply_filter( 'setcookie_secure',   Configuration::is( 'ssl' ) );
        $httponly = Filters::apply_filter( 'setcookie_httponly', true );

        // Some browser refuse to store localhost cookie
        if ( $domain == 'localhost' )
            $domain = '';

        if ( !headers_sent() ) {
            setcookie('username', salt( $user ), $time, '/', $domain, $secure, $httponly );
        } else {
            // For some reason cookies were not stored: action to be able to debug that
            Filters::do_action( 'setcookie_failed', $user );
        }
    }

    /**
     * Set user name
     *
     */
    private function set( $username ) {
        $this->username = $username;
    }
    
    /**
     * Get user name
     *
     */
    public function get() {
        return $this->username;
    }

}

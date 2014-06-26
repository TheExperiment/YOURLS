<?php

/**
 * Authentication Wrapper
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Administration;

use Hautelook\Phpass\PasswordHash;

class Password extends PasswordHash {

    private $password;

    /**
     * Check if an authentication is needed
     * and show login form if required
     */
    public function __construct( $password ) {
        $this->password = $password;

        $iteration = Filters::apply_filter( 'phpass_new_instance_iteration', $iteration );
        $portable  = Filters::apply_filter( 'phpass_new_instance_portable', $portable );
        parent::__construct( $iteration, $portable );
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
     * Check a submitted password sent in plain text against stored password which can be a salted hash
     *
     */
    public function check_hash() {
        if ( $this->has_phpass_password( $user ) ) {
            // Stored password is hashed with phpass
            list( , $hash ) = explode( ':', $user_passwords[ $user ] );
            $hash = str_replace( '!', '$', $hash );

            return ( $this->phpass_check( $submitted_password, $hash ) );
        } else if( $this->has_md5_password( $user ) ) {
            // Stored password is a salted md5 hash: "md5:<$r = rand(10000,99999)>:<md5($r.'thepassword')>"
            list( , $salt, ) = explode( ':', $user_passwords[ $user ] );

            return $user_passwords[ $user ] == 'md5:'.$salt.':'.md5( $salt . $submitted_password );
        } else {
            // Password stored in clear text
            return $user_passwords[ $user ] == $submitted_password;
        }
    }

    /**
     * Overwrite plaintext passwords in config file with phpassed versions.
     *
     * @since 1.7
     * @param string $config_file Full path to file
     * @return true if overwrite was successful, an error message otherwise
     */
    public function hash() {
        if( !is_readable( $config_file ) )

            return 'cannot read file'; // not sure that can actually happen...

        if( !is_writable( $config_file ) )

            return 'cannot write file';

        // Include file to read value of $user_passwords
        // Temporary suppress error reporting to avoid notices about redeclared constants
        $errlevel = error_reporting();
        error_reporting( 0 );
        require $config_file;
        error_reporting( $errlevel );

        $configdata = file_get_contents( $config_file );
        if( $configdata == false )

            return 'could not read file';

        $to_hash = 0; // keep track of number of passwords that need hashing
        foreach ( $user_passwords as $user => $password ) {
            if ( !$this->has_phpass_password( $user ) && !$this->has_md5_password( $user ) ) {
                $to_hash++;
                $hash = $this->phpass_hash( $password );
                // PHP would interpret $ as a variable, so replace it in storage.
                $hash = str_replace( '$', '!', $hash );
                $quotes = "'" . '"';
                $pattern = "/[$quotes]${user}[$quotes]\s*=>\s*[$quotes]" . preg_quote( $password, '-' ) . "[$quotes]/";
                $replace = "'$user' => 'phpass:$hash' /* Password encrypted by YOURLS */ ";
                $count = 0;
                $configdata = preg_replace( $pattern, $replace, $configdata, -1, $count );
                // There should be exactly one replacement. Otherwise, fast fail.
                if ( $count != 1 ) {
                    debug_log( "Problem with preg_replace for password hash of user $user" );

                    return 'preg_replace problem';
                }
            }
        }

        if( $to_hash == 0 )

            return 0; // There was no password to encrypt

        $success = file_put_contents( $config_file, $configdata );
        if ( $success === FALSE ) {
            debug_log( 'Failed writing to ' . $config_file );

            return 'could not write file';
        }

        return true;
    }

    /**
     * Check to see if any passwords are stored as cleartext.
     *
     * @since 1.7
     * @return bool true if any passwords are cleartext
     */
    public function is_cleartext() {
        foreach ( $user_passwords as $user => $pwdata ) {
            if ( !$this->has_md5_password( $user ) && !$this->has_phpass_password( $user ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a user has a hashed password
     *
     * Check if a user password is 'md5:[38 chars]'.
     * TODO: deprecate this when/if we have proper user management with password hashes stored in the DB
     *
     * @since 1.7
     * @param string $user user login
     * @return bool true if password hashed, false otherwise
     */
    public function is_md5( $user ) {
        return(    isset( $user_passwords[ $user ] )
                && substr( $user_passwords[ $user ], 0, 4 ) == 'md5:'
                && strlen( $user_passwords[ $user ] ) == 42 // http://www.google.com/search?q=the+answer+to+life+the+universe+and+everything
               );
    }

    /**
     * Check if a user's password is hashed with PHPASS.
     *
     * Check if a user password is 'phpass:[lots of chars]'.
     * TODO: deprecate this when/if we have proper user management with password hashes stored in the DB
     *
     * @since 1.7
     * @param string $user user login
     * @return bool true if password hashed with PHPASS, otherwise false
     */
    public function is_phpass() {
        return( isset( $user_passwords[ $user ] )
                && substr( $user_passwords[ $user ], 0, 7 ) == 'phpass:'
        );
    }


    /**
     * The following code is a shim that helps users store passwords securely in config.php
     * by storing a password hash and removing the plaintext.
     *
     * @todo Remove this once real user management is implemented
     */
    public function plaintext_warning() {
        // Did we just fail at encrypting passwords ?
        if ( isset( $_GET['dismiss'] ) && $_GET['dismiss'] == 'hasherror' ) {
            yourls_update_option( 'defer_hashing_error', time() + 86400 * 7 ); // now + 1 week

        } else {

            // Encrypt passwords that are clear text
            if ( !defined( 'YOURLS_NO_HASH_PASSWORD' ) && yourls_has_cleartext_passwords() ) {
                $hash = yourls_hash_passwords_now( YOURLS_CONFIGFILE );
                if ( $hash === true ) {
                    // Hashing succesful. Remove flag from DB if any.
                    if( yourls_get_option( 'defer_hashing_error' ) )
                        yourls_delete_option( 'defer_hashing_error' );
                } else {
                    // It failed, display message for first time or if last time was a week ago
                    if ( time() > yourls_get_option( 'defer_hashing_error' ) or !yourls_get_option( 'defer_hashing_error' ) ) {
                        $message  = yourls_s( 'Could not auto-encrypt passwords. Error was: "%s".', $hash );
                        $message .= ' ';
                        $message .= yourls_s( '<a href="%s">Get help</a>.', 'http://yourls.org/userpassword' );
                        $message .= '</p><p>';
                        $message .= yourls_s( '<a href="%s">Click here</a> to dismiss this message for one week.', '?dismiss=hasherror' );

                        yourls_add_notice( $message );
                    }
                }
            }
        }
    }

}

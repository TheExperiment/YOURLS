<?php

/**
 * MySQL Wrapper
 *
 * @since 2.0
 * @copyright 2009-2014 YOURLS - MIT
 */

namespace YOURLS;

use YOURLS\Extensions\Filters;

/**
 * Connection with database
 *
 * Load everything (driver and subclasses) to talk with MySQL.
 */
class MySQL {
    
    private $filters;
    
    public function __construct() {
        $this->filters = new Filters;
    }
    

    /**
     * Pick the right DB class and return an instance
     *
     * @since 1.7
     * @param string $extension Optional: user defined choice
     * @return class $ydb DB class instance
     */
    public function set_driver() {

        // Auto-pick the driver. Priority: user defined, then PDO, then mysqli, then mysql
        if ( defined( 'YOURLS_DB_DRIVER' ) ) {
            $driver = strtolower( YOURLS_DB_DRIVER ); // accept 'MySQL', 'mySQL', etc
        } elseif ( extension_loaded( 'pdo_mysql' ) ) {
            $driver = 'pdo';
        } elseif ( extension_loaded( 'mysqli' ) ) {
            $driver = 'mysqli';
        } elseif ( extension_loaded( 'mysql' ) ) {
            $driver = 'mysql';
        } else {
            $driver = '';
        }

        // Set the new driver
        if ( in_array( $driver, array( 'mysql', 'mysqli', 'pdo' ) ) ) {
            $class = $this->require_files( $driver );
        }

        global $ydb;

        if ( !class_exists( $class, false ) ) {
            $ydb = new stdClass();
            die(
                _( 'YOURLS requires the mysql, mysqli or pdo_mysql PHP extension. No extension found. Check your server config, or contact your host.' )/*,
                _( 'Fatal error' ),
                503*/
            );
        }

        $this->filters->do_action( 'set_YOURLS_DB_driver', $driver );

        $ydb = new $class( YOURLS_DB_USER, YOURLS_DB_PASS, YOURLS_DB_NAME, YOURLS_DB_HOST );
        $ydb->YOURLS_DB_driver = $driver;

        debug_log( "Database driver: $driver" );
    }

    /**
     * Load required DB class files
     *
     * This goes in its own function to allow easier unit tests
     *
     * @since 1.7.1
     * @param string $driver DB driver
     * @return string name of the DB class to instantiate
     */
    public function require_files( $driver ) {
        require_once( YOURLS_INC . '/ezSQL/ez_sql_core.php' );
        require_once( YOURLS_INC . '/ezSQL/ez_sql_core_yourls.php' );
        require_once( YOURLS_INC . '/ezSQL/ez_sql_' . $driver . '.php' );
        require_once( YOURLS_INC . '/ezSQL/ez_sql_' . $driver . '_yourls.php' );

        return 'ezSQL_' . $driver . '_yourls';
    }

    /**
     * Connect to DB
     *
     * @since 1.0
     */
    public function connect() {
        global $ydb;

        if (   !defined( 'YOURLS_DB_USER' )
            or !defined( 'YOURLS_DB_PASS' )
            or !defined( 'YOURLS_DB_NAME' )
            or !defined( 'YOURLS_DB_HOST' )
        ) die ( _( 'Incorrect DB config, or could not connect to DB' )/*, _( 'Fatal error' ), 503 */);

        // Are we standalone or in the WordPress environment?
        if ( class_exists( 'wpdb', false ) ) {
            /* TODO: should we deprecate this? Follow WP dev in that area */
            $ydb =  new wpdb( YOURLS_DB_USER, YOURLS_DB_PASS, YOURLS_DB_NAME, YOURLS_DB_HOST );
        } else {
            $this->set_driver();
        }

        return $ydb;
    }

    /**
     * Return true if DB server is responding
     *
     * This function is supposed to be called right after get_all_options() has fired. It is not designed (yet) to
     * check for a responding server after several successful operation to check if the server has gone MIA
     *
     * @since 1.7.1
     */
    public function is_alive() {
        global $ydb;

        $alive = false;
        switch( $ydb->YOURLS_DB_driver ) {
            case 'pdo' :
                $alive = isset( $ydb->dbh );
                break;

            case 'mysql' :
                $alive = ( isset( $ydb->dbh ) && false !== $ydb->dbh );
                break;

            case 'mysqli' :
                $alive = ( null == mysqli_connect_error() );
                break;

            // Custom DB driver & class : delegate check
            default:
                $alive = apply_filter( 'is_db_alive_custom', false );
        }

        return $alive;
    }

    /**
     * Die with a DB error message
     *
     * @TODO in version 1.8 : use a new localized string, specific to the problem (ie: "DB is dead")
     *
     * @since 1.7.1
     */
    public function dead() {
        // Use any /user/db_error.php file
        if( file_exists( YOURLS_USERDIR . '/db_error.php' ) ) {
            include_once( YOURLS_USERDIR . '/db_error.php' );
            die();
        }

        die( _( 'Incorrect DB config, or could not connect to DB' )/*, _( 'Fatal error' ), 503 */);
    }

}

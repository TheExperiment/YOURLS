<?php

/**
 * MySQL Wrapper
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Database;

use YOURLS\Extensions\Filters;

/**
 * Connection with database
 *
 * Load everything (driver and subclasses) to talk with MySQL.
 */
class Database /*extends someThingLikeEzSQL*/ {

    /**
     * Driver for the database connection
     */
    private static $driver;

    /**
     * Driver for the database connection
     */
    private $logger;

    public function __construct() {
        $this->logger = new Logger();
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
        if ( !Options::is_set( 'db_driver' ) ) {
            if ( extension_loaded( 'pdo_mysql' ) ) {
                Options::set( 'db_driver', 'PDO' );
            } elseif ( extension_loaded( 'mysqli' ) ) {
                Options::set( 'db_driver', 'MySQLi' );
            } elseif ( extension_loaded( 'mysql' ) ) {
                Options::set( 'db_driver', 'MySQL' );
            } else {
                Options::set( 'db_driver', '' );
            }
        }

        // Set the new driver
        if ( !in_array( $driver, array( 'mysql', 'mysqli', 'pdo' ) ) ) {
            throw new DatabaseException( _( 'YOURLS requires the mysql, mysqli or pdo_mysql PHP extension. No extension found. Check your server config, or contact your host.' ),
            _( 'Fatal error' ),
            503
            );
        }

        Filters:do_action( 'set_YOURLS_DB_driver', $driver );
        self::$driver = 'YOURLS\\Database\\'.$driver;
        $ydb = new $driver( YOURLS_DB_USER, YOURLS_DB_PASS, YOURLS_DB_NAME, YOURLS_DB_HOST );
        $ydb->db_driver = $driver;

        $this->logger->addDebug( "Database Initialized", array( 'dirver', $driver ) );
    }

    /**
     * Connect to DB
     *
     * @since 1.0
     */
    public static function connect() {
        global $ydb;

        if (   !defined( 'YOURLS_DB_USER' )
            or !defined( 'YOURLS_DB_PASS' )
            or !defined( 'YOURLS_DB_NAME' )
            or !defined( 'YOURLS_DB_HOST' )
        ) throw new DatabaseException( _( 'Incorrect DB config, or could not connect to DB' ), _( 'Fatal error' ), 503 );

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
                $alive = Filters::apply_filter( 'is_db_alive_custom', false );
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

    /**
     * Escape a string or an array of strings before DB usage. ALWAYS escape before using in a SQL query. Thanks.
     *
     * @param string|array $data string or array of strings to be escaped
     * @return string|array escaped data
     */
    public static function escape( $data ) {
        if( is_array( $data ) ) {
            foreach( $data as $k => $v ) {
                if( is_array( $v ) ) {
                    $data[ $k ] = self::escape( $v );
                } else {
                    $data[ $k ] = self::escape_real( $v );
                }
            }
        } else {
            $data = self::escape_real( $data );
        }

        return $data;
    }

    /**
     * This function uses a "real" escape if possible, using PDO, MySQL or MySQLi functions,
     * with a fallback to a "simple" addslashes
     * If you're implementing a custom DB engine or a custom cache system, you can define an
     * escape function using filter 'custom_escape_real'
     *
     * @since 1.7
     * @param string $a string to be escaped
     * @return string escaped string
     */
    private static function escape_real( $string ) {
        global $ydb;
        if( isset( $ydb ) && ( $ydb instanceof ezSQLcore ) )

            return $ydb->escape( $string );

        // YOURLS DB classes have been bypassed by a custom DB engine or a custom cache layer
        return Filters::apply_filters( 'custom_escape_real', addslashes( $string ), $string );
    }

}

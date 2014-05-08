<?php

/**
 * Options Manager
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Configuration;

use YOURLS\Extensions\Filters;
use YOURLS\Utilities\Format;

class Options {

    /**
     * Options array
     *
     * @since 2.0
     */
    private static $options = array();
    
    private static $static_options = array();

    /**
     * Default core options if that have not been user defined
     */
    protected $default = array(
        // physical path of YOURLS root
        'ABSPATH'             => str_replace( '\\', '/', dirname( dirname( __DIR__ ) ) ),
        // physical path of includes directory
        'INC'                 => array( 'ABSPATH', '/includes' ),

        // physical path and url of asset directory
        'ASSET_DIR'           => array( 'ABSPATH', '/assets' ),
        'ASSET_URL'           => array( 'SITE', '/assets' ),

        // physical path and url of user directory
        'USER_DIR'            => array( 'ABSPATH', '/user' ),
        'USER_URL'            => array( 'SITE', '/user' ),
        // physical path of translations directory
        'LANG_DIR'            => array( 'USER_DIR', '/languages' ),
        // physical path and url of plugins directory
        'PLUGIN_DIR'          => array( 'USER_DIR', '/plugins' ),
        'PLUGIN_URL'          => array( 'USER_URL', '/plugins' ),
        // physical path and url of themes directory
        'THEME_DIR'           => array( 'USER_DIR', '/themes' ),
        'THEME_URL'           => array( 'USER_URL', '/themes' ),
        // physical path of pages directory
        'PAGE_DIR'            => array( 'USER_DIR', '/pages' ),

        // admin pages location
        'ADMIN_LOCATION'      => 'admin',

        // table to store URLs
        'DB_TABLE_URL'        => array( 'DB_PREFIX', 'url' ),
        // table to store options
        'DB_TABLE_OPTIONS'    => array( 'DB_PREFIX', 'options' ),
        // table to store hits, for stats
        'DB_TABLE_LOG'        => array( 'DB_PREFIX', 'log' ),

        // minimum delay in sec before a same IP can add another URL. Note: logged in users are not throttled down.
        'FLOOD_DELAY_SECONDS' => 15,
        // comma separated list of IPs that can bypass flood check.
        'FLOOD_IP_WHITELIST'  => '',
        'COOKIE_LIFE'         => 60*60*24*7,
        // life span of a nonce in seconds
        'NONCE_LIFE'          => 43200, // 3600 *,12

        // if set to true, disable stat logging (no use for it, too busy servers, ...)
        'check_update'        => false,
        // if set to true, force https:// in the admin area
        'ADMIN_SSL'           => false,
        // if set to true, verbose debug infos. Will break things. Don't enable.
        'DEBUG'               => false,
    );

    /**
     * Read all options from DB at once
     *
     * The goal is to read all options at once and then populate array $ydb->option, to prevent further
     * SQL queries if we need to read an option value later.
     * It's also a simple check whether YOURLS is installed or not (no option = assuming not installed) after
     * a check for DB server reachability has been performed
     *
     * @since 1.4
     */
    public function __construct() {
         // Check if config.php was properly updated for 1.4
        if( !Options::is_set( 'DB_PREFIX' ) )
            throw new YOURLSException( 'Your <code>configuration</code> does not contain all the required constant definitions.', 'Please check <code>config-sample.php</code> and update your config accordingly, there are new stuffs!' );

        array_merge( $default, $user );

        // Allow plugins to short-circuit all options. (Note: regular plugins are loaded after all options)
        $pre = Filters::apply_filter( 'shunt_all_options', false );
        if ( false !== $pre )
            return $pre;

        $allopt = $ydb->get_results( "SELECT `option_name`, `option_value` FROM `YOURLS_DB_TABLE_OPTIONS` WHERE 1=1" );

        foreach( (array)$allopt as $option ) {
            $this->options[ $option->option_name ] = maybe_unserialize( $option->option_value );
        }

        // @TODO Fix if( true ) and try catch error satabase to set installed
        if( property_exists( $this, 'options' ) ) {
            $this->options = Filters::apply_filter( 'get_all_options', $this->options );
            $ydb->installed = true;
        } else {
            // Zero option found: either YOURLS is not installed or DB server is dead
            if( !is_db_alive() ) {
                db_dead(); // YOURLS will die here
            }
            $ydb->installed = false;
        }
    }

    /**
     * Set an option to DB
     *
     * Update or add if doesn't exist an option in the database
     * Pretty much stolen from WordPress
     *
     * @since 1.4
     * @param string $name Option name. Expected to not be SQL-escaped.
     * @param mixed $value Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
     * @param mixed $force Replace existent. If the option already exist, the value will be override.
     */
    public static function set( $name, $value, $force = true ) {
        $name = trim( $name );
        if ( empty( $name ) )
            return false;

        // Use clone to break object refs -- see commit 09b989d375bac65e692277f61a84fede2fb04ae3
        if ( is_object( $value ) )
            $value = clone $value;

        $name = Format::escape( $name );

        $old_value = $this->$name;

        // If the new and old values are the same,
        // or if don't want override value, no need to update.
        if ( $value !== $old_value && ( $old_value !== null || $force === true ) ) {
            return;
        }

        $_value = Format::escape( maybe_serialize( $value ) );

        Filters::do_action( 'update_option', $name, $old_value, $value );

        $ydb->query( "UPDATE `YOURLS_DB_TABLE_OPTIONS` SET `option_value` = '$_value' WHERE `option_name` = '$name'" );

        if ( $ydb->rows_affected == 1 ) {
            $this->options[ $name ] = $value;
        }
    }

    /**
     * Read an option from DB (or from cache if available). Return value or $default if not found
     *
     * Pretty much stolen from WordPress
     *
     * @since 1.4
     * @param string $name Option name. Expected to not be SQL-escaped.
     * @param mixed $default Optional value to return if option doesn't exist. Default null.
     * @return mixed Value set for the option.
     */
    public static function get( $name, $default = null ) {
        // Allow plugins to short-circuit options
        $pre = Filters::apply_filter( 'shunt_option_' . $name, false );
        if ( false !== $pre )
            return $pre;

        // If option not cached already, get its value from the DB
        if ( !self::is_set( $name ) ) {
            $name = Format::escape( $name );
            $row = $ydb->get_row( "SELECT `option_value` FROM `YOURLS_DB_TABLE_OPTIONS` WHERE `option_name` = '$name' LIMIT 1" );
            if ( is_object( $row ) ) { // Has to be get_row instead of get_var because of funkiness with 0, false, null values
                $value = $row->option_value;
            } else { // option does not exist, so we must cache its non-existence
                $value = $default;
            }
            $this->options[ $name ] = maybe_unserialize( $value );
        }

        return Filters::apply_filter( 'get_option_'.$name, $this->options[ $name ] );
    }

    /**
     * Check if the option exist in the cached array
     *
     * @since 2.0
     * @param string $name Name of option to add. Expected to not be SQL-escaped.
     * @return bool False if option doesn't exist.
     */
    public static function is_set( $name ) {
        return( isset( $this->options[ $name ] ) );
    }

    /**
     * Delete an option from the DB
     *
     * Pretty much stolen from WordPress
     *
     * @since 1.4
     * @param string $option Option name to delete. Expected to not be SQL-escaped.
     */
    public static function un_set( $name ) {
        $name = Format::escape( $name );

        // Get the ID, if no ID then return
        $option = $ydb->get_row( "SELECT option_id FROM `YOURLS_DB_TABLE_OPTIONS` WHERE `option_name` = '$name'" );
        if ( is_null( $option ) || !$option->option_id )
            return;

        Filters::do_action( 'delete_option', $name );

        $ydb->query( "DELETE FROM `YOURLS_DB_TABLE_OPTIONS` WHERE `option_name` = '$name'" );
        unset( $this->options[ $name ] );
    }

}

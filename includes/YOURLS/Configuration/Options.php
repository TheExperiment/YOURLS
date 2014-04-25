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
    public static function __construct() {
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
    public static function __set( $name, $value, $force = true ) {
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
    public static function __get( $name, $default = null ) {
        // Allow plugins to short-circuit options
        $pre = Filters::apply_filter( 'shunt_option_' . $name, false );
        if ( false !== $pre )
            return $pre;

        // If option not cached already, get its value from the DB
        if ( !isset( $this->$name ) ) {
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
    public static function __isset( $name ) {
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
    public static function __unset( $name ) {
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

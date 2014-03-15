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

class Options {

    /**
     * Read an option from DB (or from cache if available). Return value or $default if not found
     *
     * Pretty much stolen from WordPress
     *
     * @since 1.4
     * @param string $option Option name. Expected to not be SQL-escaped.
     * @param mixed $default Optional value to return if option doesn't exist. Default false.
     * @return mixed Value set for the option.
     */
    public function get_option( $option_name, $default = false ) {
        global $ydb;

        // Allow plugins to short-circuit options
        $pre = Filters::apply_filter( 'shunt_option_'.$option_name, false );
        if ( false !== $pre )
            return $pre;

        // If option not cached already, get its value from the DB
        if ( !isset( $ydb->option[$option_name] ) ) {
            $table = YOURLS_DB_TABLE_OPTIONS;
            $option_name = escape( $option_name );
            $row = $ydb->get_row( "SELECT `option_value` FROM `$table` WHERE `option_name` = '$option_name' LIMIT 1" );
            if ( is_object( $row ) ) { // Has to be get_row instead of get_var because of funkiness with 0, false, null values
                $value = $row->option_value;
            } else { // option does not exist, so we must cache its non-existence
                $value = $default;
            }
            $ydb->option[ $option_name ] = maybe_unserialize( $value );
        }

        return Filters::apply_filter( 'get_option_'.$option_name, $ydb->option[$option_name] );
    }

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
    public function get_all_options() {
        global $ydb;

        // Allow plugins to short-circuit all options. (Note: regular plugins are loaded after all options)
        $pre = Filters::apply_filter( 'shunt_all_options', false );
        if ( false !== $pre )
            return $pre;

        $table = YOURLS_DB_TABLE_OPTIONS;

        $allopt = $ydb->get_results( "SELECT `option_name`, `option_value` FROM `$table` WHERE 1=1" );

        foreach( (array)$allopt as $option ) {
            $ydb->option[ $option->option_name ] = maybe_unserialize( $option->option_value );
        }

        if( property_exists( $ydb, 'option' ) ) {
            $ydb->option = Filters::apply_filter( 'get_all_options', $ydb->option );
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
     * Update (add if doesn't exist) an option to DB
     *
     * Pretty much stolen from WordPress
     *
     * @since 1.4
     * @param string $option Option name. Expected to not be SQL-escaped.
     * @param mixed $newvalue Option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
     * @return bool False if value was not updated, true otherwise.
     */
    public function update_option( $option_name, $newvalue ) {
        global $ydb;
        $table = YOURLS_DB_TABLE_OPTIONS;

        $option_name = trim( $option_name );
        if ( empty( $option_name ) )
            return false;

        // Use clone to break object refs -- see commit 09b989d375bac65e692277f61a84fede2fb04ae3
        if ( is_object( $newvalue ) )
            $newvalue = clone $newvalue;

        $option_name = escape( $option_name );

        $oldvalue = get_option( $option_name );

        // If the new and old values are the same, no need to update.
        if ( $newvalue === $oldvalue )
            return false;

        if ( false === $oldvalue ) {
            add_option( $option_name, $newvalue );

            return true;
        }

        $_newvalue = escape( maybe_serialize( $newvalue ) );

        Filters::do_action( 'update_option', $option_name, $oldvalue, $newvalue );

        $ydb->query( "UPDATE `$table` SET `option_value` = '$_newvalue' WHERE `option_name` = '$option_name'" );

        if ( $ydb->rows_affected == 1 ) {
            $ydb->option[ $option_name ] = $newvalue;

            return true;
        }

        return false;
    }

    /**
     * Add an option to the DB
     *
     * Pretty much stolen from WordPress
     *
     * @since 1.4
     * @param string $option Name of option to add. Expected to not be SQL-escaped.
     * @param mixed $value Optional option value. Must be serializable if non-scalar. Expected to not be SQL-escaped.
     * @return bool False if option was not added and true otherwise.
     */
    public function add_option( $name, $value = '' ) {
        global $ydb;
        $table = YOURLS_DB_TABLE_OPTIONS;

        $name = trim( $name );
        if ( empty( $name ) )
            return false;

        // Use clone to break object refs -- see commit 09b989d375bac65e692277f61a84fede2fb04ae3
        if ( is_object( $value ) )
            $value = clone $value;

        $name = escape( $name );

        // Make sure the option doesn't already exist
        if ( false !== get_option( $name ) )
            return false;

        $_value = escape( maybe_serialize( $value ) );

        Filters::do_action( 'add_option', $name, $_value );

        $ydb->query( "INSERT INTO `$table` (`option_name`, `option_value`) VALUES ('$name', '$_value')" );
        $ydb->option[ $name ] = $value;

        return true;
    }

    /**
     * Delete an option from the DB
     *
     * Pretty much stolen from WordPress
     *
     * @since 1.4
     * @param string $option Option name to delete. Expected to not be SQL-escaped.
     * @return bool True, if option is successfully deleted. False on failure.
     */
    public function delete_option( $name ) {
        global $ydb;
        $table = YOURLS_DB_TABLE_OPTIONS;
        $name = escape( $name );

        // Get the ID, if no ID then return
        $option = $ydb->get_row( "SELECT option_id FROM `$table` WHERE `option_name` = '$name'" );
        if ( is_null( $option ) || !$option->option_id )
            return false;

        Filters::do_action( 'delete_option', $name );

        $ydb->query( "DELETE FROM `$table` WHERE `option_name` = '$name'" );
        unset( $ydb->option[ $name ] );

        return true;
    }

}

<?php

/**
 * Upgrade Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Administration;

/**
 * Summary of Upgrade
 */
class Upgrade {

    /**
     * Upgrade YOURLS and DB schema
     *
     */
    public function __construct( $step, $oldver, $newver, $oldsql, $newsql ) {
        // other upgrades which are done in a single pass
        switch( $step ) {

            case 1:
            case 2:
                if( $oldsql < 482 )
                    $this->to_15();

                redirect_javascript( admin_url( "upgrade?step=3" ) );

                break;

            case 3:
                // Update options to reflect latest version
                update_option( 'version', VERSION );
                update_option( 'db_version', YOURLS_DB_VERSION );
                break;
        }
    }

    /**
     * Main func for upgrade from 1.4.3 to 1.5
     *
     */
    public function to_15( ) {
        // Create empty 'active_plugins' entry in the option if needed
        if( get_option( 'active_plugins' ) === false )
            add_option( 'active_plugins', array() );
        echo "<p>Enabling the plugin API. Please wait...</p>";

        // Alter URL table to store titles
        global $ydb;
        $table_url = YOURLS_DB_TABLE_URL;
        $sql = "ALTER TABLE `$table_url` ADD `title` TEXT CHARACTER SET utf8 AFTER `url`;";
        $ydb->query( $sql );
        echo "<p>Updating table structure. Please wait...</p>";

        // Update .htaccess
        create_htaccess();
        echo "<p>Updating .htaccess file. Please wait...</p>";
    }

}

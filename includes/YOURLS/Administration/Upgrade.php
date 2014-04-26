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
                if( $oldsql < 2000 )
                    $this->to_2_0();

                redirect_javascript( admin_url( "upgrade?step=3" ) );

                break;

            case 3:
                // Update options to reflect latest version
                Options::$version = VERSION;
                Options::$db_version = YOURLS_DB_VERSION;
                break;
        }
    }

    /**
     * Main func for upgrade from 1.7+ to 2.0
     *
     */
    public function to_2_0( ) {
    }

}

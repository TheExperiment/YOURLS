<?php

/**
 * Upgrade Wrapper
 *
 * @since 2.0
 * @copyright 2009-2014 YOURLS - MIT
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
                    upgrade_to_15();

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
    public function upgrade_to_15( ) {
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

    /**
     * Update options to reflect new version
     *
     */
    public function update_options_to_14() {
        update_option( 'version', '1.4' );
        update_option( 'db_version', '200' );

        if( defined('YOURLS_DB_TABLE_NEXTDEC') ) {
            global $ydb;
            $table = YOURLS_DB_TABLE_NEXTDEC;
            $next_id = $ydb->get_var("SELECT `next_id` FROM `$table`");
            update_option( 'next_id', $next_id );
            @$ydb->query( "DROP TABLE `$table`" );
        } else {
            update_option( 'next_id', 1 ); // In case someone mistakenly deleted the next_id constant or table too early
        }
    }

    /**
     * Create new tables for YOURLS 1.4: options & log
     *
     */
    public function create_tables_for_14() {
        global $ydb;

        $queries = array();

        $queries[YOURLS_DB_TABLE_OPTIONS] =
            'CREATE TABLE IF NOT EXISTS `'.YOURLS_DB_TABLE_OPTIONS.'` ('.
            '`option_id` int(11) unsigned NOT NULL auto_increment,'.
            '`option_name` varchar(64) NOT NULL default "",'.
            '`option_value` longtext NOT NULL,'.
            'PRIMARY KEY (`option_id`,`option_name`),'.
            'KEY `option_name` (`option_name`)'.
            ');';

        $queries[YOURLS_DB_TABLE_LOG] =
            'CREATE TABLE IF NOT EXISTS `'.YOURLS_DB_TABLE_LOG.'` ('.
            '`click_id` int(11) NOT NULL auto_increment,'.
            '`click_time` datetime NOT NULL,'.
            '`shorturl` varchar(200) NOT NULL,'.
            '`referrer` varchar(200) NOT NULL,'.
            '`user_agent` varchar(255) NOT NULL,'.
            '`ip_address` varchar(41) NOT NULL,'.
            '`country_code` char(2) NOT NULL,'.
            'PRIMARY KEY (`click_id`),'.
            'KEY `shorturl` (`shorturl`)'.
            ');';

        foreach( $queries as $query ) {
            $ydb->query( $query ); // There's no result to be returned to check if table was created (except making another query to check table existence, which we'll avoid)
        }

        echo "<p>New tables created. Please wait...</p>";

    }

    /**
     * Alter table structure, part 1 (change schema, drop index)
     *
     */
    public function alter_url_table_to_14() {
        global $ydb;
        $table = YOURLS_DB_TABLE_URL;

        $alters = array();
        $results = array();
        $alters[] = "ALTER TABLE `$table` CHANGE `id` `keyword` VARCHAR( 200 ) NOT NULL";
        $alters[] = "ALTER TABLE `$table` CHANGE `url` `url` TEXT NOT NULL";
        $alters[] = "ALTER TABLE `$table` DROP PRIMARY KEY";

        foreach ( $alters as $query ) {
            $ydb->query( $query );
        }

        echo "<p>Structure of existing tables updated. Please wait...</p>";
    }

    /**
     * Alter table structure, part 2 (recreate indexes after the table is up to date)
     *
     */
    public function alter_url_table_to_14_part_two() {
        global $ydb;
        $table = YOURLS_DB_TABLE_URL;

        $alters = array();
        $alters[] = "ALTER TABLE `$table` ADD PRIMARY KEY ( `keyword` )";
        $alters[] = "ALTER TABLE `$table` ADD INDEX ( `ip` )";
        $alters[] = "ALTER TABLE `$table` ADD INDEX ( `timestamp` )";

        foreach ( $alters as $query ) {
            $ydb->query( $query );
        }

        echo "<p>New table index created</p>";
    }

    /**
     * Convert each link from 1.3 (id) to 1.4 (keyword) structure
     *
     */
    public function update_table_to_14() {
        global $ydb;
        $table = YOURLS_DB_TABLE_URL;

        // Modify each link to reflect new structure
        $chunk = 45;
        $from = isset($_GET['from']) ? intval( $_GET['from'] ) : 0 ;
        $total = get_db_stats();
        $total = $total['total_links'];

        $sql = "SELECT `keyword`,`url` FROM `$table` WHERE 1=1 ORDER BY `url` ASC LIMIT $from, $chunk ;";

        $rows = $ydb->get_results($sql);

        $count = 0;
        $queries = 0;
        foreach( $rows as $row ) {
            $keyword = $row->keyword;
            $url = $row->url;
            $newkeyword = int2string( $keyword );
            $ydb->query("UPDATE `$table` SET `keyword` = '$newkeyword' WHERE `url` = '$url';");
            if( $ydb->result === true ) {
                $queries++;
            } else {
                echo "<p>Huho... Could not update rown with url='$url', from keyword '$keyword' to keyword '$newkeyword'</p>"; // Find what went wrong :/
            }
            $count++;
        }

        // All done for this chunk of queries, did it all go as expected?
        $success = true;
        if( $count != $queries ) {
            $success = false;
            $num = $count - $queries;
            echo "<p>$num error(s) occured while updating the URL table :(</p>";
        }

        if ( $count == $chunk ) {
            // there are probably other rows to convert
            $from = $from + $chunk;
            $remain = $total - $from;
            echo "<p>Converted $chunk database rows ($remain remaining). Continuing... Please do not close this window until it's finished!</p>";
            redirect_javascript( admin_url( "upgrade?step=2&oldver=1.3&newver=1.4&oldsql=100&newsql=200&from=$from" ), $success );
        } else {
            // All done
            echo '<p>All rows converted! Please wait...</p>';
            redirect_javascript( admin_url( "upgrade?step=3&oldver=1.3&newver=1.4&oldsql=100&newsql=200" ), $success );
        }

    }

    /**
     * Clean .htaccess as it existed before 1.4. Returns boolean
     *
     */
    public function clean_htaccess_for_14() {
        $filename = YOURLS_ABSPATH.'/.htaccess';

        $result = false;
        if( is_writeable( $filename ) ) {
            $contents = implode( '', file( $filename ) );
            // remove "ShortURL" block
            $contents = preg_replace( '/# BEGIN ShortURL.*# END ShortURL/s', '', $contents );
            // comment out deprecated RewriteRule
            $find = 'RewriteRule .* - [E=REMOTE_YOURLS_USER:%{HTTP:Authorization},L]';
            $replace = "# You can safely remove this 5 lines block -- it's no longer used in YOURLS\n".
                    "# $find";
            $contents = str_replace( $find, $replace, $contents );

            // Write cleaned file
            $f = fopen( $filename, 'w' );
            fwrite( $f, $contents );
            fclose( $f );

            $result = true;
        }

        return $result;
    }

}

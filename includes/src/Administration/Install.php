<?php

/**
 * MySQL Wrapper
 *
 * @since 2.0
 * @copyright 2009-2014 YOURLS - MIT
 */

namespace YOURLS\Administration;

/**
 * Summary of Install
 */
class Install {

    /**
     * Check if server has MySQL 5.0+
     *
     */
    function check_database_version() {
        global $ydb;

        // Attempt to get MySQL server version, check result and if error count increased
        $num_errors1 = count( $ydb->captured_errors );
        $version     = get_database_version();
        $num_errors2 = count( $ydb->captured_errors );

        if( $version == NULL || ( $num_errors2 > $num_errors1 ) ) {
            die( _( 'Incorrect DB config, or could not connect to DB' ), _( 'Fatal error' ), 503 );
        }

        return ( version_compare( '5.0', $version ) <= 0 );
    }

    /**
     * Get DB version
     *
     * The regex removes everything that's not a number at the start of the string, or remove anything that's not a number and what
     * follows after that.
     *   'omgmysql-5.5-ubuntu-4.20' => '5.5'
     *   'mysql5.5-ubuntu-4.20'     => '5.5'
     *   '5.5-ubuntu-4.20'          => '5.5'
     *   '5.5-beta2'                => '5.5'
     *   '5.5'                      => '5.5'
     *
     * @since 1.7
     * @return string sanitized DB version
     */
    function get_database_version() {
        global $ydb;

        return preg_replace( '/(^[^0-9]*)|[^0-9.].*/', '', $ydb->mysql_version() );
    }

    /**
     * Check if PHP > 5.2
     *
     */
    function check_php_version() {
        return ( version_compare( '5.2', phpversion() ) <= 0 );
    }

    /**
     * Check if server is an Apache
     *
     */
    function is_apache() {
        if( !array_key_exists( 'SERVER_SOFTWARE', $_SERVER ) )

            return false;
        return (
           strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false
        || strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== false
        );
    }

    /**
     * Check if server is running IIS
     *
     */
    function is_iis() {
        return ( array_key_exists( 'SERVER_SOFTWARE', $_SERVER ) ? ( strpos( $_SERVER['SERVER_SOFTWARE'], 'IIS' ) !== false ) : false );
    }

    /**
     * Create .htaccess or web.config. Returns boolean
     *
     */
    function create_htaccess() {
        $host = parse_url( SITE );
        $path = ( isset( $host['path'] ) ? $host['path'] : '' );

        if ( is_iis() ) {
            // Prepare content for a web.config file
            $content = array(
                '<?'.'xml version="1.0" encoding="UTF-8"?>',
                '<configuration>',
                '    <system.webServer>',
                '        <security>',
                '            <requestFiltering allowDoubleEscaping="true" />',
                '        </security>',
                '        <rewrite>',
                '            <rules>',
                '                <rule name="YOURLS" stopProcessing="true">',
                '                    <match url="^(.*)$" ignoreCase="false" />',
                '                    <conditions>',
                '                        <add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />',
                '                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" negate="true" />',
                '                    </conditions>',
                '                    <action type="Rewrite" url="'.$path.'/yourls-loader.php" appendQueryString="true" />',
                '                </rule>',
                '            </rules>',
                '        </rewrite>',
                '    </system.webServer>',
                '</configuration>',
            );

            $filename = ABSPATH.'/web.config';
            $marker = 'none';

        } else {
            // Prepare content for a .htaccess file
            $content = array(
                '<IfModule mod_rewrite.c>',
                'RewriteEngine On',
                'RewriteBase '.$path.'/',
                'RewriteCond %{REQUEST_FILENAME} !-f',
                'RewriteCond %{REQUEST_FILENAME} !-d',
                'RewriteRule ^.*$ '.$path.'/yourls-loader.php [L]',
                '</IfModule>',
            );

            $filename = ABSPATH.'/.htaccess';
            $marker = 'YOURLS';

        }

        return ( insert_with_markers( $filename, $marker, $content ) );
    }

    /**
     * Inserts $insertion (text in an array of lines) into $filename (.htaccess) between BEGIN/END $marker block. Returns bool. Stolen from WP
     *
     */
    function insert_with_markers( $filename, $marker, $insertion ) {
        if ( !file_exists( $filename ) || is_writeable( $filename ) ) {
            if ( !file_exists( $filename ) ) {
                $markerdata = '';
            } else {
                $markerdata = explode( "\n", implode( '', file( $filename ) ) );
            }

            if ( !$f = @fopen( $filename, 'w' ) )
                return false;

            $foundit = false;
            if ( $markerdata ) {
                $state = true;
                foreach ( $markerdata as $n => $markerline ) {
                    if ( strpos( $markerline, '# BEGIN ' . $marker ) !== false )
                        $state = false;
                    if ( $state ) {
                        if ( $n + 1 < count( $markerdata ) )
                            fwrite( $f, "{$markerline}\n" );
                        else
                            fwrite( $f, "{$markerline}" );
                    }
                    if ( strpos( $markerline, '# END ' . $marker ) !== false ) {
                        if ( $marker != 'none' )
                            fwrite( $f, "# BEGIN {$marker}\n" );
                        if ( is_array( $insertion ) )
                            foreach ( $insertion as $insertline )
                                fwrite( $f, "{$insertline}\n" );
                        if ( $marker != 'none' )
                            fwrite( $f, "# END {$marker}\n" );
                        $state = true;
                        $foundit = true;
                    }
                }
            }
            if ( !$foundit ) {
                if ( $marker != 'none' )
                    fwrite( $f, "\n\n# BEGIN {$marker}\n" );
                foreach ( $insertion as $insertline )
                    fwrite( $f, "{$insertline}\n" );
                if ( $marker != 'none' )
                    fwrite( $f, "# END {$marker}\n\n" );
            }
            fclose( $f );

            return true;
        } else {
            return false;
        }
    }

    /**
     * Create MySQL tables. Return array( 'success' => array of success strings, 'errors' => array of error strings )
     *
     */
    function create_sql_tables() {
        global $ydb;

        $error_msg = array();
        $success_msg = array();

        // Create Table Query
        $create_tables = array();
        $create_tables[DB_TABLE_URL] =
            'CREATE TABLE IF NOT EXISTS `'.DB_TABLE_URL.'` ('.
            '`keyword` varchar(200) BINARY NOT NULL,'.
            '`url` text BINARY NOT NULL,'.
            '`title` text CHARACTER SET utf8,'.
            '`timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'.
            '`ip` VARCHAR(41) NOT NULL,'.
            '`clicks` INT(10) UNSIGNED NOT NULL,'.
            ' PRIMARY KEY  (`keyword`),'.
            ' KEY `timestamp` (`timestamp`),'.
            ' KEY `ip` (`ip`)'.
            ');';

        $create_tables[DB_TABLE_OPTIONS] =
            'CREATE TABLE IF NOT EXISTS `'.DB_TABLE_OPTIONS.'` ('.
            '`option_id` bigint(20) unsigned NOT NULL auto_increment,'.
            '`option_name` varchar(64) NOT NULL default "",'.
            '`option_value` longtext NOT NULL,'.
            'PRIMARY KEY  (`option_id`,`option_name`),'.
            'KEY `option_name` (`option_name`)'.
            ') AUTO_INCREMENT=1 ;';

        $create_tables[DB_TABLE_LOG] =
            'CREATE TABLE IF NOT EXISTS `'.DB_TABLE_LOG.'` ('.
            '`click_id` int(11) NOT NULL auto_increment,'.
            '`click_time` datetime NOT NULL,'.
            '`shorturl` varchar(200) BINARY NOT NULL,'.
            '`referrer` varchar(200) NOT NULL,'.
            '`user_agent` varchar(255) NOT NULL,'.
            '`ip_address` varchar(41) NOT NULL,'.
            '`country_code` char(2) NOT NULL,'.
            'PRIMARY KEY  (`click_id`),'.
            'KEY `shorturl` (`shorturl`)'.
            ') AUTO_INCREMENT=1 ;';

        $create_table_count = 0;

        $ydb->show_errors = true;

        // Create tables
        foreach ( $create_tables as $table_name => $table_query ) {
            $ydb->query( $table_query );
            $create_success = $ydb->query( "SHOW TABLES LIKE '$table_name'" );
            if( $create_success ) {
                $create_table_count++;
                $success_msg[] = s( "Table '%s' created.", $table_name );
            } else {
                $error_msg[] = s( "Error creating table '%s'.", $table_name );
            }
        }

        // Initializes the option table
        if( !initialize_options() )
            $error_msg[] = _( 'Could not initialize options' );

        // Insert sample links
        if( !insert_sample_links() )
            $error_msg[] = _( 'Could not insert sample short URLs' );

        // Check results of operations
        if ( sizeof( $create_tables ) == $create_table_count ) {
            $success_msg[] = _( 'YOURLS tables successfully created.' );
        } else {
            $error_msg[] = _( 'Error creating YOURLS tables.' );
        }

        return array( 'success' => $success_msg, 'error' => $error_msg );
    }

    /**
     * Initializes the option table
     *
     * Each update_option() returns either true on success (option updated) or false on failure (new value == old value, or
     * for some reason it could not save to DB).
     * Since true & true & true = 1, we cast it to boolean type to return true (or false)
     *
     * @since 1.7
     * @return bool
     */
    function initialize_options() {
        return ( bool ) (
              update_option( 'version', VERSION )
            & update_option( 'db_version', DB_VERSION )
            & update_option( 'next_id', 1 )
        );
    }

    /**
     * Populates the URL table with a few sample links
     *
     * @since 1.7
     * @return bool
     */
    function insert_sample_links() {
        $link1 = add_new_link( 'http://blog.yourls.org/', 'yourlsblog', 'YOURLS\' Blog' );
        $link2 = add_new_link( 'http://yourls.org/',      'yourls',     'YOURLS: Your Own URL Shortener' );
        $link3 = add_new_link( 'http://ozh.org/',         'ozh',        'ozh.org' );

        return ( bool ) (
              $link1['status'] == 'success'
            & $link2['status'] == 'success'
            & $link3['status'] == 'success'
        );
    }

    /**
     * Toggle maintenance mode. Inspired from WP. Returns true for success, false otherwise
     *
     */
    function maintenance_mode( $maintenance = true ) {

        $file = ABSPATH . '/.maintenance' ;

        // Turn maintenance mode on : create .maintenance file
        if ( (bool)$maintenance ) {
            if ( ! ( $fp = @fopen( $file, 'w' ) ) )
                return false;

            $maintenance_string = '<?php $maintenance_start = ' . time() . '; ?>';
            @fwrite( $fp, $maintenance_string );
            @fclose( $fp );
            @chmod( $file, 0644 ); // Read and write for owner, read for everybody else

            // Not sure why the fwrite would fail if the fopen worked... Just in case
            return( is_readable( $file ) );

            // Turn maintenance mode off : delete the .maintenance file
        } else {
            return @unlink($file);
        }
    }

}
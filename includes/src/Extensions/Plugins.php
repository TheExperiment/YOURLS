<?php

/**
 * Plugins Wrapper
 *
 * @since 1.5
 * @copyright 2009-2014 YOURLS - MIT
 */

namespace YOURLS\Extensions;

/**
 * Plugins API
 * 
 * @author Ozh
 * @since 1.5
 */
class Plugins {

    /**
     * Return number of active plugins
     *
     * @return integer Number of activated plugins
     */
    public function has_active_plugins( ) {
        global $ydb;

        if( !property_exists( $ydb, 'plugins' ) || !$ydb->plugins )
            $ydb->plugins = array();

        return count( $ydb->plugins );
    }

    /**
     * List plugins in /user/plugins
     *
     * @global object $ydb Storage of mostly everything YOURLS needs to know
     * @return array Array of [/plugindir/plugin.php]=>array('Name'=>'Ozh', 'Title'=>'Hello', )
     */
    public function get_plugins( $category = 'plugins' ) {
        if( $category == 'themes' )
            $plugins = (array) glob( YOURLS_THEMEDIR .'/*/theme.css');
        else
        $plugins = (array) glob( YOURLS_PLUGINDIR .'/*/plugin.php');

        if( !$plugins )

            return array();

        foreach( $plugins as $key => $plugin ) {
            $_plugin = plugin_basename( $plugin, $category );
            $plugins[ $_plugin ] = get_plugin_data( $plugin );
            unset( $plugins[ $key ] );
        }

        return $plugins;
    }

    /**
     * Check if a plugin is active
     *
     * @param string $plugin Physical path to plugin file
     * @return bool
     */
    public function is_active_plugin( $plugin ) {
        if( !$this->has_active_plugins( ) )

            return false;

        global $ydb;
        $plugin = $this->plugin_basename( $plugin );

        return in_array( $plugin, $ydb->plugins );

    }

    /**
     * Parse a plugin header
     *
     * @param string $file Physical path to plugin file
     * @return array Array of 'Field'=>'Value' from plugin comment header lines of the form "Field: Value"
     */
    public function get_plugin_data( $file ) {
        $fp = fopen( $file, 'r' ); // assuming $file is readable, since load_plugins() filters this
        $data = fread( $fp, 8192 ); // get first 8kb
        fclose( $fp );

        // Capture all the header within first comment block
        if( !preg_match( '!.*?/\*(.*?)\*/!ms', $data, $matches ) )

            return array();

        // Capture each line with "Something: some text"
        unset( $data );
        $lines = preg_split( "[\n|\r]", $matches[1] );
        unset( $matches );

        $plugin_data = array();
        foreach( $lines as $line ) {
            if( !preg_match( '!(.*?):\s+(.*)!', $line, $matches ) )
                continue;

            list( $null, $field, $value ) = array_map( 'trim', $matches);
            $plugin_data[ $field ] = $value;
        }

        return $plugin_data;
    }

    /**
     * Include active plugins
     */
    public function load_plugins() {
        // Don't load plugins when installing or updating
        if( is_installing() OR is_upgrading() )

            return;

        $active_plugins = get_option( 'active_plugins' );
        if( false === $active_plugins )

            return;

        global $ydb;
        $ydb->plugins = array();

        if( defined( 'YOURLS_DEBUG' ) && YOURLS_DEBUG == true )
            $ydb->debug_log[] = 'Plugins: ' . count( $active_plugins );

        foreach( (array)$active_plugins as $key=>$plugin ) {
            if( $this->validate_plugin_file( YOURLS_PLUGINDIR.'/'.$plugin ) ) {
                include_once( YOURLS_PLUGINDIR.'/'.$plugin );
                $ydb->plugins[] = $plugin;
                unset( $active_plugins[$key] );
            }
        }

        // $active_plugins should be empty now, if not, a plugin could not be find: remove it
        if( count( $active_plugins ) ) {
            update_option( 'active_plugins', $ydb->plugins );
            $message = n( 'Could not find and deactivated plugin:', 'Could not find and deactivated plugins:', count( $active_plugins ) );
            $missing = '<strong>'.join( '</strong>, <strong>', $active_plugins ).'</strong>';
            add_notice( $message .' '. $missing );
        }
    }

    /**
     * Check if a file is safe for inclusion (well, "safe", no guarantee)
     *
     * @param string $file Full pathname to a file
     * @return bool
     */
    public function validate_plugin_file( $file ) {
        if (
            false !== strpos( $file, '..' )
            OR
            false !== strpos( $file, './' )
            OR
            ( 'plugin.php' !== substr( $file, -10 )	&& 'theme.php' !== substr( $file, -9 ) )	// a plugin must be named 'plugin.php', a theme must be named 'theme.php'
            OR
            !is_readable( $file )
        )

            return false;

        return true;
    }

    /**
     * Activate a plugin
     *
     * @param string $plugin Plugin filename (full or relative to plugins directory)
     * @return mixed string if error or true if success
     */
    public function activate_plugin( $plugin ) {
        // validate file
        $plugin = $this->plugin_basename( $plugin );
        $plugindir = sanitize_filename( YOURLS_PLUGINDIR );
        if( !$this->validate_plugin_file( $plugindir.'/'.$plugin ) )

            return _( 'Not a valid plugin file' );

        // check not activated already
        global $ydb;
        if( $this->has_active_plugins() && in_array( $plugin, $ydb->plugins ) )

            return _( 'Plugin already activated' );

        // attempt activation. TODO: uber cool fail proof sandbox like in WP.
        ob_start();
        include_once( YOURLS_PLUGINDIR.'/'.$plugin );
        if ( ob_get_length() > 0 ) {
            // there was some output: error
            $output = ob_get_clean();

            return s( 'Plugin generated unexpected output. Error was: <br/><pre>%s</pre>', $output );
        }

        // so far, so good: update active plugin list
        $ydb->plugins[] = $plugin;
        update_option( 'active_plugins', $ydb->plugins );
        do_action( 'activated_plugin', $plugin );
        do_action( 'activated_' . $plugin );

        return true;
    }

    /**
     * Deactivate a plugin
     *
     * @param string $plugin Plugin filename (full relative to plugins directory)
     * @return mixed string if error or true if success
     */
    public function deactivate_plugin( $plugin ) {
        $plugin = $this->plugin_basename( $plugin );

        // Check plugin is active
        if( !$this->is_active_plugin( $plugin ) )

            return _( 'Plugin not active' );

        // Deactivate the plugin
        global $ydb;
        $key = array_search( $plugin, $ydb->plugins );
        if( $key !== false ) {
            array_splice( $ydb->plugins, $key, 1 );
        }

        update_option( 'active_plugins', $ydb->plugins );
        do_action( 'deactivated_plugin', $plugin );
        do_action( 'deactivated_' . $plugin );

        return true;
    }

    /**
     * Return the path of a plugin file, relative to the plugins directory
     */
    public function plugin_basename( $file, $category = 'plugins' ) {
        $file = sanitize_filename( $file );
        if( $category == 'themes' )
            $plugindir = sanitize_filename( YOURLS_THEMEDIR );
        else
        $plugindir = sanitize_filename( YOURLS_PLUGINDIR );
        $file = str_replace( $plugindir, '', $file );

        return trim( $file, '/' );
    }

    /**
     * Return the URL of the directory a plugin
     */
    public function plugin_url( $file ) {
        $url = YOURLS_PLUGINURL . '/' . $this->plugin_basename( $file );
        if( is_ssl() or needs_ssl() )
            $url = str_replace( 'http://', 'https://', $url );

        return apply_filter( 'plugin_url', $url, $file );
    }

    /**
     * Display list of links to plugin admin pages, if any
     */
    public function list_plugin_admin_pages() {
        global $ydb;

        if( !property_exists( $ydb, 'plugin_pages' ) || !$ydb->plugin_pages )

            return;

        $plugin_links = array();
        foreach( (array)$ydb->plugin_pages as $plugin => $page ) {
            $plugin_links[ $plugin ] = array(
                'url'    => admin_url( 'plugins?page='.$page['slug'] ),
                'anchor' => $page['title'],
            );
        }

        return $plugin_links;
    }

    /**
     * Register a plugin administration page
     */
    public function register_plugin_page( $slug, $title, $function ) {
        global $ydb;

        if( !property_exists( $ydb, 'plugin_pages' ) || !$ydb->plugin_pages )
            $ydb->plugin_pages = array();

        $ydb->plugin_pages[ $slug ] = array(
            'slug'     => $slug,
            'title'    => $title,
            'function' => $function,
        );
    }

    /**
     * Handle plugin or theme administration page
     *
     */
    public function admin_page( $plugin_page, $type = 'plugin' ) {
        global $ydb;

        // Check the plugin page is actually registered
        if( !isset( $ydb->plugin_pages[$plugin_page] ) ) {
            die( _( 'This page does not exist. Maybe a plugin you thought was activated is inactive?' )/*, _( 'Invalid link' ) */);
        }

        // Draw the page itself
        do_action( 'load-' . $plugin_page);
        html_head( $type . '_page_' . $plugin_page, $ydb->plugin_pages[$plugin_page]['title'] );
        template_content( 'before', $type );

        call_user_func( $ydb->plugin_pages[$plugin_page]['function'] );

        template_content( 'after', $type . '_page_' . $plugin_page );
        die();
    }

    /**
     * Callback function: Sort plugins
     *
     * @link http://php.net/uasort
     *
     * @param array $plugin_a
     * @param array $plugin_b
     * @return int 0, 1 or -1, see uasort()
     */
    public function sort_callback( $plugin_a, $plugin_b ) {
        $orderby = apply_filters( 'plugins_sort_callback', 'Plugin Name' );
        $order   = apply_filters( 'plugins_sort_callback', 'ASC' );

        $a = $plugin_a[$orderby];
        $b = $plugin_b[$orderby];

        if ( $a == $b )
            return 0;

        if ( 'DESC' == $order )
            return ( $a < $b ) ? 1 : -1;
        else
            return ( $a < $b ) ? -1 : 1;
    }

}

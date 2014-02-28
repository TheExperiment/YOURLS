<?php

/**
 * Plugins Wrapper
 *
 * @since 1.5
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Extensions;

/**
 * Plugins API
 *
 * @author Ozh
 * @since 1.5
 */
class Plugins extends Extensions {

    /**
     * Summary of __construct
     */
    public function __construct() {
        $this->category = 'plugins';
    }

    /**
     * Return number of active plugins
     *
     * @return integer Number of activated plugins
     */
    public function has_active_plugins() {
        global $ydb;

        if( !property_exists( $ydb, 'plugins' ) || !$ydb->plugins )
            $ydb->plugins = array();

        return count( $ydb->plugins );
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
        $plugin = $this->extension_basename( $plugin );

        return in_array( $plugin, $ydb->plugins );

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
            if( $this->validate_extension_file( YOURLS_PLUGINDIR.'/'.$plugin ) ) {
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
     * Activate a plugin
     *
     * @param string $plugin Plugin filename (full or relative to plugins directory)
     * @return mixed string if error or true if success
     */
    public function activate_plugin( $plugin ) {
        // validate file
        $plugin = $this->extension_basename( $plugin );
        $plugindir = sanitize_filename( YOURLS_PLUGINDIR );
        if( !$this->validate_extension_file( $plugindir.'/'.$plugin ) )

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
        $plugin = $this->extension_basename( $plugin );

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
    public function extension_basename( $file, $category = 'plugins' ) {
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
        $url = YOURLS_PLUGINURL . '/' . $this->extension_basename( $file );
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

}

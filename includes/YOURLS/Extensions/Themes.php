<?php

/**
 * Themes Wrapper
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Extensions;

/**
 * The theme API, which allows designing and customizing the interface.
 *
 * Several functions used by themes are shared with plugins: see functions-plugins.php
 *
 * @since 2.0
 */
class Themes extends Extensions {

    private static $template;
    private static $assets;

    /**
     * Summary of __construct
     */
    public function __construct() {
        $this->category = 'themes';
    }

    /**
     * Define default page structure (ie callable functions to render a page)
     *
     * Page structure is the following: the 'before' part, the 'main' part and the 'after' part,
     * for instance:
     * - 'before' elements: sidebar, logo, title, ...
     * - 'main' elements: the main content of the page: plugin list, short URL list, plugin sub page...
     * - 'after' elements: footer, ...
     * The 'before' and 'after' elements can be modified with filter 'template_content'
     *
     * @since 1.7
     */
    public function set_template_content() {
        // Page structure
        $elements = array (
            'before' => array(
                'sidebar_start',
                'html_logo',
                'html_global_stats',
                'html_menu',
                'html_footer',
                'sidebar_end',
                'wrapper_start',   // themes should probably not remove this
            ),
            'after' => array(
                'wrapper_end',     // themes should probably not remove this
            )
        );

        self::$template = Filters::apply_filter( 'set_template_content', $elements );
    }

    /**
     * Remove an element (ie callable function) from the page template, or replace it with another one
     *
     * @since 1.7
     * @param string $function      Callable function to remove from the template
     * @param string $replace_with  Optional, callable function to replace with
     * @param string $where         Optional, only remove/replace $function in $where part ('before' or 'after')
     */
    public function remove_from_template( $function, $replace_with = null, $where = null ) {
        if( $where ) {
            $this->remove_from_array_deep( self::$template[ $where ], $function, $replace_with );
        } else {
            $this->remove_from_array_deep( self::$template, $function, $replace_with );
        }
    }

    /**
     * Helper function: remove an element, based on its value, from a multidimensional array
     *
     * @since 1.7
     * @param string $remove        element to remove from the array
     * @param string $replace_with  Optional, element to replace with
     * @return unknown
     */
    public function remove_from_array_deep( &$array, $remove, $replace_with = null ) {
        foreach( $array as $key => &$value ) {
            if( is_array( $value ) ) {
                $this->remove_from_array_deep( $value, $remove, $replace_with );
            } else {
                if( $remove == $value ) {
                    if( $replace_with ) {
                        $array[ $key ] = $replace_with;
                    } else {
                        unset( $array[ $key ] );
                    }
                }
            }
        }
    }

    /**
     * Draw page with HTML functions in requested order
     *
     * @since 1.7
     * @param string $template_part what template part (eg 'before' or 'after' the page main content)
     */
    public function template_content( $template_part ) {
        // Collect additional optional arguments, for instance the page context ('admin', 'plugins'...)
        $args = func_get_args();
        array_shift( $args ); // remove first element which is $template_part

        // Allow theming!
        $elements = Filters::apply_filter( 'template_content', self::$template, $template_part, $args );

        // 'Draw' page. Each template function is passed all arguments passed to template_content()
        foreach( (array) $elements[ $template_part ] as $element ) {
            if( is_callable( $element ) ) {
                call_user_func_array( $element, $args );
            } else {
                add_notice( s( 'Undefined template function <code>%s</code>', $element ), 'error' ); //@TODO notice style
            }
        }

        if( $template_part == 'after' )
            html_ending();
    }

    /**
     * Set list of core assets (arrays of handle => filename)
     *
     * Register the list of core assets and their handle. These assets are then
     * enqueueable as needed.
     *
     * @since 1.7
     * @return array   arrays of core assets
     */
    public function core_assets() {
        return array(
            'js'  => array(
                // 'handle' => 'file basename'
                'jquery'    => 'jquery.min',
                'clipboard' => 'ZeroClipboard.min',
                'scripts'   => 'bootstrap.min',
                'details'   => 'details.min',
                'yourls'    => 'yourls',
            ),
            'css' => array(
                'style'     => 'yourls',
            ),
        );
    }

    /**
     * Process and output asset queue (CSS or JS files)
     *
     * @since 1.7
     */
    public function output_asset_queue() {
        // Filter the asset list before echoing links
        $assets = Filters::apply_filter( 'html_assets_queue', self::$assets );

        $core = $this->core_assets();

        // Include assets
        foreach( $assets as $type => $files ) {
            foreach( $files as $name => $src ) {
                // If no src provided, assume it's a core asset
                if( !$src ) {
                    if( isset( $core[ $type ][ $name ] ) ) {
                        // @TODO: allow inclusion of non minified scripts or CSS for debugging
                        // Something like: $min = ( defined and true ( 'SCRIPT_YOURLS_DEBUG' ) ? '' : 'min' );
                        $src = site_url( false, YOURLS_ASSETURL . "/$type/" . $core[ $type ][ $name ] . ".$type?v=" . VERSION );
                    }
                }

                $src = sanitize_url( $src );

                // Output asset HTML tag
                switch( $type ) {
                    case 'css':
                        echo '<link rel="stylesheet" href="' . $src . '" type="text/css" media="screen">';
                        break;

                    case 'js':
                        echo '<script src="' . $src . '" type="text/javascript"></script>';
                        break;

                    default:
                        add_notice( _( 'You can only enqueue "css" or "js" files' ) );
                }
            }
        }
    }

    /**
     * Dequeue an asset (remove it from the queue of needed assets)
     *
     * @since 1.7
     * @param $string $name  name of the asset
     * @param $string $type  type of asset ('css' or 'js')
     * @return bool          true if asset dequeued, false if unfound
     */
    public function dequeue_asset( $name, $type ) {
        // Check file type
        if( !in_array( $type, array( 'css', 'js' ) ) ) {
            return false;
        }

        if( $this->is_asset_queued( $name, $type ) ) {
            unset( self::$assets[ $type ][ $name ] );

            return true;
        }

        return false;
    }

    /**
     * Enqueue an asset (add it to the list of needed assets)
     *
     * @since 1.7
     * @param string $name  name of the asset
     * @param string $src   source (full URL) of the asset. If ommitted, assumed it's a core asset
     * @param string $type  type of asset ('css' or 'js')
     * @param mixed  $deps  dependencies required first - a string or an array of strings
     * @return bool         false on error, true otherwise
     */
    public function enqueue_asset( $name, $type, $src = '', $deps = array() ) {
        // Check file type
        if( !in_array( $type, array( 'css', 'js' ) ) ) {
            add_notice( _( 'You can only enqueue "css" or "js" files' ) );

            return false;
        }

        // Already in queue?
        if( $this->is_asset_queued( $name, $type ) )

            return false;

        // Are there any (core) dependencies needed first?
        if( $deps ) {
            foreach( (array)$deps as $dep ) {
                $this->enqueue_asset( $dep, $type );
            }
        }

        self::$assets[ $type ][ $name ] = $src;

        return true;
    }

    /**
     * Enqueue a stylesheet
     *
     * Wrapper function for enqueue_asset()
     *
     * @since 1.7
     * @see enqueue_asset()
     * @param string $name  name of the asset
     * @param string $src   source (full URL) of the asset. If ommitted, assumed it's a core asset
     * @param mixed  $deps  dependencies required first - a string or an array of strings
     * @return bool         false on error, true otherwise
     */
    public function enqueue_style( $name, $src = '', $deps = array() ) {
        return $this->enqueue_asset( $name, 'css', $src, $deps  );
    }

    /**
     * Enqueue a script
     *
     * Wrapper function for enqueue_asset()
     *
     * @since 1.7
     * @see enqueue_asset()
     * @param string $name  name of the asset
     * @param string $src   source (full URL) of the asset. If ommitted, assumed it's a core asset
     * @param mixed  $deps  dependencies required first - a string or an array of strings
     * @return bool         false on error, true otherwise
     */
    public function enqueue_script( $name, $src = '', $deps = array() ) {
        return $this->enqueue_asset( $name, 'js', $src, $deps );
    }

    /**
     * Dequeue a stylesheet
     *
     * Wrapper function for dequeue_asset()
     *
     * @since 1.7
     * @see dequeue_asset()
     * @param string $name  name of the asset
     * @return bool         false on error, true otherwise
     */
    public function dequeue_style( $name ) {
        return $this->dequeue_asset( $name, 'css' );
    }

    /**
     * Dequeue a script
     *
     * Wrapper function for dequeue_asset()
     *
     * @since 1.7
     * @see dequeue_asset()
     * @param string $name  name of the asset
     * @return bool         false on error, true otherwise
     */
    public function dequeue_script( $name ) {
        return $this->dequeue_asset( $name, 'js' );
    }

    /**
     * Check if an asset is queued
     *
     * @since 1.7
     * @param string $name  name of the asset
     * @param string $type  type of the asset ('css' or 'js')
     * @return bool         true if the asset is in the queue, false otherwise
     */
    public function is_asset_queued( $name, $type ) {
        return isset( self::$assets[ $type ][ $name ] );
    }

    /**
     * Check if a script is queued
     *
     * Wrapper function for is_asset_queued()
     *
     * @since 1.7
     * @param string $name  name of the script
     * @return bool         true if the script is in the queue, false otherwise
     */
    public function is_script_queued( $name ) {
        return $this->is_asset_queued( $name, 'js' );
    }

    /**
     * Check if a stylesheet is queued
     *
     * Wrapper function for is_asset_queued()
     *
     * @since 1.7
     * @param string $name  name of the stylesheet
     * @return bool         true if the stylesheet is in the queue, false otherwise
     */
    public function is_style_queued( $name ) {
        return $this->is_asset_queued( $name, 'css' );
    }

    /**
     * Init theme API and load active theme if any
     *
     * @since 1.7
     */
    public function init() {
        Filters::do_action( 'pre_init_theme' );

        // Enqueue default asset files - $ydb->assets will keep a list of needed CSS and JS
        // Asset src are defined in core_assets()
        $this->enqueue_style( 'style' );
        $this->enqueue_script( 'jquery' );
        $this->enqueue_script( 'clipboard' );
        $this->enqueue_script( 'yourls' );
        $this->enqueue_script( 'scripts' );
        $this->enqueue_script( 'details' );

        // Set default template structure
        $this->set_template_content();

        // Don't load theme when installing or updating.
        if( Configuration::is( 'installing' ) OR Configuration::is( 'upgrading' ) )

            return;

        // Load theme if applicable
        $this->load_active();
    }

    /**
     * Check if there is an active theme and attempt to load it
     *
     * @since 1.7
     * @return mixed  true if active theme loaded, false if no active theme, error message if problem
     */
    public function load_active() {

        Filters::do_action( 'pre_load_active_theme' );

        // is there an active theme ?
        $active_theme = $this->get_active();
        if( Configuration::is( 'debug' ) ) {
            global $ydb;
            $ydb->debug_log[] = 'Theme: ' . $active_theme;
        }
        if( !$active_theme ) {
            Filters::do_action( 'load_active_theme_empty' );

            return false;
        }

        // Try to load the active theme
        if( $this->load( $active_theme ) ) {
            Filters::do_action( 'load_active_theme' );

            return true;
        }

        // There was a problem : deactivate theme and report error
        $this->activate( 'default' );
        add_notice( $load );
        /*add_notice( s( 'Deactivated theme: %s' ), $active_theme );*/

        return $load;
    }

    /**
     * Attempt to load a theme
     *
     * @since 1.7
     * @param string $theme   theme directory inside YOURLS_THEMEDIR
     * @return mixed          true, or an error message
     */
    public function load( $theme ) {
        $theme_php     = $this->get_dir( $theme ) . '/theme.php';
        $theme_css     = $this->get_dir( $theme ) . '/theme.css';
        $theme_css_url = $this->get_url( $theme ) . '/theme.css';

        if( !is_readable( $theme_css ) )

            return s( 'Cannot find <code>theme.css</code> in <code>%s</code>', $theme );

        // attempt activation of the theme's function file if there is one
        if( is_readable( $theme_php ) ) {
            ob_start();
            include_once( $theme_php );
            if ( ob_get_length() > 0 ) {
                // there was some output: error
                $output = ob_get_clean();

                return s( 'Theme generated unexpected output. Error was: <br/><pre>%s</pre>', $output );
            }
            ob_end_clean();
        }

        // Enqueue theme.css
        $this->enqueue_style( $theme, $theme_css_url );

        // Success !
        Filters::do_action( 'theme_loaded' );

        return true;
    }

    /**
     * Activate a theme
     *
     * @since 1.7
     * @param string $theme   theme directory inside YOURLS_THEMEDIR
     * @return mixed          true, or an error message
     */
    public function activate( $theme ) {
        if ( $theme == 'default' ) {
            Options::set( 'active_theme', '' );
            Filters::do_action( 'activated_theme', $theme );
            Filters::do_action( 'activated_' . $theme );

            return true;
        }

        $theme_php = $this->get_dir( $theme ) . '/theme.php';
        $theme_css = $this->get_dir( $theme ) . '/theme.css';

        // Check if the theme has a theme.css
        if( !is_readable( $theme_css ) )

            return s( 'Cannot find <code>theme.css</code> in <code>%s</code>', $theme );

        // Validate theme.php file if exists
        if( is_readable( $theme_php ) && !$this->validate_plugin_file( $theme_php ) )

            return s( 'Not a valid <code>theme.php</code> file in <code>%s</code>', $theme );

        // Check that it's not activated already
        if( $theme == $this->get_active() )

            return _( 'Theme already activated' );

        // Attempt to load the theme
        $load = $this->load( $theme );

        if( $load === true ) {
            // so far, so good
            Options::set( 'active_theme', $theme );
            Filters::do_action( 'activated_theme', $theme );
            Filters::do_action( 'activated_' . $theme );

            return true;
        } else {
            // oops.
            add_notice( $load );

            return $load;
        }
    }

    /**
     * Get active theme
     *
     * @since 1.7
     * @return string name of theme directory, or empty string if no theme
     */
    public function get_active() {
        global $ydb;
        if( !property_exists( $ydb, 'theme' ) || $ydb->theme == '' ) {
            $ydb->theme = ( Options::get( 'active_theme' ) ) ? Options::get( 'active_theme' ) : '';
            // Update option to save one query on next page load
            Options::set( 'active_theme', $ydb->theme );
        }

        return Filters::apply_filter( 'get_active_theme', $ydb->theme );
    }

    /**
     * Return the base directory of a given theme
     *
     * @since 1.7
     * @param string $theme  theme (its directory)
     * @return string        sanitized physical path
     */
    public function get_dir( $theme ) {
        return sanitize_filename( YOURLS_THEMEDIR . "/$theme" );
    }

    /**
     * Return the base URL of a given theme
     *
     * @since 1.7
     * @param string $theme  theme (its directory)
     * @return string        sanitized URL
     */
    public function get_url( $theme ) {
        return sanitize_url( YOURLS_THEMEURL . "/$theme" );
    }

    /**
     * Return the base directory of the active theme, if any
     *
     * @since 1.7
     * @return string        sanitized physical path, or an empty string
     */
    public function get_active_dir() {
        return ( $this->get_active() ? $this->get_dir( $this->get_active() ) : '' );
    }

    /**
     * Return the base URL of the active theme, if any
     *
     * @since 1.7
     * @return string        sanitized URL,  or an empty string
     */
    public function get_active_url() {
        return ( $this->get_active() ? $this->get_url( $this->get_active() ) : '' );
    }

    /**
     * Get theme screenshot
     *
     * Search in a given directory for a file named screenshot.(png|jpg|gif)
     *
     * @since 1.7
     * @param string $theme_dir Theme directory to search
     * @return string screenshot filename, empty string if not found
     */
    public function get_screenshot( $theme_dir ) {
        $screenshot = '';

        // search for screenshot.(gif|jpg|png)
        foreach( array( 'png', 'jpg', 'gif' ) as $ext ) {
            if( file_exists( $this->get_dir( $theme_dir ) . '/screenshot.' . $ext ) ) {
                $screenshot = $this->get_url( $theme_dir ) . '/screenshot.' . $ext;
                break;
            }
        }

        return $screenshot;
    }

}

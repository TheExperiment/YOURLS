<?php

/**
 * Extensions Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Extensions;

/**
 * Extensions API
 *
 * @author Ozh
 * @since 1.5
 */
class Extensions {

    /**
     * Category of extensions
     *
     * @var string
     */
    protected $category;

    /**
     * List extensions in /user/extensions
     *
     * @global object $ydb Storage of mostly everything YOURLS needs to know
     * @return array Array of [/extensiondir/extension.php]=>array('Name'=>'Ozh', 'Title'=>'Hello', )
     */
    public function get_extensions() {
        if( $this->$category == 'themes' )
            $extensions = (array) glob( YOURLS_THEMEDIR .'/*/theme.css');
        else
        $extensions = (array) glob( YOURLS_PLUGINDIR .'/*/extension.php');

        if( !$extensions )

            return array();

        foreach( $extensions as $key => $extension ) {
            $_extension = $this->extension_basename( $extension, $this->$category );
            $extensions[ $_extension ] = $this->get_extension_data( $extension );
            unset( $extensions[ $key ] );
        }

        return $extensions;
    }

    /**
     * Parse a extension header
     *
     * @param string $file Physical path to extension file
     * @return array Array of 'Field'=>'Value' from extension comment header lines of the form "Field: Value"
     */
    public function get_extension_data( $file ) {
        $fp = fopen( $file, 'r' ); // assuming $file is readable, since load_extensions() filters this
        $data = fread( $fp, 8192 ); // get first 8kb
        fclose( $fp );

        // Capture all the header within first comment block
        if( !preg_match( '!.*?/\*(.*?)\*/!ms', $data, $matches ) )

            return array();

        // Capture each line with "Something: some text"
        unset( $data );
        $lines = preg_split( "[\n|\r]", $matches[1] );
        unset( $matches );

        $extension_data = array();
        foreach( $lines as $line ) {
            if( !preg_match( '!(.*?):\s+(.*)!', $line, $matches ) )
                continue;

            list( $null, $field, $value ) = array_map( 'trim', $matches);
            $extension_data[ $field ] = $value;
        }

        return $extension_data;
    }

    /**
     * Check if a file is safe for inclusion (well, "safe", no guarantee)
     *
     * @param string $file Full pathname to a file
     * @return bool
     */
    public function validate_extension_file( $file ) {
        if (
            false !== strpos( $file, '..' )
            OR
            false !== strpos( $file, './' )
            OR // a extension must be named 'plugin.php', a theme must be named 'theme.php'
            ( 'plugin.php' !== substr( $file, -10 )	&& 'theme.php' !== substr( $file, -9 ) )
            OR
            !is_readable( $file )
        )

            return false;

        return true;
    }

    /**
     * Return the path of a extension file, relative to the extensions directory
     */
    public function extension_basename( $file ) {
        $file = sanitize_filename( $file );
        if( $this->$category == 'themes' )
            $extensiondir = sanitize_filename( YOURLS_THEMEDIR );
        else
        $extensiondir = sanitize_filename( YOURLS_PLUGINDIR );
        $file = str_replace( $extensiondir, '', $file );

        return trim( $file, '/' );
    }

    /**
     * Handle extension or theme administration page
     *
     */
    public function admin_page( $extension_page ) {
        global $ydb;

        // Check the extension page is actually registered
        if( !isset( $ydb->extension_pages[$extension_page] ) ) {
            die( _( 'This page does not exist. Maybe a extension you thought was activated is inactive?' )/*, _( 'Invalid link' ) */);
        }

        // Draw the page itself
        do_action( 'load-' . $extension_page);
        html_head( $this->category . '_page_' . $extension_page, $ydb->extension_pages[$extension_page]['title'] );
        template_content( 'before', $this->category );

        call_user_func( $ydb->extension_pages[$extension_page]['function'] );

        template_content( 'after', $this->category . '_page_' . $extension_page );
        die();
    }

    /**
     * Callback function: Sort extensions
     *
     * @link http://php.net/uasort
     *
     * @param array $extension_a
     * @param array $extension_b
     * @return int 0, 1 or -1, see uasort()
     */
    public function sort_callback( $extension_a, $extension_b ) {
        $orderby = apply_filters( 'extensions_sort_callback', 'Plugin Name' );
        $order   = apply_filters( 'extensions_sort_callback', 'ASC' );

        $a = $extension_a[$orderby];
        $b = $extension_b[$orderby];

        if ( $a == $b )
            return 0;

        if ( 'DESC' == $order )
            return ( $a < $b ) ? 1 : -1;
        else
            return ( $a < $b ) ? -1 : 1;
    }

}

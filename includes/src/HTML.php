<?php

/**
 * HTML Wrapper
 *
 * @since 2.0
 * @copyright 2009-2014 YOURLS - MIT
 */

namespace YOURLS;

/**
 * Here we prepare HTML output
 */
class HTML {

    /**
     * Display HTML head and <body> tag
     *
     * @param string $context Context of the page (stats, index, infos, ...)
     * @param string $title HTML title of the page
     */
    function html_head( $context = 'index', $title = '' ) {

        do_action( 'pre_html_head', $context, $title );

        // Force no cache for all admin pages
        if( is_admin() && !headers_sent() ) {
            header( 'Expires: Thu, 23 Mar 1972 07:00:00 GMT' );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
            header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
            header( 'Pragma: no-cache' );
            content_type_header( apply_filters( 'html_head_content-type', 'text/html' ) );
            do_action( 'admin_headers', $context, $title );
        }

        // Store page context in global object
        global $ydb;
        $ydb->context = $context;

        // Body class
        $bodyclass = apply_filter( 'bodyclass', '' );

        // Page title
        $_title = 'YOURLS &middot; Your Own URL Shortener';
        $title = $title ? $title . " &mdash; " . $_title : $_title;
        $title = apply_filter( 'html_title', $title, $context );

        ?>
<!DOCTYPE html>
<html <?php html_language_attributes(); ?>>
<head>
    <meta charset="utf-8">
    <title><?php echo $title ?></title>
    <meta name="description" content="YOURLS is Your Own URL Shortener. Get it at http://yourls.org/">
    <meta name="author" content="The YOURLS project - http://yourls.org/">
    <meta name="generator" content="YOURLS <?php echo VERSION ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="canonical" href="<?php site_url(); ?>/">
    <?php
        favicon();
        output_asset_queue();
        if ( $context == 'infos' ) { 	// Load charts component as needed ?>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
        google.load('visualization', '1.0', { 'packages': ['corechart', 'geochart'] });
    </script>
    <?php } ?>
    <script type="text/javascript">
        //<![CDATA[
        var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
        var moviepath = '<?php site_url( true, ASSETURL . '/js/ZeroClipboard.swf' ); ?>';

        //]]>
    </script>
    <?php do_action( 'html_head', $context ); ?>
</head>
<body class="<?php echo $context . ( $bodyclass ? ' ' . $bodyclass : '' ); ?>">
    <div class="container">
        <div class="row">
            <?php
    }

    /**
     * Display YOURLS logo
     *
     * @param bool $linked true if a link is wanted
     */
    function html_logo( $linked = true ) {
        do_action( 'pre_html_logo' );
        $logo = '<img class="yourls-logo-img" src="' . site_url( false, ASSETURL . '/img/yourls-logo.png' ) . '" alt="YOURLS" title="YOURLS"/>';
        if ( $linked )
            $logo = html_link( admin_url( 'index' ), $logo, 'YOURLS', false, false );
        ?>
            <div class="yourls-logo">
                <?php echo $logo; ?>
            </div>
            <?php
        do_action( 'html_logo' );
    }

    /**
     * Display HTML heading (h1 .. h6) tag
     *
     * @since 2.0
     * @param string $title     Title to display
     * @param int    $size      Optional size, 1 to 6, defaults to 6
     * @param string $subtitle  Optional subtitle to be echoed after the title
     * @param string $class     Optional html class
     * @param bool   $echo
     */
    function html_htag( $title, $size = 1, $subtitle = null, $class = null, $echo = true ) {
        $size = intval( $size );
        if( $size < 1 )
            $size = 1;
        elseif( $size > 6 )
            $size = 6;

        if( $class ) {
            $class = 'class="' . esc_attr( $class ) . '"';
        }

        $result = "<h$size$class>$title";
        if ( $subtitle ) {
            $result .= " <small>&mdash; $subtitle</small>";
        }
        $result .= "</h$size>";
        if ( $echo )
            echo $result;
        else
            return $result;
    }

    /**
     * Display the admin menu
     *
     * @param string $current_page Which page is loaded?
     */
    function html_menu( $current_page = null ) {
        // Build menu links
        $help_link   = apply_filter( 'help-link', '<a href="' . site_url( false ) .'/docs/"><i class="fa fa-question-circle fa-fw"></i> ' . _( 'Help' ) . '</a>' );

        $admin_links    = array();
        $admin_sublinks = array();

        $admin_links['admin'] = array(
            'url'    => admin_url( 'index' ),
            'title'  => _( 'Go to the admin interface' ),
            'anchor' => _( 'Interface' ),
            'icon'   => 'home'
        );

        if( ( is_admin() && is_public_or_logged() ) || defined( 'USER' ) ) {
            $admin_links['tools'] = array(
                'url'    => admin_url( 'tools' ),
                'anchor' => _( 'Tools' ),
                'icon'   => 'wrench'
            );
            $admin_links['plugins'] = array(
                'url'    => admin_url( 'plugins' ),
                'anchor' => _( 'Plugins' ),
                'icon'   => 'cogs'
            );
            $admin_links['themes'] = array(
                'url'    => admin_url( 'themes' ),
                'anchor' => _( 'Themes' ),
                'icon'   => 'picture-o'
            );
            $admin_sublinks['plugins'] = list_plugin_admin_pages();
        }

        $admin_links    = apply_filter( 'admin-links',    $admin_links );
        $admin_sublinks = apply_filter( 'admin-sublinks', $admin_sublinks );

        // Build menu HTML
        $menu = apply_filter( 'admin_menu_start', '<nav class="sidebar-responsive-collapse"><ul class="admin-menu">' );
        if( defined( 'USER' ) && is_private() ) {
            $menu .= apply_filter( 'logout_link', '<div class="nav-header">' . sprintf( _( 'Hello <strong>%s</strong>' ), USER ) . '<a href="?action=logout" title="' . esc_attr__( 'Logout' ) . '" class="pull-right"><i class="fa fa-sign-out fa-fw"></i></a></div>' );
        } else {
            $menu .= apply_filter( 'logout_link', '' );
        }

        foreach( (array)$admin_links as $link => $ar ) {
            if( isset( $ar['url'] ) ) {
                $anchor = isset( $ar['anchor'] ) ? $ar['anchor'] : $link;
                $title  = isset( $ar['title'] ) ? 'title="' . $ar['title'] . '"' : '';
                $class_active  = $current_page == $link ? ' active' : '';

                $format = '<li id="admin-menu-%link%-link" class="admin-menu-toplevel%class%">
                    <a href="%url%" %title%><i class="fa fa-%icon% fa-fw"></i> %anchor%</a></li>';
                $data   = array(
                    'link'   => $link,
                    'class'  => $class_active,
                    'url'    => $ar['url'],
                    'title'  => $title,
                    'icon'   => $ar['icon'],
                    'anchor' => $anchor,
                );

                $menu .= apply_filter( 'admin-menu-link-' . $link, replace_string_tokens( $format, $data ), $format, $data );
            }

            // Submenu if any. TODO: clean up, too many code duplicated here
            if( isset( $admin_sublinks[$link] ) ) {
                $menu .= '<ul class="admin-menu submenu" id="admin-submenu-' . $link . '">';
                foreach( $admin_sublinks[$link] as $link => $ar ) {
                    if( isset( $ar['url'] ) ) {
                        $anchor = isset( $ar['anchor'] ) ? $ar['anchor'] : $link;
                        $title  = isset( $ar['title'] ) ? 'title="' . $ar['title'] . '"' : '';
                        $class_active  = ( isset( $_GET['page'] ) && $_GET['page'] == $link ) ? ' active' : '';

                        $format = '<li id="admin-menu-%link%-link" class="admin-menu-sublevel admin-menu-sublevel-%link%%class%">
                            <a href="%url%" %itle%>%anchor%</a></li>';
                        $data   = array(
                            'link'   => $link,
                            'class'  => $class_active,
                            'url'    => $ar['url'],
                            'title'  => $title,
                            'anchor' => $anchor,
                        );

                        $menu .= apply_filter( 'admin_menu_sublink_' . $link, replace_string_tokens( $format, $data ), $format, $data );
                    }
                }
                $menu .=  '</ul>';
            }
        }

        if ( isset( $help_link ) )
            $menu .=  '<li id="admin-menu-help-link">' . $help_link .'</li>';

        $menu .=  apply_filter( 'admin_menu_end', '</ul></nav>' );

        do_action( 'pre_admin_menu' );
        echo apply_filter( 'html_admin_menu', $menu );
        do_action( 'post_admin_menu' );
    }

    /**
     * Display global stats in a div
     *
     * @since 2.0
     */
    function html_global_stats() {
        list( $total_urls, $total_clicks ) = array_values( get_db_stats() );
        // @FIXME: this SQL query is also used in admin/index.php - reduce query count
        $html  = '<div class="global-stats"><div class="global-stats-data">';
        $html .= '<strong class="status-number increment">' . number_format_i18n( $total_urls ) . '</strong><p>' . _( 'Links' );
        $html .= '</p></div><div class="global-stats-data">';
        $html .= '<strong class="status-number">' . number_format_i18n( $total_clicks ) . '</strong><p>' . _( 'Clicks' ) . '</p></div></div>';
        echo apply_filters( 'html_global_stats', $html );
    }

    /**
     * Wrapper function to display admin notice
     *
     * @param string $message The message showed
     * @param string $style notice / error / info / warning / success
     */
    function add_notice( $message, $style = 'notice' ) {
        // Escape single quotes in $message to avoid breaking the anonymous function
        $message = notice_box( strtr( $message, array( "'" => "\'" ) ), $style );
        add_action( 'admin_notice', create_function( '', "echo '$message';" ) );
    }

    /**
     * Return a formatted notice
     *
     * @param string $message The message showed
     * @param string $style notice / error / info / warning / success
     */
    function notice_box( $message, $style = 'notice' ) {
        return '<div class="alert alert-' . $style . '"><a class="close" data-dismiss="alert" href="#">&times;</a>' . $message . '</div>';
    }

    /**
     * Wrapper function to display label
     *
     * @since 2.0
     * @param string $message The message showed
     * @param string $style notice / error / info / warning / success
     */
    function add_label( $message, $style = 'normal', $space = null ) {
        $label = '<span class="label label-' . $style . '">' . $message . '</span>';
        if ( $space )
            $label = $space == 'before' ? ' ' . $label : $label . ' ';
        echo $label;
    }

    /**
     * Display a page
     *
     */
    function page( $page ) {
        $include = PAGEDIR . "/$page.php";
        if( !file_exists( $include ) ) {
            die( "Page '$page' not found", 'Not found', 404 );
        }
        do_action( 'pre_page', $page );
        include_once( $include );
        do_action( 'post_page', $page );
        die();
    }

    /**
     * Display the language attributes for the HTML tag.
     *
     * Builds up a set of html attributes containing the text direction and language
     * information for the page. Stolen from WP.
     *
     * @since 1.6
     */
    function html_language_attributes() {
        $attributes = array();
        $output = '';

        $attributes[] = ( is_rtl() ? 'dir="rtl"' : 'dir="ltr"' );

        $doctype = apply_filters( 'html_language_attributes_doctype', 'html' );
        // Experimental: get HTML lang from locale. Should work. Convert fr_FR -> fr-FR
        if ( $lang = str_replace( '_', '-', get_locale() ) ) {
            if( $doctype == 'xhtml' ) {
                $attributes[] = "xml:lang=\"$lang\"";
            } else {
                $attributes[] = "lang=\"$lang\"";
            }
        }

        $output = implode( ' ', $attributes );
        $output = apply_filters( 'html_language_attributes', $output );
        echo $output;
    }

    /**
     * Display HTML footer (including closing body & html tags)
     *
     */
    function html_footer() {
        echo '<hr /><div class="footer" role="contentinfo"><p>';
        $footer  = s( 'Powered by %s', html_link( 'http://yourls.org/', 'YOURLS', 'YOURLS', false, false ) );
            echo apply_filters( 'html_footer_text', $footer );
        echo '</p></div>';
    }

    /**
     * Display HTML debug infos
     *
     */
    function html_debug() {
        global $ydb;
        echo '<pre class="debug-info"><button type="button" class="close" onclick="$(this).parent().fadeOut();return false;" title="Dismiss">&times;</button>';
        echo  'Queries: ' . $ydb->num_queries . "\n";
            echo join( "\n", $ydb->debug_log );
        echo '</pre>';
        do_action( 'html_debug', $ydb->context );
    }

    /**
     * Display "Add new URL" box
     *
     * @param string $url URL to prefill the input with
     * @param string $keyword Keyword to prefill the input with
     */
    function html_addnew( $url = '', $keyword = '' ) {
        ?>
            <div class="new-url-form">
                <div class="new-url-long">
                    <label><?php e( 'Enter the URL' ); ?></label>
                    <input type="text" class="add-url" name="url" placeholder="http://&hellip;" size="80">
                </div>
                <div class="new-url-short">
                    <label><?php e( 'Short URL' ); ?> <span class="label label-info"><?php e( 'Optional' ); ?></span></label>
                    <input type="text" placeholder="<?php e( 'keyword' ); ?>" name="keyword" value="<?php echo $keyword; ?>" class="add-keyword" size="8">
                    <?php nonce_field( 'add_url', 'nonce-add' ); ?>
                </div>
                <div class="new-url-action">
                    <button name="add-button" class="add-button"><?php e( 'Shorten The URL' ); ?></button>
                </div>
                <div class="feedback"></div>
                <?php do_action( 'html_addnew' ); ?>
            </div>
            <?php
    }

    /**
     * Display main search form
     *
     * The $param array is defined in /admin/index.php
     *
     * @param array $params Array of all required parameters
     * @return string Result
     */
    function html_search( $params = array() ) {
        extract( $params ); // extract $search_text, $search_in ...
        ?>
            <form class="search-form" action="" method="get" role="search">
                <?php
                            // @TODO: Clean up HTML - CSS
                            // First search control: text to search
                            $_input = '<input type="text" name="search" class="form-control search-primary" value="' . esc_attr( $search_text ) . '" />';
                            $_options = array(
                                'keyword' => _( 'Short URL' ),
                                'url'     => _( 'URL' ),
                                'title'   => _( 'Title' ),
                                'ip'      => _( 'IP' ),
                            );
                            $_select_search = html_select( 'search_in', $_options, $search_in );
                            $_button = '<span class="input-group-btn">
                            <button type="submit" id="submit-sort" class="btn btn-primary">' . _( 'Search' ) . '</button>
                            <button type="button" id="submit-clear-filter" class="btn btn-danger" onclick="window.parent.location.href = \'index\'">' . _( 'Clear' ) . '</button>
                            </span>';

                            // Second search control: order by
                            $_options = array(
                                'keyword'      => _( 'Short URL' ),
                                'url'          => _( 'URL' ),
                                'timestamp'    => _( 'Date' ),
                                'ip'           => _( 'IP' ),
                                'clicks'       => _( 'Clicks' ),
                            );
                            $_select_order = html_select( 'sort_by', $_options, $sort_by );
                            $sort_order = isset( $sort_order ) ? $sort_order : 'desc' ;
                            $_options = array(
                                'asc'  => _( 'Ascending' ),
                                'desc' => _( 'Descending' ),
                            );
                            $_select2_order = html_select( 'sort_order', $_options, $sort_order );

                            // Fourth search control: Show links with more than XX clicks
                            $_options = array(
                                'more' => _( 'more' ),
                                'less' => _( 'less' ),
                            );
                            $_select_clicks = html_select( 'click_filter', $_options, $click_filter );
                            $_input_clicks  = '<input type="text" name="click_limit" class="form-control" value="' . $click_limit . '" /> ';

                            // Fifth search control: Show links created before/after/between ...
                            $_options = array(
                                'before'  => _( 'before' ),
                                'after'   => _( 'after' ),
                                'between' => _( 'between' ),
                            );
                            $_select_creation = html_select( 'date_filter', $_options, $date_filter );
                            $_input_creation  = '<input type="text" name="date-first" class="form-control date-first" value="' . $date_first . '" />';
                            $_input2_creation = '<input type="text" name="date-second" class="form-control date-second" value="' . $date_second . '"' . ( $date_filter === 'between' ? ' style="display:inline"' : '' ) . '/>';

                            $advanced_search = array(
                                _( 'Search' )   => array( $_input, $_button ),
                                _( 'In' )       => array( $_select_search ),
                                _( 'Order by' ) => array( $_select_order, $_select2_order ),
                                _( 'Clicks' )   => array( $_select_clicks, $_input_clicks ),
                                _( 'Created' )  => array( $_select_creation, $_input_creation, $_input2_creation )
                            );
                            foreach( $advanced_search as $title => $options ) {
                                ?>
                <div class="control-group">
                    <label class="control-label"><?php echo $title; ?></label>
                    <div class="controls input-group">
                        <?php
                                        foreach( $options as $option )
                                            echo $option
                                        ?>
                    </div>
                </div>
                <?php
                            }
                            ?>

            </form>
            <?php
                // Remove empty keys from the $params array so it doesn't clutter the pagination links
                $params = array_filter( $params, 'return_if_not_empty_string' ); // remove empty keys

                if( isset( $search_text ) ) {
                    $params['search'] = $search_text;
                    unset( $params['search_text'] );
                }
                do_action( 'html_search' );
    }

    /**
     * Wrapper function to display the global pagination on interface
     *
     * @param array $params
     */
    function html_pagination( $params = array() ) {
        extract( $params ); // extract $page, ...
        if( $total_pages > 1 ) {
                ?>
            <div>
                <ul class="pagination">
                    <?php
                        // Pagination offsets: min( max ( zomg! ) );
                    $p_start = max( min( $total_pages - 4, $page - 2 ), 1 );
                        $p_end = min( max( 5, $page + 2 ), $total_pages );
                        if( $p_start >= 2 ) {
                        $link = add_query_arg( array( 'page' => 1 ) );
                        echo '<li><a href="' . $link . '" title="' . esc_attr__( 'Go to First Page' ) . '">&laquo;</a></li>';
                        echo '<li><a href="'.add_query_arg( array( 'page' => $page - 1 ) ).'">&lsaquo;</a></li>';
                        }
                        for( $i = $p_start ; $i <= $p_end; $i++ ) {
                            if( $i == $page ) {
                            echo '<li class="active"><a href="#">' . $i . '</a></li>';
                            } else {
                            $link = add_query_arg( array( 'page' => $i ) );
                            echo '<li><a href="' . $link . '" title="' . sprintf( esc_attr( 'Page %s' ), $i ) .'">'.$i.'</a></li>';
                            }
                        }
                        if( ( $p_end ) < $total_pages ) {
                        $link = add_query_arg( array( 'page' => $total_pages ) );
                        echo '<li><a href="' . add_query_arg( array( 'page' => $page + 1 ) ) . '">&rsaquo;</a></li>';
                        echo '<li><a href="' . $link . '" title="' . esc_attr__( 'Go to First Page' ) . '">&raquo;</a></li>';
                        }
                        ?>
                </ul>
            </div>
            <?php }
            do_action( 'html_pagination' );
    }

    /**
     * Wrapper function to display how many items are shown
     *
     * @since 2.0
     *
     * @param string $item_type Type of the item (e.g. "links")
     * @param int $min_on_page
     * @param int $max_on_page
     * @param int $total_items Total of items in data
     */
    function html_displaying_count( $item_type, $min_on_page, $max_on_page, $total_items ) {
        if( $max_on_page - $min_on_page + 1 >= $total_items )
            printf( _( 'Displaying <strong class="increment">all %1$s</strong> %2$s' ), $max_on_page, $item_type );
        else
            printf( _( 'Displaying %1$s <strong>%2$s</strong> to <strong class="increment">%3$s</strong> of <strong class="increment">%4$s</strong> in total' ), $item_type, $min_on_page, $max_on_page, $total_items );
    }

    /**
     * Return a select box
     *
     * @since 1.6
     *
     * @param string $name HTML 'name' (also use as the HTML 'id')
     * @param array $options array of 'value' => 'Text displayed'
     * @param string $selected optional 'value' from the $options array that will be highlighted
     * @param boolean $display false (default) to return, true to echo
     * @return string HTML content of the select element
     */
    function html_select( $name, $options, $selected = '', $display = false ) {
        $html = '<select name="' . $name . '" class="input-group-addon">';
        foreach( $options as $value => $text ) {
            $html .= '<option value="' . $value .'"';
            $html .= $selected == $value ? ' selected="selected"' : '';
            $html .= ">$text</option>";
        }
        $html .= "</select>";
        $html  = apply_filters( 'html_select', $html, $name, $options, $selected, $display );
        if( $display )
            echo $html;

        return $html;
    }

    /**
     * Display the Quick Share box
     *
     */
    function share_box( $longurl, $shorturl, $title = '', $text='', $shortlink_title = '', $share_title = '', $hidden = false ) {
        // @TODO: HTML Clean up
        if ( $shortlink_title == '' )
            $shortlink_title = '<h2>' . _( 'Your short link' ) . '</h2>';
        if ( $share_title == '' )
            $share_title = '<h2>' . _( 'Quick Share' ) . '</h2>';

        // Allow plugins to short-circuit the whole function
        $pre = apply_filter( 'shunt_share_box', false );
        if ( false !== $pre )
            return $pre;

        $text   = ( $text ? '"' . $text . '" ' : '' );
        $title  = ( $title ? "$title " : '' );
        $share  = esc_textarea( $title.$text.$shorturl );
        $count  = 140 - strlen( $share );
        $hidden = ( $hidden ? 'style="display:none;"' : '' );

        // Allow plugins to filter all data
        $data = compact( 'longurl', 'shorturl', 'title', 'text', 'shortlink_title', 'share_title', 'share', 'count', 'hidden' );
        $data = apply_filter( 'share_box_data', $data );
        extract( $data );

        $_share = rawurlencode( $share );
        $_url   = rawurlencode( $shorturl );
        ?>

            <div id="shareboxes" <?php echo $hidden; ?>>

                <?php do_action( 'shareboxes_before', $longurl, $shorturl, $title, $text ); ?>

                <div id="copybox" class="share">
                    <?php echo $shortlink_title; ?>
                    <div class="input-group col col-lg-4">
                        <input id="copylink" type="text" value="<?php echo esc_url( $shorturl ); ?>"/><span class="input-group-btn">
                            <?php html_zeroclipboard( 'copylink' ); ?>
                        </span>
                    </div>
                    <p>
                        <small><?php e( 'Long link' ); ?>: <a id="origlink" href="<?php echo esc_url( $longurl ); ?>"><?php echo esc_url( $longurl ); ?></a></small>
                        <?php if( do_log_redirect() ) { ?>
                        <br />
                        <small><?php e( 'Stats' ); ?>: <a id="statlink" href="<?php echo esc_url( $shorturl ); ?>+"><?php echo esc_url( $shorturl ); ?>+</a></small>
                        <input type="hidden" id="titlelink" value="<?php echo esc_attr( $title ); ?>" />
                        <?php } ?>
                    </p>
                </div>

                <?php do_action( 'shareboxes_middle', $longurl, $shorturl, $title, $text ); ?>

                <?php
                    do_action( 'share_links', $longurl, $shorturl, $title, $text );
                    // Note: on the main admin page, there are no parameters passed to the sharebox when it's drawn.
                    ?>

                <?php do_action( 'shareboxes_after', $longurl, $shorturl, $title, $text ); ?>

            </div>

            <?php
    }

    /**
     * Display or return the ZeroClipboard button, with Tooltip additions
     *
     * @since 1.7
     * @param string $clipboard_target Id of the fetched element to copy value
     * @param bool $echo true to print, false to return
     */
    function html_zeroclipboard( $clipboard_target, $echo = true ) {
        $html = apply_filter( 'html_zeroclipboard',
        '<button class="btn-clipboard" data-copied-hint="' . _( 'Copied!' ) . '" data-clipboard-target="' . $clipboard_target . '" data-placement="bottom" data-trigger="manual" data-original-title="' . _( 'Copy to clipboard' ) . '"><i class="fa fa-copy"></i></button>',
        $clipboard_target );
        if( $echo )
            echo $html;

        return $html;
    }

    /**
     * Die die die
     *
     */
    function die( $message = '', $title = '', $header_code = 200 ) {
        status_header( $header_code );

        if( !$head = did_action( 'html_head' ) ) {
            html_head( 'die', _( 'Fatal error' ) );
            template_content( 'before', 'die' );
        }

        echo apply_filter( 'die_title', "<h2>$title</h2>" );
        echo apply_filter( 'die_message', "<p>$message</p>" );
        do_action( 'die' );

        if( !$head ) {
            template_content( 'after', 'die' );
        }
        die();
    }

    /**
     * Return an "Edit" row for the main table
     *
     * @param string $keyword Keyword to edit
     * @return string HTML of the edit row
     */
    function table_edit_row( $keyword ) {
        $keyword = sanitize_string( $keyword );
        $id = string2htmlid( $keyword ); // used as HTML #id
        $url = get_keyword_longurl( $keyword );

        $title = htmlspecialchars( get_keyword_title( $keyword ) );
        $safe_url = esc_attr( $url );
        $safe_title = esc_attr( $title );
        $www = link();

        $nonce = create_nonce( 'edit-save_'.$id );

        // @TODO: HTML Clean up
        if( $url ) {
            $return = '
            <tr id="edit-%id%" class="edit-row">
                <td class="edit-row">
                    <strong>%l10n_long_url%</strong>:<input type="text" id="edit-url-%id%" name="edit-url-%id%" value="%safe_url%" class="text" size="70" /><br/>
                    <strong>%l10n_short_url%</strong>: %www%<input type="text" id="edit-keyword-%id%" name="edit-keyword-%id%" value="%keyword%" class="text" size="10" /><br/>
                    <strong>%l10n_title%</strong>: <input type="text" id="edit-title-%id%" name="edit-title-%id%" value="%safe_title%" class="text" size="60" />
                </td>
                <td colspan="1">
                    <input type="button" id="edit-submit-%id%" name="edit-submit-%id%" value="%l10n_save%" title="%l10n_save%" class="button" onclick="edit_link_save(\'%id%\');" />
                    &nbsp;<input type="button" id="edit-close-$id" name="edit-close-%id%" value="%l10n_edit%" title="%l10n_edit%" class="button" onclick="edit_link_hide(\'%id%\');" />
                    <input type="hidden" id="old_keyword_%id%" value="%keyword%"/><input type="hidden" id="nonce_%id%" value="%nonce%"/>
                </td>
            </tr>
            ';

            $data = array(
                'id' => $id,
                'keyword' => $keyword,
                'safe_url' => $safe_url,
                'safe_title' => $safe_title,
                'nonce' => $nonce,
                'www' => link(),
                'l10n_long_url' => _( 'Long URL' ),
                'l10n_short_url' => _( 'Short URL' ),
                'l10n_title' => _( 'Title' ),
                'l10n_save' => _( 'Save' ),
                'l10n_edit' => _( 'Cancel' ),
            );

            $return = urldecode( replace_string_tokens( $format, $data ) );
        } else {
            $return = '<tr class="edit-row notfound"><td class="edit-row notfound">' . _( 'Error, URL not found' ) . '</td></tr>';
        }

        $return = apply_filter( 'table_edit_row', $return, $format, $data );
        // Compat note : up to YOURLS 1.6 the values passed to this filter where: $return, $keyword, $url, $title
        return $return;
    }

    /**
     * Return an "Add" row for the main table
     *
     * @return string HTML of the edit row
     */
    function table_add_row( $keyword, $url, $title = '', $ip, $clicks, $timestamp ) {
        $keyword  = sanitize_string( $keyword );
        $id       = string2htmlid( $keyword ); // used as HTML #id
        $shorturl = link( $keyword );

        $statlink = statlink( $keyword );

        $delete_link = nonce_url( 'delete-link-'.$id,
            add_query_arg( array( 'id' => $id, 'action' => 'delete', 'keyword' => $keyword ), admin_url( 'admin-ajax.php' ) )
        );

        $edit_link = nonce_url( 'edit-link-'.$id,
            add_query_arg( array( 'id' => $id, 'action' => 'edit', 'keyword' => $keyword ), admin_url( 'admin-ajax.php' ) )
        );

        // Action link buttons: the array
        $actions = array(
            'stats' => array(
                'href'    => $statlink,
                'id'      => "statlink-$id",
                'title'   => esc_attr__( 'Stats' ),
                'icon'    => "bar-chart-o",
                'anchor'  => _( 'Stats' ),
            ),
            'share' => array(
                'href'    => '',
                'id'      => "share-button-$id",
                'title'   => esc_attr__( 'Share' ),
                'anchor'  => _( 'Share' ),
                'icon'    => "share-square-o",
                'onclick' => "toggle_share('$id');return false;",
            ),
            'edit' => array(
                'href'    => $edit_link,
                'id'      => "edit-button-$id",
                'title'   => esc_attr__( 'Edit' ),
                'anchor'  => _( 'Edit' ),
                'icon'    => "edit",
                'onclick' => "edit_link_display('$id');return false;",
            ),
            'delete' => array(
                'href'    => $delete_link,
                'id'      => "delete-button-$id",
                'title'   => esc_attr__( 'Delete' ),
                'anchor'  => _( 'Delete' ),
                'icon'    => "trash-o",
                'onclick' => "remove_link('$id');return false;",
            )
        );
        $actions = apply_filter( 'table_add_row_action_array', $actions );

        // @TODO: HTML Clean up
        // Action link buttons: the HTML
        $action_links = '<div class="btn-group">';
        foreach( $actions as $key => $action ) {
            $onclick = isset( $action['onclick'] ) ? 'onclick="' . $action['onclick'] . '"' : '' ;
            $action_links .= sprintf( '<a href="%s" id="%s" title="%s" class="%s" %s><i class="fa fa-%s"></i></a>',
                $action['href'], $action['id'], $action['title'], 'btn btn-'.$key, $onclick, $action['icon']
            );
        }
        $action_links .= '</div>';
        $action_links  = apply_filter( 'action_links', $action_links, $keyword, $url, $ip, $clicks, $timestamp );

        if( ! $title )
            $title = $url;

        $protocol_warning = '';
        if( ! in_array( get_protocol( $url ) , array( 'http://', 'https://' ) ) )
            $protocol_warning = apply_filters( 'add_row_protocol_warning', '<i class="warning protocol_warning fa fa-exclamation-circle" title="' . _( 'Not a common link' ) . '"></i> ' );

        // Row template that you can filter before it's parsed (don't remove HTML classes & id attributes)
        $format = '<tr id="id-%id%">
        <td class="keyword btn-clipboard" id="keyword-%id%" %copy%><a href="%shorturl%">%keyword_html%</a></td>
        <td class="url" id="url-%id%">
            <a href="%long_url%" title="%title_attr%">%title_html%</a><br/>
            <small class="longurl">%warning%<a href="%long_url%">%long_url_html%</a></small><br/>
            <input type="hidden" id="keyword_%id%" value="%keyword%"/>
            <input type="hidden" id="shorturl-%id%" value="%shorturl%"/>
            <input type="hidden" id="longurl-%id%" value="%long_url%"/>
            <input type="hidden" id="title-%id%" value="%title_attr%"/>
            <div class="actions" id="actions-%id%">
                <p><small class="added_on">%added_on_from%</small><p>
                <p>%actions%</p>
            </div>
        </td>
        <td class="clicks" id="clicks-%id%">%clicks%</td>
        </tr>';

        // Highlight domain in displayed URL
        $domain = parse_url( $url, PHP_URL_HOST );
        if( $domain ) {
            if( substr( $domain, 0, 4 ) == 'www.' ) {
                $domain = substr( $domain, 4 );
            }
            $display_url = preg_replace( "/$domain/", '<strong class="domain">' . $domain . '</strong>', $url, 1 );
        } else {
            $display_url = $url;
        }

        $data = array(
            'id'            => $id,
                'shorturl'      => esc_url( $shorturl ),
            'keyword'       => esc_attr( $keyword ),
                'keyword_html'  => esc_html( $keyword ),
                'long_url'      => esc_url( $url ),
            'long_url_html' => trim_long_string( $display_url, 100 ),
                'title_attr'    => esc_attr( $title ),
                'title_html'    => esc_html( trim_long_string( $title ) ),
                'warning'       => $protocol_warning,
            'added_on_from' => s( 'Added on <span class="timestamp">%s</span> from <span class="ip">%s</span>', date( 'M d, Y H:i', $timestamp +( HOURS_OFFSET * 3600 ) ), $ip ),
            'clicks'        => number_format_i18n( $clicks, 0, '', '' ),
            'actions'       => $action_links,
            'copy'          => 'data-clipboard-target="' . 'shorturl-' . $id /*. '" data-copied-hint="' . _( 'Copied!' ) . '" data-placement="top" data-trigger="manual" data-original-title="' . _( 'Copy to clipboard' ) */. '"',
        );

        $row = replace_string_tokens( $format, $data );
        $row = apply_filter( 'table_add_row', $row, $format, $data );
        // Compat note : up to YOURLS 1.6 the values passed to this filter where: $keyword, $url, $title, $ip, $clicks, $timestamp
        return $row;
    }

    /**
     * Echo the main table head
     *
     */
    function table_head( $data = null ) {
        echo apply_filter( 'table_head_start', '<thead><tr>' );

        if( $data === null )  {
            $data = array(
            'shorturl' => _( 'Short URL' ),
            'longurl'  => _( 'Original URL' ),
            'clicks'   => _( 'Clicks' ),
            );
        }

        $cells = '';
        foreach( $data as $id => $name ) {
            $cells .= '<th id="table-head-' . $id . '">' . $name . '</th>';
        }
        echo apply_filter( 'table_head_cells', $cells, $data );
        echo apply_filter( 'table_head_end', '</tr></thead>' );
    }

    /**
     * Echo the tbody start tag
     *
     */
    function table_tbody_start() {
        echo apply_filter( 'table_tbody_start', '<tbody class="list">' );
    }

    /**
     * Echo the tbody end tag
     *
     */
    function table_tbody_end() {
        echo apply_filter( 'table_tbody_end', '</tbody>' );
    }

    /**
     * Echo the table start tag
     *
     */
    function table_start( $div_id = '', $table_class = '' ) {
        echo apply_filter( 'table_start', '<div id="' . $div_id . '"><table class="' . $table_class . '">', $table_class );
    }

    /**
     * Echo the table end tag
     *
     */
    function table_end() {
        echo apply_filter( 'table_end', '</table></div>' );
    }

    /**
     * Echo the content start tag
     *
     * @since 2.0
     */
    function wrapper_start() {
        do_action( 'admin_notice' );
        echo apply_filter( 'wrapper_start', '<div class="content" role="main">' );
    }

    /**
     * Echo the content end tag
     *
     * @since 2.0
     */
    function wrapper_end() {
        echo apply_filter( 'wrapper_end', '</div></div>' );
        if( defined( 'DEBUG' ) && DEBUG == true ) {
            html_debug();
        }
    }

    /**
     * Echo the sidebar start tag
     *
     * @since 2.0
     */
    function sidebar_start() {
        echo apply_filter( 'sidebar_start', '<div class="sidebar-container"><div class="sidebar">
        <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-responsive-collapse">
          <i class="fa fa-bars"></i>
        </button>' );
    }

    /**
     * Echo the sidebar end tag
     *
     * @since 2.0
     */
    function sidebar_end() {
        echo apply_filter( 'sidebar_end', '</div></div>' );
    }

    /**
     * Echo HTML tag for a link
     *
     * @param string $href Where the link point
     * @param string $content
     * @param string $title Optionnal "title" attribut
     * @param bool $class Optionnal "class" attribut
     * @param bool $echo
     * @return HTML tag with all contents
     */
    function html_link( $href, $content = '', $title = '', $class = false, $echo = true ) {
        if( !$content )
            $content = esc_html( $href );
        if( $title ) {
            $title = sprintf( ' title="%s"', esc_attr( $title ) );
            if( $class )
                $class = sprintf( ' class="%s"', esc_attr( $title ) );
        }
        $link = sprintf( '<a href="%s"%s%s>%s</a>', esc_url( $href ), $class, $title, $content );
        if ( $echo )
            echo apply_filter( 'html_link', $link );
        else
            return apply_filter( 'html_link', $link );
    }

    /**
     * Display the login screen. Nothing past this point.
     *
     */
    function login_screen( $error_msg = '' ) {
        // Since the user is not authed, we don't disclose any kind of stats
        remove_from_template( 'html_global_stats' );

        html_head( 'login' );

        $action = ( isset( $_GET['action'] ) && $_GET['action'] == 'logout' ? '?' : '' );

        template_content( 'before' );
        html_htag( 'YOURLS', 1, 'Your Own URL Shortener' );

        ?>
            <div id="login">
                <form method="post" class="login-screen" action="<?php echo $action; // reset any QUERY parameters ?>">
                    <?php
                    if( !empty( $error_msg ) ) {
                        echo notice_box( $error_msg[0], $error_msg[1] );
        }
                ?>
                    <div class="control-group">
                        <label class="control-label" for="username"><?php e( 'Username' ); ?></label>
                        <div class="controls">
                            <input type="text" id="username" name="username" class="text" autofocus="autofocus">
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="password"><?php e( 'Password' ); ?></label>
                        <div class="controls">
                            <input type="password" id="password" name="password" class="text">
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="submit" name="submit"><?php e( 'Login' ); ?></button>
                    </div>
                </form>
            </div>
<?php

        template_content( 'after' );

        die();
    }

    /**
     * Output translated strings used by the Javascript calendar
     *
     * @since 1.6
     */
    function l10n_calendar_strings() {
        echo "<script>";
        echo "var l10n_cal_month = " . json_encode( array_values( l10n_months() ) ) . ";";
        echo "var l10n_cal_days = " . json_encode( array_values( l10n_weekday_initial() ) ) . ";";
        echo "var l10n_cal_today = \"" . esc_js( _( 'Today' ) ) . "\";";
        echo "var l10n_cal_close = \"" . esc_js( _( 'Close' ) ) . "\";";
        echo "</script>";

        // Dummy returns, to initialize l10n strings used in the calendar
        _( 'Today' );
        _( 'Close' );
    }


    /**
     * Display a notice if there is a newer version of YOURLS available
     *
     * @since 1.7
     */
    function new_core_version_notice() {

        debug_log( 'Check for new version: ' . ( maybe_check_core_version() ? 'yes' : 'no' ) );

        $checks = get_option( 'core_version_checks' );

        if( isset( $checks->last_result->latest ) AND version_compare( $checks->last_result->latest, VERSION, '>' ) ) {
            $msg = s( '<a href="%s">YOURLS version %s</a> is available. Please update!', 'http://yourls.org/download', $checks->last_result->latest );
            add_notice( $msg );
        }
    }

    /**
     * Send a filerable content type header
     *
     * @since 1.7
     * @param string $type content type ('text/html', 'application/json', ...)
     * @return bool whether header was sent
     */
    function content_type_header( $type ) {
        if( !headers_sent() ) {
            $charset = apply_filters( 'content_type_header_charset', 'utf-8' );
            header( "Content-Type: $type; charset=$charset" );

            return true;
        }

        return false;
    }

    /**
     * Get search text from query string variables search_protocol, search_slashes and search
     *
     * Some servers don't like query strings containing "(ht|f)tp(s)://". A javascript bit
     * explodes the search text into protocol, slashes and the rest (see JS function
     * split_search_text_before_search()) and this function glues pieces back together
     * See issue https://github.com/YOURLS/YOURLS/issues/1576
     *
     * @since 1.7
     * @return string Search string
     */
    function get_search_text() {
        $search = '';
        if( isset( $_GET['search_protocol'] ) )
            $search .= $_GET['search_protocol'];
        if( isset( $_GET['search_slashes'] ) )
            $search .= $_GET['search_slashes'];
        if( isset( $_GET['search'] ) )
            $search .= $_GET['search'];

        return htmlspecialchars( trim( $search ) );
    }

    /**
     * Display custom message based on query string parameter 'login_msg'
     *
     * @since 1.7
     */
    function display_login_message() {
        if( !isset( $_GET['login_msg'] ) )

            return;

        switch( $_GET['login_msg'] ) {
            case 'pwdclear':
                $message  = html_htag( _( 'Warning' ), 4, null, null, false );
                $message .= '<p>' . _( 'Your password is stored as clear text in your <code>config.php</code>' );
                $message .= '<br />' . _( 'Did you know you can easily improve the security of your YOURLS install by <strong>encrypting</strong> your password?' );
                $message .= '<br />' . _( 'See <a href="http://yourls.org/userpassword">UsernamePassword</a> for details.' ) . '</p>';
                add_notice( $message, 'notice' );
                break;
        }
    }

    /**
     * Close html page
     *
     * @since 2.0
     */
    function html_ending() {
        do_action( 'html_ending' );
        echo '</div></body></html>';
    }

    /**
     * Add a callout container
     *
     * @since 2.0
     */
    function html_callout( $type, $content, $title = '' ) {
        echo '<div class="callout callout-' . $type . '">';
        if ( $title != '' )
            html_htag( $title, 4 );
        echo $content;
        echo '</div>';
    }

}

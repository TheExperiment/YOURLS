<?php

/**
 * HTML Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTML;

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
    public function head( $context = 'index', $title = '' ) {

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
<html <?php $this->language_attributes(); ?>>
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
        var moviepath = '<?php site_url( true, YOURLS_ASSETURL . '/js/ZeroClipboard.swf' ); ?>';

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
    public function logo( $linked = true ) {
        do_action( 'pre_html_logo' );
        $logo = '<img class="yourls-logo-img" src="' . site_url( false, YOURLS_ASSETURL . '/img/yourls-logo.png' ) . '" alt="YOURLS" title="YOURLS"/>';
        if ( $linked )
            $logo = $this->link( admin_url( 'index' ), $logo, 'YOURLS', false, false );
        ?>
            <div class="yourls-logo">
                <?php echo $logo; ?>
            </div>
            <?php
        do_action( 'html_logo' );
    }

    /**
     * Display the admin menu
     *
     * @param string $current_page Which page is loaded?
     */
    public function menu( $current_page = null ) {
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

        if( ( is_admin() && is_public_or_logged() ) || defined( 'YOURLS_USER' ) ) {
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
        if( defined( 'YOURLS_USER' ) && is_private() ) {
            $menu .= apply_filter( 'logout_link', '<div class="nav-header">' . sprintf( _( 'Hello <strong>%s</strong>' ), YOURLS_USER ) . '<a href="?action=logout" title="' . esc_attr__( 'Logout' ) . '" class="pull-right"><i class="fa fa-sign-out fa-fw"></i></a></div>' );
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
    public function global_stats() {
        list( $total_urls, $total_clicks ) = array_values( get_db_stats() );
        // @FIXME: this SQL query is also used in admin/index.php - reduce query count
        $html  = '<div class="global-stats"><div class="global-stats-data">';
        $html .= '<strong class="status-number increment">' . number_format_i18n( $total_urls ) . '</strong><p>' . _( 'Links' );
        $html .= '</p></div><div class="global-stats-data">';
        $html .= '<strong class="status-number">' . number_format_i18n( $total_clicks ) . '</strong><p>' . _( 'Clicks' ) . '</p></div></div>';
        echo apply_filters( 'html_global_stats', $html );
    }

    /**
     * Display HTML footer (including closing body & html tags)
     *
     */
    public function footer() {
        echo '<hr /><div class="footer" role="contentinfo"><p>';
        $footer  = s( 'Powered by %s', $this->link( 'http://yourls.org/', 'YOURLS', 'YOURLS', false, false ) );
            echo apply_filters( 'html_footer_text', $footer );
        echo '</p></div>';
    }

    /**
     * Display "Add new URL" box
     *
     * @param string $url URL to prefill the input with
     * @param string $keyword Keyword to prefill the input with
     */
    public function addnew( $url = '', $keyword = '' ) {
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
    public function search( $params = array() ) {
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
                            $_select_search = $this->select( 'search_in', $_options, $search_in );
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
                            $_select_order = $this->select( 'sort_by', $_options, $sort_by );
                            $sort_order = isset( $sort_order ) ? $sort_order : 'desc' ;
                            $_options = array(
                                'asc'  => _( 'Ascending' ),
                                'desc' => _( 'Descending' ),
                            );
                            $_select2_order = $this->select( 'sort_order', $_options, $sort_order );

                            // Fourth search control: Show links with more than XX clicks
                            $_options = array(
                                'more' => _( 'more' ),
                                'less' => _( 'less' ),
                            );
                            $_select_clicks = $this->select( 'click_filter', $_options, $click_filter );
                            $_input_clicks  = '<input type="text" name="click_limit" class="form-control" value="' . $click_limit . '" /> ';

                            // Fifth search control: Show links created before/after/between ...
                            $_options = array(
                                'before'  => _( 'before' ),
                                'after'   => _( 'after' ),
                                'between' => _( 'between' ),
                            );
                            $_select_creation = $this->select( 'date_filter', $_options, $date_filter );
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
     * Output translated strings used by the Javascript calendar
     *
     * @since 1.6
     */
    public function l10n_calendar_strings() {
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

}

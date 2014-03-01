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
class Wrappers {

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
    public function htag( $title, $size = 1, $subtitle = null, $class = null, $echo = true ) {
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
     * Wrapper function to display label
     *
     * @since 2.0
     * @param string $message The message showed
     * @param string $style notice / error / info / warning / success
     */
    public function add_label( $message, $style = 'normal', $space = null ) {
        $label = '<span class="label label-' . $style . '">' . $message . '</span>';
        if ( $space )
            $label = $space == 'before' ? ' ' . $label : $label . ' ';
        echo $label;
    }

    /**
     * Display the language attributes for the HTML tag.
     *
     * Builds up a set of html attributes containing the text direction and language
     * information for the page. Stolen from WP.
     *
     * @since 1.6
     */
    public function language_attributes() {
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
     * Wrapper function to display the global pagination on interface
     *
     * @param array $params
     */
    public function pagination( $params = array() ) {
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
    public function displaying_count( $item_type, $min_on_page, $max_on_page, $total_items ) {
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
    public function select( $name, $options, $selected = '', $display = false ) {
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
     * Display or return the ZeroClipboard button, with Tooltip additions
     *
     * @since 1.7
     * @param string $clipboard_target Id of the fetched element to copy value
     * @param bool $echo true to print, false to return
     */
    public function zeroclipboard( $clipboard_target, $echo = true ) {
        $html = apply_filter( 'html_zeroclipboard',
        '<button class="btn-clipboard" data-copied-hint="' . _( 'Copied!' ) . '" data-clipboard-target="' . $clipboard_target . '" data-placement="bottom" data-trigger="manual" data-original-title="' . _( 'Copy to clipboard' ) . '"><i class="fa fa-copy"></i></button>',
        $clipboard_target );
        if( $echo )
            echo $html;

        return $html;
    }

    /**
     * Echo the content start tag
     *
     * @since 2.0
     */
    public function wrapper_start() {
        do_action( 'admin_notice' );
        echo apply_filter( 'wrapper_start', '<div class="content" role="main">' );
    }

    /**
     * Echo the content end tag
     *
     * @since 2.0
     */
    public function wrapper_end() {
        echo apply_filter( 'wrapper_end', '</div></div>' );
        if( defined( 'YOURLS_DEBUG' ) && YOURLS_DEBUG == true ) {
            $this->debug();
        }
    }

    /**
     * Echo the sidebar start tag
     *
     * @since 2.0
     */
    public function sidebar_start() {
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
    public function sidebar_end() {
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
    public function link( $href, $content = '', $title = '', $class = false, $echo = true ) {
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
     * Close html page
     *
     * @since 2.0
     */
    public function ending() {
        do_action( 'html_ending' );
        echo '</div></body></html>';
    }

    /**
     * Add a callout container
     *
     * @since 2.0
     */
    public function callout( $type, $content, $title = '' ) {
        echo '<div class="callout callout-' . $type . '">';
        if ( $title != '' )
            $this->htag( $title, 4 );
        echo $content;
        echo '</div>';
    }

}

<?php

/**
 * Table Wrapper
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
class Table {

    /**
     * Return an "Edit" row for the main table
     *
     * @param string $keyword Keyword to edit
     * @return string HTML of the edit row
     */
    public function table_edit_row( $keyword ) {
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

        $return = Filters::apply_filter( 'table_edit_row', $return, $format, $data );
        // Compat note : up to YOURLS 1.6 the values passed to this filter where: $return, $keyword, $url, $title
        return $return;
    }

    /**
     * Return an "Add" row for the main table
     *
     * @return string HTML of the edit row
     */
    public function table_add_row( $keyword, $url, $title = '', $ip, $clicks, $timestamp ) {
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
        $actions = Filters::apply_filter( 'table_add_row_action_array', $actions );

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
        $action_links  = Filters::apply_filter( 'action_links', $action_links, $keyword, $url, $ip, $clicks, $timestamp );

        if( ! $title )
            $title = $url;

        $protocol_warning = '';
        if( ! in_array( get_protocol( $url ) , array( 'http://', 'https://' ) ) )
            $protocol_warning = Filters::apply_filters( 'add_row_protocol_warning', '<i class="warning protocol_warning fa fa-exclamation-circle" title="' . _( 'Not a common link' ) . '"></i> ' );

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
        $row = Filters::apply_filter( 'table_add_row', $row, $format, $data );
        // Compat note : up to YOURLS 1.6 the values passed to this filter where: $keyword, $url, $title, $ip, $clicks, $timestamp
        return $row;
    }

    /**
     * Echo the main table head
     *
     */
    public function table_head( $data = null ) {
        echo Filters::apply_filter( 'table_head_start', '<thead><tr>' );

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
        echo Filters::apply_filter( 'table_head_cells', $cells, $data );
        echo Filters::apply_filter( 'table_head_end', '</tr></thead>' );
    }

    /**
     * Echo the tbody start tag
     *
     */
    public function table_tbody_start() {
        echo Filters::apply_filter( 'table_tbody_start', '<tbody class="list">' );
    }

    /**
     * Echo the tbody end tag
     *
     */
    public function table_tbody_end() {
        echo Filters::apply_filter( 'table_tbody_end', '</tbody>' );
    }

    /**
     * Echo the table start tag
     *
     */
    public function table_start( $div_id = '', $table_class = '' ) {
        echo Filters::apply_filter( 'table_start', '<div id="' . $div_id . '"><table class="' . $table_class . '">', $table_class );
    }

    /**
     * Echo the table end tag
     *
     */
    public function table_end() {
        echo Filters::apply_filter( 'table_end', '</table></div>' );
    }

}

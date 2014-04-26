<?php

/**
 * Shortener Engine
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Shortener;

class Link {

    /**
     * Keyword expression
     * @var \Keyword
     */
    private $keyword;

    /**
     * Keyword expression
     * @var \URL
     */
    private $url;

    /**
     * Add a new link in the DB, either with custom keyword, or find one
     *
     */
    public function add() {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_add_new_link', false, $url, $keyword, $title );
        if ( false !== $pre )
            return $pre;

        $url = encodeURI( $url );
        $url = escape( sanitize_url( $url ) );
        if ( !$url || $url == 'http://' || $url == 'https://' ) {
            $return['status']    = 'fail';
            $return['code']      = 'error:nourl';
            $return['message']   = _( 'Missing or malformed URL' );
            $return['errorCode'] = '400';

            return Filters::apply_filter( 'add_new_link_fail_nourl', $return, $url, $keyword, $title );
        }

        // Prevent DB flood
        $ip = get_IP();
        check_IP_flood( $ip );

        // Prevent internal redirection loops: cannot shorten a shortened URL
        if( get_relative_url( $url ) ) {
            if( is_shorturl( $url ) ) {
                $return['status']    = 'fail';
                $return['code']      = 'error:noloop';
                $return['message']   = _( 'URL is a short URL' );
                $return['errorCode'] = '400';

                return Filters::apply_filter( 'add_new_link_fail_noloop', $return, $url, $keyword, $title );
            }
        }

        Filters::do_action( 'pre_add_new_link', $url, $keyword, $title );

        $strip_url = stripslashes( $url );
        $return = array();

        // duplicates allowed or new URL => store it
        if( allow_duplicate_longurls() || !( $url_exists = url_exists( $url ) ) ) {

            if( isset( $title ) && !empty( $title ) ) {
                $title = sanitize_title( $title );
            } else {
                $title = get_remote_title( $url );
            }
            $title = Filters::apply_filter( 'add_new_title', $title, $url, $keyword );

            // Custom keyword provided
            if ( $keyword ) {

                Filters::do_action( 'add_new_link_custom_keyword', $url, $keyword, $title );

                $keyword = escape( sanitize_string( $keyword ) );
                $keyword = Filters::apply_filter( 'custom_keyword', $keyword, $url, $title );
                if ( !keyword_is_free( $keyword ) ) {
                    // This shorturl either reserved or taken already
                    $return['status']  = 'fail';
                    $return['code']    = 'error:keyword';
                    $return['message'] = s( 'Short URL %s already exists in database or is reserved', $keyword );
                } else {
                    // all clear, store !
                    insert_link_in_db( $url, $keyword, $title );
                    $return['url']      = array('keyword' => $keyword, 'url' => $strip_url, 'title' => $title, 'date' => date('Y-m-d H:i:s'), 'ip' => $ip );
                    $return['status']   = 'success';
                    $return['message']  = /* //translators: eg "http://someurl/ added to DB" */ s( '%s added to database', trim_long_string( $strip_url ) );
                    $return['title']    = $title;
                    $return['html']     = table_add_row( $keyword, $url, $title, $ip, 0, time() );
                    $return['shorturl'] = SITE .'/'. $keyword;
                }

                // Create random keyword
            } else {

                Filters::do_action( 'add_new_link_create_keyword', $url, $keyword, $title );

                $timestamp = date( 'Y-m-d H:i:s' );
                $id = get_next_decimal();
                $ok = false;
                do {
                    $keyword = int2string( $id );
                    $keyword = Filters::apply_filter( 'random_keyword', $keyword, $url, $title );
                    if ( keyword_is_free($keyword) ) {
                        if( @insert_link_in_db( $url, $keyword, $title ) ){
                            // everything ok, populate needed vars
                            $return['url']      = array('keyword' => $keyword, 'url' => $strip_url, 'title' => $title, 'date' => $timestamp, 'ip' => $ip );
                            $return['status']   = 'success';
                            $return['message']  = /* //translators: eg "http://someurl/ added to DB" */ s( '%s added to database', trim_long_string( $strip_url ) );
                            $return['title']    = $title;
                            $return['html']     = table_add_row( $keyword, $url, $title, $ip, 0, time() );
                            $return['shorturl'] = SITE .'/'. $keyword;
                        }else{
                            // database error, couldnt store result
                            $return['status']   = 'fail';
                            $return['code']     = 'error:db';
                            $return['message']  = s( 'Error saving url to database' );
                        }
                        $ok = true;
                    }
                    $id++;
                } while ( !$ok );
                @update_next_decimal( $id );
            }

            // URL was already stored
        } else {

            Filters::do_action( 'add_new_link_already_stored', $url, $keyword, $title );

            $return['status']   = 'fail';
            $return['code']     = 'error:url';
            $return['url']      = array( 'keyword' => $url_exists->keyword, 'url' => $strip_url, 'title' => $url_exists->title, 'date' => $url_exists->timestamp, 'ip' => $url_exists->ip, 'clicks' => $url_exists->clicks );
            $return['message']  = /* //translators: eg "http://someurl/ already exists" */ s( '%s already exists in database', trim_long_string( $strip_url ) );
            $return['title']    = $url_exists->title;
            $return['shorturl'] = SITE .'/'. $url_exists->keyword;
        }

        Filters::do_action( 'post_add_new_link', $url, $keyword, $title );

        $return['statusCode'] = 200; // regardless of result, this is still a valid request

        return Filters::apply_filter( 'add_new_link', $return, $url, $keyword, $title );
    }

    /**
     * Edit a link
     *
     */
    public function edit_link( $newkeyword = '', $title = '' ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_edit_link', null, $keyword, $url, $keyword, $newkeyword, $title );
        if ( null !== $pre )
            return $pre;

        global $ydb;

        $table = YOURLS_DB_TABLE_URL;
        $url = escape (sanitize_url( $url ) );
        $keyword = escape( sanitize_string( $keyword ) );
        $title = escape( sanitize_title( $title ) );
        $newkeyword = escape( sanitize_string( $newkeyword ) );
        $strip_url = stripslashes( $url );
        $strip_title = stripslashes( $title );
        $old_url = $ydb->get_var( "SELECT `url` FROM `$table` WHERE `keyword` = '$keyword';" );

        // Check if new URL is not here already
        if ( $old_url != $url && !allow_duplicate_longurls() ) {
            $new_url_already_there = intval($ydb->get_var("SELECT COUNT(keyword) FROM `$table` WHERE `url` = '$url';"));
        } else {
            $new_url_already_there = false;
        }

        // Check if the new keyword is not here already
        if ( $newkeyword != $keyword ) {
            $keyword_is_ok = keyword_is_free( $newkeyword );
        } else {
            $keyword_is_ok = true;
        }

        Filters::do_action( 'pre_edit_link', $url, $keyword, $newkeyword, $new_url_already_there, $keyword_is_ok );

        // All clear, update
        if ( ( !$new_url_already_there || allow_duplicate_longurls() ) && $keyword_is_ok ) {
            $update_url = $ydb->query( "UPDATE `$table` SET `url` = '$url', `keyword` = '$newkeyword', `title` = '$title' WHERE `keyword` = '$keyword';" );
            if( $update_url ) {
                $return['url']     = array( 'keyword' => $newkeyword, 'shorturl' => SITE.'/'.$newkeyword, 'url' => $strip_url, 'display_url' => trim_long_string( $strip_url ), 'title' => $strip_title, 'display_title' => trim_long_string( $strip_title ) );
                $return['status']  = 'success';
                $return['message'] = _( 'Link updated in database' );
            } else {
                $return['status']  = 'fail';
                $return['message'] = /* //translators: "Error updating http://someurl/ (Shorturl: http://sho.rt/blah)" */ s( 'Error updating %s (Short URL: %s)', trim_long_string( $strip_url ), $keyword ) ;
            }

            // Nope
        } else {
            $return['status']  = 'fail';
            $return['message'] = _( 'URL or keyword already exists in database' );
        }

        return Filters::apply_filter( 'edit_link', $return, $url, $keyword, $newkeyword, $title, $new_url_already_there, $keyword_is_ok );
    }
    /**
     * Update a title link (no checks for duplicates etc..)
     *
     */
    public function edit_link_title( $keyword, $title ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_edit_link_title', null, $keyword, $title );
        if ( null !== $pre )
            return $pre;

        global $ydb;

        $keyword = escape( sanitize_keyword( $keyword ) );
        $title = escape( sanitize_title( $title ) );

        $table = YOURLS_DB_TABLE_URL;
        $update = $ydb->query("UPDATE `$table` SET `title` = '$title' WHERE `keyword` = '$keyword';");

        return $update;
    }

    /**
     * Return array of stats. (string)$filter is 'bottom', 'last', 'rand' or 'top'. (int)$limit is the number of links to return
     *
     */
    public function get_link_stats( $shorturl ) {
        global $ydb;

        $table_url = YOURLS_DB_TABLE_URL;
        $shorturl  = escape( sanitize_keyword( $shorturl ) );

        $res = $ydb->get_row( "SELECT * FROM `$table_url` WHERE keyword = '$shorturl';" );
        $return = array();

        if( !$res ) {
            // non existent link
            $return = array(
                'statusCode' => 404,
                'message'    => 'Error: short URL not found',
            );
        } else {
            $return = array(
                'statusCode' => 200,
                'message'    => 'success',
                'link'       => array(
                    'shorturl' => SITE .'/'. $res->keyword,
                    'url'      => $res->url,
                    'title'    => $res->title,
                    'timestamp'=> $res->timestamp,
                    'ip'       => $res->ip,
                    'clicks'   => $res->clicks,
                )
            );
        }

        return Filters::apply_filter( 'get_link_stats', $return, $shorturl );
    }

}

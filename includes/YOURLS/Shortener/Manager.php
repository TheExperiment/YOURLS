<?php

/**
 * Manager
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Shortener;

class Manager {

    /**
     * Edit a link
     *
     */
    public function edit_link( $url, $keyword, $newkeyword='', $title='' ) {
        // Allow plugins to short-circuit the whole function
        $pre = apply_filter( 'shunt_edit_link', null, $keyword, $url, $keyword, $newkeyword, $title );
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

        do_action( 'pre_edit_link', $url, $keyword, $newkeyword, $new_url_already_there, $keyword_is_ok );

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

        return apply_filter( 'edit_link', $return, $url, $keyword, $newkeyword, $title, $new_url_already_there, $keyword_is_ok );
    }

    /**
     * Return array of all information associated with keyword. Returns false if keyword not found. Set optional $use_cache to false to force fetching from DB
     *
     */
    public function get_keyword_infos( $keyword, $use_cache = true ) {
        global $ydb;
        $keyword = escape( sanitize_string( $keyword ) );

        do_action( 'pre_get_keyword', $keyword, $use_cache );

        if( isset( $ydb->infos[$keyword] ) && $use_cache == true ) {
            return apply_filter( 'get_keyword_infos', $ydb->infos[$keyword], $keyword );
        }

        do_action( 'get_keyword_not_cached', $keyword );

        $table = YOURLS_DB_TABLE_URL;
        $infos = $ydb->get_row( "SELECT * FROM `$table` WHERE `keyword` = '$keyword'" );

        if( $infos ) {
            $infos = (array)$infos;
            $ydb->infos[ $keyword ] = $infos;
        } else {
            $ydb->infos[ $keyword ] = false;
        }

        return apply_filter( 'get_keyword_infos', $ydb->infos[$keyword], $keyword );
    }

    /**
     * Return (string) selected information associated with a keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_keyword_info( $keyword, $field, $notfound = false ) {

        // Allow plugins to short-circuit the whole function
        $pre = apply_filter( 'shunt_get_keyword_info', false, $keyword, $field, $notfound );
        if ( false !== $pre )
            return $pre;

        $keyword = sanitize_string( $keyword );
        $infos = get_keyword_infos( $keyword );

        $return = $notfound;
        if ( isset( $infos[ $field ] ) && $infos[ $field ] !== false )
            $return = $infos[ $field ];

        return apply_filter( 'get_keyword_info', $return, $keyword, $field, $notfound );
    }

    /**
     * Update click count on a short URL. Return 0/1 for error/success.
     *
     */
    public function update_clicks( $keyword, $clicks = false ) {
        // Allow plugins to short-circuit the whole function
        $pre = apply_filter( 'shunt_update_clicks', false, $keyword, $clicks );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $keyword = escape( sanitize_string( $keyword ) );
        $table = YOURLS_DB_TABLE_URL;
        if ( $clicks !== false && is_int( $clicks ) && $clicks >= 0 )
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = $clicks WHERE `keyword` = '$keyword'" );
        else
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = clicks + 1 WHERE `keyword` = '$keyword'" );

        do_action( 'update_clicks', $keyword, $update, $clicks );

        return $update;
    }

    /**
     * Return array of stats. (string)$filter is 'bottom', 'last', 'rand' or 'top'. (int)$limit is the number of links to return
     *
     */
    public function get_stats( $filter = 'top', $limit = 10, $start = 0 ) {
        global $ydb;

        switch( $filter ) {
            case 'bottom':
                $sort_by    = 'clicks';
                $sort_order = 'asc';
                break;
            case 'last':
                $sort_by    = 'timestamp';
                $sort_order = 'desc';
                break;
            case 'rand':
            case 'random':
                $sort_by    = 'RAND()';
                $sort_order = '';
                break;
            case 'top':
            default:
                $sort_by    = 'clicks';
                $sort_order = 'desc';
                break;
        }

        // Fetch links
        $limit = intval( $limit );
        $start = intval( $start );
        if ( $limit > 0 ) {

            $table_url = YOURLS_DB_TABLE_URL;
            $results = $ydb->get_results( "SELECT * FROM `$table_url` WHERE 1=1 ORDER BY `$sort_by` $sort_order LIMIT $start, $limit;" );

            $return = array();
            $i = 1;

            foreach ( (array)$results as $res ) {
                $return['links']['link_'.$i++] = array(
                    'shorturl' => SITE .'/'. $res->keyword,
                    'url'      => $res->url,
                    'title'    => $res->title,
                    'timestamp'=> $res->timestamp,
                    'ip'       => $res->ip,
                    'clicks'   => $res->clicks,
                );
            }
        }

        $return['stats'] = get_db_stats();

        $return['statusCode'] = 200;

        return apply_filter( 'get_stats', $return, $filter, $limit, $start );
    }

    /**
     * Get total number of URLs and sum of clicks. Input: optional "AND WHERE" clause. Returns array
     *
     * IMPORTANT NOTE: make sure arguments for the $where clause have been sanitized and escape()'d
     * before calling this function.
     *
     */
    public function get_db_stats( $where = '' ) {
        global $ydb;
        $table_url = YOURLS_DB_TABLE_URL;

        $totals = $ydb->get_row( "SELECT COUNT(keyword) as count, SUM(clicks) as sum FROM `$table_url` WHERE 1=1 $where" );
        $return = array( 'total_links' => $totals->count, 'total_clicks' => $totals->sum );

        return apply_filter( 'get_db_stats', $return, $where );
    }
    /**
     * Get number of SQL queries performed
     *
     */
    public function get_num_queries() {
        global $ydb;

        return apply_filter( 'get_num_queries', $ydb->num_queries );
    }

    /**
     * Log a redirect (for stats)
     *
     * This function does not check for the existence of a valid keyword, in order to save a query. Make sure the keyword
     * exists before calling it.
     *
     * @since 1.4
     * @param string $keyword short URL keyword
     * @return mixed Result of the INSERT query (1 on success)
     */
    public function log_redirect( $keyword ) {
        // Allow plugins to short-circuit the whole function
        $pre = apply_filter( 'shunt_log_redirect', false, $keyword );
        if ( false !== $pre )
            return $pre;

        if ( !do_log_redirect() )
            return true;

        global $ydb;
        $table = YOURLS_DB_TABLE_LOG;

        $keyword  = escape( sanitize_string( $keyword ) );
        $referrer = ( isset( $_SERVER['HTTP_REFERER'] ) ? escape( sanitize_url( $_SERVER['HTTP_REFERER'] ) ) : 'direct' );
        $ua       = escape( get_user_agent() );
        $ip       = escape( get_IP() );
        $location = escape( geo_ip_to_countrycode( $ip ) );

        return $ydb->query( "INSERT INTO `$table` (click_time, shorturl, referrer, user_agent, ip_address, country_code) VALUES (NOW(), '$keyword', '$referrer', '$ua', '$ip', '$location')" );
    }

    /**
     * Check if we want to not log redirects (for stats)
     *
     */
    public function do_log_redirect() {
        return ( !defined( 'YOURLS_NOSTATS' ) || YOURLS_NOSTATS != true );
    }

    /**
     * Return array of keywords that redirect to the submitted long URL
     *
     * @since 1.7
     * @param string $longurl long url
     * @param string $sort Optional ORDER BY order (can be 'keyword', 'title', 'timestamp' or'clicks')
     * @param string $order Optional SORT order (can be 'ASC' or 'DESC')
     * @return array array of keywords
     */
    public function get_longurl_keywords( $longurl, $sort = 'none', $order = 'ASC' ) {
        global $ydb;
        $longurl = escape( sanitize_url( $longurl ) );
        $table   = YOURLS_DB_TABLE_URL;
        $query   = "SELECT `keyword` FROM `$table` WHERE `url` = '$longurl'";

        // Ensure sort is a column in database (@TODO: update verification array if database changes)
        if ( in_array( $sort, array('keyword','title','timestamp','clicks') ) ) {
            $query .= " ORDER BY '".$sort."'";
            if ( in_array( $order, array( 'ASC','DESC' ) ) ) {
                $query .= " ".$order;
            }
        }

        return apply_filter( 'get_longurl_keywords', $ydb->get_col( $query ), $longurl );
    }

    /**
     * Update a title link (no checks for duplicates etc..)
     *
     */
    public function edit_link_title( $keyword, $title ) {
        // Allow plugins to short-circuit the whole function
        $pre = apply_filter( 'shunt_edit_link_title', null, $keyword, $title );
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

        return apply_filter( 'get_link_stats', $return, $shorturl );
    }

    /**
     * Return title associated with keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_keyword_title( $notfound = false ) {
        return get_keyword_info( 'title', $notfound );
    }

    /**
     * Return long URL associated with keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_keyword_longurl( $notfound = false ) {
        return get_keyword_info( 'url', $notfound );
    }

    /**
     * Return number of clicks on a keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_keyword_clicks( $notfound = false ) {
        return get_keyword_info( 'clicks', $notfound );
    }

    /**
     * Return IP that added a keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_keyword_IP( $notfound = false ) {
        return get_keyword_info(  'ip', $notfound );
    }

    /**
     * Return timestamp associated with a keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_keyword_timestamp( $notfound = false ) {
        return get_keyword_info( 'timestamp', $notfound );
    }

}

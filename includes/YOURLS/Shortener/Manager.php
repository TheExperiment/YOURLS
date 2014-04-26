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
     * Check if an IP shortens URL too fast to prevent DB flood. Return true, or die.
     *
     */
    public function check_ip_flood( $ip = '' ) {

        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_check_IP_flood', false, $ip );
        if ( false !== $pre )
            return $pre;

        Filters::do_action( 'pre_check_ip_flood', $ip ); // at this point $ip can be '', check it if your plugin hooks in here

        // Raise white flag if installing or if no flood delay defined
        if(
            ( defined('YOURLS_FLOOD_DELAY_SECONDS') && YOURLS_FLOOD_DELAY_SECONDS === 0 ) ||
            !defined('YOURLS_FLOOD_DELAY_SECONDS') ||
            is_installing()
        )

            return true;

        // Don't throttle logged in users
        if( is_private() ) {
            if( is_valid_user() === true )

                return true;
        }

        // Don't throttle whitelist IPs
        if( defined( 'YOURLS_FLOOD_IP_WHITELIST' ) && YOURLS_FLOOD_IP_WHITELIST ) {
            $whitelist_ips = explode( ',', YOURLS_FLOOD_IP_WHITELIST );
            foreach( (array)$whitelist_ips as $whitelist_ip ) {
                $whitelist_ip = trim( $whitelist_ip );
                if ( $whitelist_ip == $ip )
                    return true;
            }
        }

        $ip = ( $ip ? sanitize_ip( $ip ) : get_IP() );
        $ip = escape( $ip );

        Filters::do_action( 'check_ip_flood', $ip );

        global $ydb;
        $table = YOURLS_DB_TABLE_URL;

        $lasttime = $ydb->get_var( "SELECT `timestamp` FROM $table WHERE `ip` = '$ip' ORDER BY `timestamp` DESC LIMIT 1" );
        if( $lasttime ) {
            $now = date( 'U' );
            $then = date( 'U', strtotime( $lasttime ) );
            if( ( $now - $then ) <= YOURLS_FLOOD_DELAY_SECONDS ) {
                // Flood!
                Filters::do_action( 'ip_flood', $ip, $now - $then );
                die( _( 'Too many URLs added too fast. Slow down please.' )/*, _( 'Forbidden' ), 403 */);
            }
        }

        return true;
    }

    /**
     * Update id for next link with no custom keyword
     *
     */
    public function update_next_decimal( $int = '' ) {
        $int = ( $int == '' ) ? (int)Options::$next_id + 1 : (int)$int ;
        Options::$next_id = $int;
        Filters::do_action( 'update_next_decimal', $int );
    }

    /**
     * SQL query to insert a new link in the DB. Returns boolean for success or failure of the inserting
     *
     */
    public function insert_link_in_db( $url, $keyword, $title = '' ) {
        global $ydb;

        $url     = escape( sanitize_url( $url ) );
        $keyword = escape( sanitize_keyword( $keyword ) );
        $title   = escape( sanitize_title( $title ) );

        $table = YOURLS_DB_TABLE_URL;
        $timestamp = date('Y-m-d H:i:s');
        $ip = get_IP();
        $insert = $ydb->query("INSERT INTO `$table` (`keyword`, `url`, `title`, `timestamp`, `ip`, `clicks`) VALUES('$keyword', '$url', '$title', '$timestamp', '$ip', 0);");

        Filters::do_action( 'insert_link', (bool)$insert, $url, $keyword, $title, $timestamp, $ip );

        return (bool)$insert;
    }

    /**
     * Return (string) selected information associated with a keyword. Optional $notfound = string default message if nothing found
     *
     */
    public function get_keyword_info( $keyword, $field, $notfound = false ) {

        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_get_keyword_info', false, $keyword, $field, $notfound );
        if ( false !== $pre )
            return $pre;

        $keyword = sanitize_string( $keyword );
        $infos = get_keyword_infos( $keyword );

        $return = $notfound;
        if ( isset( $infos[ $field ] ) && $infos[ $field ] !== false )
            $return = $infos[ $field ];

        return Filters::apply_filter( 'get_keyword_info', $return, $keyword, $field, $notfound );
    }

    /**
     * Update click count on a short URL. Return 0/1 for error/success.
     *
     */
    public function update_clicks( $keyword, $clicks = false ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_update_clicks', false, $keyword, $clicks );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $keyword = escape( sanitize_string( $keyword ) );
        $table = YOURLS_DB_TABLE_URL;
        if ( $clicks !== false && is_int( $clicks ) && $clicks >= 0 )
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = $clicks WHERE `keyword` = '$keyword'" );
        else
            $update = $ydb->query( "UPDATE `$table` SET `clicks` = clicks + 1 WHERE `keyword` = '$keyword'" );

        Filters::do_action( 'update_clicks', $keyword, $update, $clicks );

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

        return Filters::apply_filter( 'get_stats', $return, $filter, $limit, $start );
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

        return Filters::apply_filter( 'get_db_stats', $return, $where );
    }
    /**
     * Get number of SQL queries performed
     *
     */
    public function get_num_queries() {
        global $ydb;

        return Filters::apply_filter( 'get_num_queries', $ydb->num_queries );
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
        $pre = Filters::apply_filter( 'shunt_log_redirect', false, $keyword );
        if ( false !== $pre )
            return $pre;

        if ( !do_log_redirect() )
            return true;

        global $ydb;
        $table = YOURLS_DB_TABLE_LOG;

        $keyword  = escape( sanitize_string( $keyword ) );
        $referrer = ( isset( $_SERVER['HTTP_REFERER'] ) ? escape( sanitize_url( $_SERVER['HTTP_REFERER'] ) ) : 'direct' );
        $ua       = escape( get_user_agent() );
        $ip       = escape( get_ip() );
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

        return Filters::apply_filter( 'get_longurl_keywords', $ydb->get_col( $query ), $longurl );
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
    public function get_keyword_ip( $notfound = false ) {
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

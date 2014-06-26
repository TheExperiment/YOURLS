<?php

/**
 * Manager
 *
 * @since 2.0
 * @version 2.0.0-alpha
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
        $int = ( $int == '' ) ? (int)Options::get( 'next_id' ) + 1 : (int)$int ;
        Options::set( 'next_id', $int );
        Filters::do_action( 'update_next_decimal', $int );
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

}

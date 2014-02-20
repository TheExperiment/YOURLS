<?php

/**
 * API Wrapper
 *
 * @since 2.0
 * @copyright 2009-2014 YOURLS - MIT
 */

namespace YOURLS;

/**
 * API's Voice
 *
 * Note about translation : this file should NOT be translation ready
 * API messages and returns are supposed to be programmatically tested, so default English is expected
 */
class API {

    /**
     * API function wrapper: Shorten a URL
     *
     * @since 1.6
     * @return array Result of API call
     */
    public function api_action_shorturl() {
        $url = ( isset( $_REQUEST['url'] ) ? $_REQUEST['url'] : '' );
        $keyword = ( isset( $_REQUEST['keyword'] ) ? $_REQUEST['keyword'] : '' );
        $title = ( isset( $_REQUEST['title'] ) ? $_REQUEST['title'] : '' );
        $return = add_new_link( $url, $keyword, $title );
        $return['simple'] = ( isset( $return['shorturl'] ) ? $return['shorturl'] : '' ); // This one will be used in case output mode is 'simple'
        unset( $return['html'] ); // in API mode, no need for our internal HTML output

        return apply_filter( 'api_result_shorturl', $return );
    }

    /**
     * API function wrapper: Stats about links (XX top, bottom, last, rand)
     *
     * @since 1.6
     * @return array Result of API call
     */
    public function api_action_stats() {
        $filter = ( isset( $_REQUEST['filter'] ) ? $_REQUEST['filter'] : '' );
        $limit = ( isset( $_REQUEST['limit'] ) ? $_REQUEST['limit'] : '' );
        $start = ( isset( $_REQUEST['start'] ) ? $_REQUEST['start'] : '' );

        return apply_filter( 'api_result_stats', api_stats( $filter, $limit, $start ) );
    }

    /**
     * API function wrapper: Just the global counts of shorturls and clicks
     *
     * @since 1.6
     * @return array Result of API call
     */
    public function api_action_db_stats() {
        return apply_filter( 'api_result_db_stats', api_db_stats() );
    }

    /**
     * API function wrapper: Stats for a shorturl
     *
     * @since 1.6
     * @return array Result of API call
     */
    public function api_action_url_stats() {
        $shorturl = ( isset( $_REQUEST['shorturl'] ) ? $_REQUEST['shorturl'] : '' );

        return apply_filter( 'api_result_url_stats', api_url_stats( $shorturl ) );
    }

    /**
     * API function wrapper: Expand a short link
     *
     * @since 1.6
     * @return array Result of API call
     */
    public function api_action_expand() {
        $shorturl = ( isset( $_REQUEST['shorturl'] ) ? $_REQUEST['shorturl'] : '' );

        return apply_filter( 'api_result_expand', api_expand( $shorturl ) );
    }

    /**
     * API function wrapper: return version numbers
     *
     * @since 1.6
     * @return array Result of API call
     */
    public function api_action_version() {
        $return['version'] = $return['simple'] = VERSION;
        if( isset( $_REQUEST['db'] ) && $_REQUEST['db'] == 1 )
            $return['db_version'] = DB_VERSION;

        return apply_filter( 'api_result_version', $return );
    }

    /**
     * Return API result. Dies after this
     *
     */
    public function api_output( $mode, $return ) {
        if( isset( $return['simple'] ) ) {
            $simple = $return['simple'];
            unset( $return['simple'] );
        }

        do_action( 'pre_api_output', $mode, $return );

        if( isset( $return['statusCode'] ) ) {
            $code = $return['statusCode'];
        } elseif ( isset( $return['errorCode'] ) ) {
            $code = $return['errorCode'];
        } else {
            $code = 200;
        }
        status_header( $code );

        switch ( $mode ) {
            case 'jsonp':
                content_type_header( 'application/javascript' );
                echo $return['callback'] . '(' . json_encode( $return ) . ')';
                break;

            case 'json':
                content_type_header( 'application/json' );
                echo json_encode( $return );
                break;

            case 'xml':
                content_type_header( 'application/xml' );
                echo xml_encode( $return );
                break;

            case 'simple':
            default:
                content_type_header( 'text/plain' );
                if( isset( $simple ) )
                    echo $simple;
                break;
        }

        do_action( 'api_output', $mode, $return );

        die();
    }

    /**
     * Return array for API stat requests
     *
     */
    public function api_stats( $filter = 'top', $limit = 10, $start = 0 ) {
        $return = get_stats( $filter, $limit, $start );
        $return['simple']  = 'Need either XML or JSON format for stats';
        $return['message'] = 'success';

        return apply_filter( 'api_stats', $return, $filter, $limit, $start );
    }

    /**
     * Return array for counts of shorturls and clicks
     *
     */
    public function api_db_stats() {
        $return = array(
            'db-stats'   => get_db_stats(),
            'statusCode' => 200,
            'simple'     => 'Need either XML or JSON format for stats',
            'message'    => 'success',
        );

        return apply_filter( 'api_db_stats', $return );
    }

    /**
     * Return array for API stat requests
     *
     */
    public function api_url_stats( $shorturl ) {
        $keyword = str_replace( SITE . '/' , '', $shorturl ); // accept either 'http://ozh.in/abc' or 'abc'
        $keyword = sanitize_string( $keyword );

        $return = get_link_stats( $keyword );
        $return['simple']  = 'Need either XML or JSON format for stats';

        return apply_filter( 'api_url_stats', $return, $shorturl );
    }

    /**
     * Expand short url to long url
     *
     */
    public function api_expand( $shorturl ) {
        $keyword = str_replace( SITE . '/' , '', $shorturl ); // accept either 'http://ozh.in/abc' or 'abc'
        $keyword = sanitize_string( $keyword );

        $longurl = get_keyword_longurl( $keyword );

        if( $longurl ) {
            $return = array(
                'keyword'   => $keyword,
                'shorturl'  => SITE . "/$keyword",
                'longurl'   => $longurl,
                'simple'    => $longurl,
                'message'   => 'success',
                'statusCode' => 200,
            );
        } else {
            $return = array(
                'keyword'   => $keyword,
                'simple'    => 'not found',
                'message'   => 'Error: short URL not found',
                'errorCode' => 404,
            );
        }

        return apply_filter( 'api_expand', $return, $shorturl );
    }

}

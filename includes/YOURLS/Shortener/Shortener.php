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

class Shortener {

    const CHARSET_32 = '0123456789abcdefghijklmnopqrstuvwxyz';
    const CHARSET_62 = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Determine the allowed character set in short URLs
     *
     */
    public static function get_charset() {
        static $charset = null;
        if( $charset !== null )

            return $charset;

        if( defined( 'URL_CONVERT' ) && in_array( URL_CONVERT, array( 62, 64 ) ) ) {
            $charset = CHARSET_62;
        } else {
            // defined to 36, or wrongly defined
            $charset = CHARSET_32;
        }

        $charset = Filters::apply_filter( 'get_shorturl_charset', $charset );

        return $charset;
    }

    /**
     * Check to see if a given keyword is reserved (ie reserved URL or an existing page). Returns bool
     *
     */
    public function keyword_is_reserved( $keyword ) {
        global $reserved_URL;
        $keyword = sanitize_keyword( $keyword );
        $reserved = false;

        if ( in_array( $keyword, $reserved_URL)
            or file_exists( YOURLS_PAGEDIR ."/$keyword.php" )
            or is_dir( YOURLS_ABSPATH ."/$keyword" )
            or ( substr( $keyword, 0, strlen( YOURLS_ADMIN_LOCATION ) + 1 ) === YOURLS_ADMIN_LOCATION."/" )
        )
            $reserved = true;

        return Filters::apply_filter( 'keyword_is_reserved', $reserved, $keyword );
    }

    /**
     * Get next id a new link will have if no custom keyword provided
     *
     */
    public function get_next_decimal() {
        return Filters::apply_filter( 'get_next_decimal', (int)Options::$next_id );
    }

    /**
     * Update id for next link with no custom keyword
     *
     */
    public function update_next_decimal( $int = '' ) {
        $int = ( $int == '' ) ? get_next_decimal() + 1 : (int)$int ;
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
     * Check if a URL already exists in the DB. Return NULL (doesn't exist) or an object with URL informations.
     *
     */
    public function url_exists( $url ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_url_exists', false, $url );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $table = YOURLS_DB_TABLE_URL;
        $url   = escape( sanitize_url( $url) );
        $url_exists = $ydb->get_row( "SELECT * FROM `$table` WHERE `url` = '".$url."';" );

        return Filters::apply_filter( 'url_exists', $url_exists, $url );
    }

    /**
     * Add a new link in the DB, either with custom keyword, or find one
     *
     */
    public function add_new_link( $url, $keyword = '', $title = '' ) {
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
     * Check if keyword id is free (ie not already taken, and not reserved). Return bool.
     *
     */
    public function keyword_is_free( $keyword ) {
        $free = true;
        if ( keyword_is_reserved( $keyword ) or keyword_is_taken( $keyword ) )
            $free = false;

        return Filters::apply_filter( 'keyword_is_free', $free, $keyword );
    }

    /**
     * Check if a keyword is taken (ie there is already a short URL with this id). Return bool.
     *
     */
    public function keyword_is_taken( $keyword ) {

        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_keyword_is_taken', false, $keyword );
        if ( false !== $pre )
            return $pre;

        global $ydb;
        $keyword = escape( sanitize_keyword( $keyword ) );
        $taken = false;
        $table = YOURLS_DB_TABLE_URL;
        $already_exists = $ydb->get_var( "SELECT COUNT(`keyword`) FROM `$table` WHERE `keyword` = '$keyword';" );
        if ( $already_exists )
            $taken = true;

        return Filters::apply_filter( 'keyword_is_taken', $taken, $keyword );
    }

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
     * Get a remote page title
     *
     * This function returns a string: either the page title as defined in HTML, or the URL if not found
     * The function tries to convert funky characters found in titles to UTF8, from the detected charset.
     * Charset in use is guessed from HTML meta tag, or if not found, from server's 'content-type' response.
     *
     * @param string $url URL
     * @return string Title (sanitized) or the URL if no title found
     */
    public function get_remote_title( $url ) {
        // Allow plugins to short-circuit the whole function
        $pre = Filters::apply_filter( 'shunt_get_remote_title', false, $url );
        if ( false !== $pre )
            return $pre;

        $url = sanitize_url( $url );

        // Only deal with http(s)://
        if( !in_array( get_protocol( $url ), array( 'http://', 'https://' ) ) )

            return $url;

        $title = $charset = false;

        $response = http_get( $url ); // can be a Request object or an error string
        if( is_string( $response ) ) {
            return $url;
        }

        // Page content. No content? Return the URL
        $content = $response->body;
        if( !$content )

            return $url;

        // look for <title>. No title found? Return the URL
        if ( preg_match('/<title>(.*?)<\/title>/is', $content, $found ) ) {
            $title = $found[1];
            unset( $found );
        }
        if( !$title )

            return $url;

        // Now we have a title. We'll try to get proper utf8 from it.

        // Get charset as (and if) defined by the HTML meta tag. We should match
        // <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        // or <meta charset='utf-8'> and all possible variations: see https://gist.github.com/ozh/7951236
        if ( preg_match( '/<meta[^>]*charset\s*=["\' ]*([a-zA-Z0-9\-_]+)/is', $content, $found ) ) {
            $charset = $found[1];
            unset( $found );
        } else {
            // No charset found in HTML. Get charset as (and if) defined by the server response
            $_charset = current( $response->headers->getValues( 'content-type' ) );
            if( preg_match( '/charset=(\S+)/', $_charset, $found ) ) {
                $charset = trim( $found[1], ';' );
                unset( $found );
            }
        }

        // Conversion to utf-8 if what we have is not utf8 already
        if( strtolower( $charset ) != 'utf-8' && function_exists( 'mb_convert_encoding' ) ) {
            // We use @ to remove warnings because mb_ functions are easily bitching about illegal chars
            if( $charset ) {
                $title = @mb_convert_encoding( $title, 'UTF-8', $charset );
            } else {
                $title = @mb_convert_encoding( $title, 'UTF-8' );
            }
        }

        // Remove HTML entities
        $title = html_entity_decode( $title, ENT_QUOTES, 'UTF-8' );

        // Strip out evil things
        $title = sanitize_title( $title );

        return Filters::apply_filter( 'get_remote_title', $title, $url );
    }

}

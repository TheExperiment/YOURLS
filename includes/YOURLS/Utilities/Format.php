<?php

/**
 * Format Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Utilities;

use YOURLS\Extensions\Filters;
/**
 * Useful methods to convert anything
 */
class Format {

    /**
     * Convert an integer (1337) to a string (3jk).
     *
     */
    public static function int2string( $num, $chars = null ) {
        if( $chars == null )
            $chars = get_shorturl_charset();
        $string = '';
        $len = strlen( $chars );
        while( $num >= $len ) {
            $mod = bcmod( $num, $len );
            $num = bcdiv( $num, $len );
            $string = $chars[ $mod ] . $string;
        }
        $string = $chars[ intval( $num ) ] . $string;

        return Filters::apply_filter( 'int2string', $string, $num, $chars );
    }

    /**
     * Convert a string (3jk) to an integer (1337)
     *
     */
    public static function string2int( $string, $chars = null ) {
        if( $chars == null )
            $chars = get_shorturl_charset();
        $integer = 0;
        $string = strrev( $string  );
        $baselen = strlen( $chars );
        $inputlen = strlen( $string );
        for ($i = 0; $i < $inputlen; $i++) {
            $index = strpos( $chars, $string[$i] );
            $integer = bcadd( $integer, bcmul( $index, bcpow( $baselen, $i ) ) );
        }

        return Filters::apply_filter( 'string2int', $integer, $string, $chars );
    }

    /**
     * Return a unique(ish) hash for a string to be used as a valid HTML id
     *
     */
    public static function string2htmlid( $string ) {
        return Filters::apply_filter( 'string2htmlid', 'y'.abs( crc32( $string ) ) );
    }

    /**
     * Make sure a link keyword (ie "1fv" as in "site.com/1fv") is valid.
     *
     */
    public static function sanitize_string( $string ) {
        // make a regexp pattern with the shorturl charset, and remove everything but this
        $pattern = self::make_regexp_pattern( get_shorturl_charset() );
        $valid = substr( preg_replace( '![^'.$pattern.']!', '', $string ), 0, 199 );

        return Filters::apply_filter( 'sanitize_string', $valid, $string );
    }

    /**
     * Sanitize a page title. No HTML per W3C http://www.w3.org/TR/html401/struct/global.html#h-7.4.2
     *
     */
    public static function sanitize_title( $unsafe_title ) {
        $title = $unsafe_title;
        $title = strip_tags( $title );
        $title = preg_replace( "/\s+/", ' ', trim( $title ) );

        return Filters::apply_filter( 'sanitize_title', $title, $unsafe_title );
    }

    /**
     * A few sanity checks on the URL. Used for redirection or DB. For display purpose, see esc_url()
     *
     * @param string $unsafe_url unsafe URL
     * @param array $protocols Optional allowed protocols, default to global $allowedprotocols
     * @return string Safe URL
     */
    public static function sanitize_url( $unsafe_url, $protocols = array() ) {
        $url = $this->esc_url( $unsafe_url, 'redirection', $protocols );

        return Filters::apply_filter( 'sanitize_url', $url, $unsafe_url );
    }

    /**
     * Perform a replacement while a string is found, eg $subject = '%0%0%0DDD', $search ='%0D' -> $result =''
     *
     * Stolen from WP's _deep_replace
     *
     */
    public static function deep_replace( $search, $subject ){
        $found = true;
        while($found) {
            $found = false;
            foreach( (array) $search as $val ) {
                while( strpos( $subject, $val ) !== false ) {
                    $found = true;
                    $subject = str_replace( $val, '', $subject );
                }
            }
        }

        return $subject;
    }

    /**
     * Make sure an integer is a valid integer (PHP's intval() limits to too small numbers)
     *
     */
    public static function sanitize_int( $in ) {
        return ( substr( preg_replace( '/[^0-9]/', '', strval( $in ) ), 0, 20 ) );
    }

    /**
     * Escape a string or an array of strings before DB usage. ALWAYS escape before using in a SQL query. Thanks.
     *
     * @param string|array $data string or array of strings to be escaped
     * @return string|array escaped data
     */
    public static function escape( $data ) {
        if( is_array( $data ) ) {
            foreach( $data as $k => $v ) {
                if( is_array( $v ) ) {
                    $data[ $k ] = $this->escape( $v );
                } else {
                    $data[ $k ] = $this->escape_real( $v );
                }
            }
        } else {
            $data = $this->escape_real( $data );
        }

        return $data;
    }

    /**
     * "Real" escape. This function should NOT be called directly. Use escape() instead.
     *
     * This function uses a "real" escape if possible, using PDO, MySQL or MySQLi functions,
     * with a fallback to a "simple" addslashes
     * If you're implementing a custom DB engine or a custom cache system, you can define an
     * escape function using filter 'custom_escape_real'
     *
     * @since 1.7
     * @param string $a string to be escaped
     * @return string escaped string
     */
    public static function escape_real( $string ) {
        global $ydb;
        if( isset( $ydb ) && ( $ydb instanceof ezSQLcore ) )

            return $ydb->escape( $string );

        // YOURLS DB classes have been bypassed by a custom DB engine or a custom cache layer
        return Filters::apply_filters( 'custom_escape_real', addslashes( $string ), $string );
    }

    /**
     * Sanitize an IP address
     *
     */
    public static function sanitize_ip( $ip ) {
        return preg_replace( '/[^0-9a-fA-F:., ]/', '', $ip );
    }

    /**
     * Make sure a date is m(m)/d(d)/yyyy, return false otherwise
     *
     */
    public static function sanitize_date( $date ) {
        if( !preg_match( '!^\d{1,2}/\d{1,2}/\d{4}$!' , $date ) ) {
            return false;
        }

        return $date;
    }

    /**
     * Sanitize a date for SQL search. Return false if malformed input.
     *
     */
    public static function sanitize_date_for_sql( $date ) {
        if( !$this->sanitize_date( $date ) )

            return false;
        return date( 'Y-m-d', strtotime( $date ) );
    }

    /**
     * Return trimmed string
     *
     */
    public static function trim_long_string( $string, $length = 60, $append = '[...]' ) {
        $newstring = $string;
        if( function_exists( 'mb_substr' ) ) {
            if ( mb_strlen( $newstring ) > $length ) {
                $newstring = mb_substr( $newstring, 0, $length - mb_strlen( $append ), 'UTF-8' ) . $append;
            }
        } else {
            if ( strlen( $newstring ) > $length ) {
                $newstring = substr( $newstring, 0, $length - strlen( $append ) ) . $append;
            }
        }

        return Filters::apply_filter( 'trim_long_string', $newstring, $string, $length, $append );
    }

    /**
     * Sanitize a version number (1.4.1-whatever -> 1.4.1)
     *
     */
    public static function sanitize_version( $ver ) {
        return preg_replace( '/[^0-9.]/', '', $ver );
    }

    /**
     * Sanitize a filename (no Win32 stuff)
     *
     */
    public static function sanitize_filename( $file ) {
        $file = str_replace( '\\', '/', $file ); // sanitize for Win32 installs
        $file = preg_replace( '|/+|' ,'/', $file ); // remove any duplicate slash

        return $file;
    }

    /**
     * Check if a string seems to be UTF-8. Stolen from WP.
     *
     */
    public static function seems_utf8( $str ) {
        $length = strlen( $str );
        for ( $i=0; $i < $length; $i++ ) {
            $c = ord( $str[ $i ] );
            if ( $c < 0x80 ) $n = 0; # 0bbbbbbb
            elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
            elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
            elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
            elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
            elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
            else return false; # Does not match any model
            for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                    return false;
            }
        }

        return true;
    }

    /**
     * Checks for invalid UTF8 in a string. Stolen from WP
     *
     * @since 1.6
     *
     * @param string $string The text which is to be checked.
     * @param boolean $strip Optional. Whether to attempt to strip out invalid UTF8. Default is false.
     * @return string The checked text.
     */
    public static function check_invalid_utf8( $string, $strip = false ) {
        $string = (string) $string;

        if ( 0 === strlen( $string ) ) {
            return '';
        }

        // Check for support for utf8 in the installed PCRE library once and store the result in a static
        static $utf8_pcre;
        if ( !isset( $utf8_pcre ) ) {
            $utf8_pcre = @preg_match( '/^./u', 'a' );
        }
        // We can't demand utf8 in the PCRE installation, so just return the string in those cases
        if ( !$utf8_pcre ) {
            return $string;
        }

        // preg_match fails when it encounters invalid UTF8 in $string
        if ( 1 === @preg_match( '/^./us', $string ) ) {
            return $string;
        }

        // Attempt to strip the bad chars if requested (not recommended)
        if ( $strip && function_exists( 'iconv' ) ) {
            return iconv( 'utf-8', 'utf-8', $string );
        }

        return '';
    }

    /**
     * Converts a number of special characters into their HTML entities. Stolen from WP.
     *
     * Specifically deals with: &, <, >, ", and '.
     *
     * $quote_style can be set to ENT_COMPAT to encode " to
     * &quot;, or ENT_QUOTES to do both. Default is ENT_NOQUOTES where no quotes are encoded.
     *
     * @since 1.6
     *
     * @param string $string The text which is to be encoded.
     * @param mixed $quote_style Optional. Converts double quotes if set to ENT_COMPAT, both single and double if set to ENT_QUOTES or none if set to ENT_NOQUOTES. Also compatible with old values; converting single quotes if set to 'single', double if set to 'double' or both if otherwise set. Default is ENT_NOQUOTES.
     * @param boolean $double_encode Optional. Whether to encode existing html entities. Default is false.
     * @return string The encoded text with HTML entities.
     */
    public static function specialchars( $string, $quote_style = ENT_NOQUOTES, $double_encode = false ) {
        $string = (string) $string;

        if ( 0 === strlen( $string ) )
            return '';

        // Don't bother if there are no specialchars - saves some processing
        if ( ! preg_match( '/[&<>"\']/', $string ) )
            return $string;

        // Account for the previous behaviour of the function when the $quote_style is not an accepted value
        if ( empty( $quote_style ) )
            $quote_style = ENT_NOQUOTES;
        elseif ( ! in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) )
            $quote_style = ENT_QUOTES;

        $charset = 'UTF-8';

        $_quote_style = $quote_style;

        if ( $quote_style === 'double' ) {
            $quote_style = ENT_COMPAT;
            $_quote_style = ENT_COMPAT;
        } elseif ( $quote_style === 'single' ) {
            $quote_style = ENT_NOQUOTES;
        }

        // Handle double encoding ourselves
        if ( $double_encode ) {
            $string = @$this->htmlspecialchars( $string, $quote_style, $charset );
        } else {
            // Decode &amp; into &
            $string = $this->specialchars_decode( $string, $_quote_style );

            $kses = new KSES();
            // Guarantee every &entity; is valid or re-encode the &
            $string = $kses->normalize_entities( $string );

            // Now re-encode everything except &entity;
            $string = preg_split( '/(&#?x?[0-9a-z]+;)/i', $string, -1, PREG_SPLIT_DELIM_CAPTURE );

            for ( $i = 0; $i < count( $string ); $i += 2 )
                $string[$i] = @$this->htmlspecialchars( $string[$i], $quote_style, $charset );

            $string = implode( '', $string );
        }

        // Backwards compatibility
        if ( 'single' === $_quote_style )
            $string = str_replace( "'", '&#039;', $string );

        return $string;
    }

    /**
     * Converts a number of HTML entities into their special characters. Stolen from WP.
     *
     * Specifically deals with: &, <, >, ", and '.
     *
     * $quote_style can be set to ENT_COMPAT to decode " entities,
     * or ENT_QUOTES to do both " and '. Default is ENT_NOQUOTES where no quotes are decoded.
     *
     * @since 1.6
     *
     * @param string $string The text which is to be decoded.
     * @param mixed $quote_style Optional. Converts double quotes if set to ENT_COMPAT, both single and double if set to ENT_QUOTES or none if set to ENT_NOQUOTES. Also compatible with old _wp_specialchars() values; converting single quotes if set to 'single', double if set to 'double' or both if otherwise set. Default is ENT_NOQUOTES.
     * @return string The decoded text without HTML entities.
     */
    public static function specialchars_decode( $string, $quote_style = ENT_NOQUOTES ) {
        $string = (string) $string;

        if ( 0 === strlen( $string ) ) {
            return '';
        }

        // Don't bother if there are no entities - saves a lot of processing
        if ( strpos( $string, '&' ) === false ) {
            return $string;
        }

        // Match the previous behaviour of _wp_specialchars() when the $quote_style is not an accepted value
        if ( empty( $quote_style ) ) {
            $quote_style = ENT_NOQUOTES;
        } elseif ( !in_array( $quote_style, array( 0, 2, 3, 'single', 'double' ), true ) ) {
            $quote_style = ENT_QUOTES;
        }

        // More complete than get_html_translation_table( HTML_SPECIALCHARS )
        $single = array( '&#039;'  => '\'', '&#x27;' => '\'' );
        $single_preg = array( '/&#0*39;/'  => '&#039;', '/&#x0*27;/i' => '&#x27;' );
        $double = array( '&quot;' => '"', '&#034;'  => '"', '&#x22;' => '"' );
        $double_preg = array( '/&#0*34;/'  => '&#034;', '/&#x0*22;/i' => '&#x22;' );
        $others = array( '&lt;'   => '<', '&#060;'  => '<', '&gt;'   => '>', '&#062;'  => '>', '&amp;'  => '&', '&#038;'  => '&', '&#x26;' => '&' );
        $others_preg = array( '/&#0*60;/'  => '&#060;', '/&#0*62;/'  => '&#062;', '/&#0*38;/'  => '&#038;', '/&#x0*26;/i' => '&#x26;' );

        if ( $quote_style === ENT_QUOTES ) {
            $translation = array_merge( $single, $double, $others );
            $translation_preg = array_merge( $single_preg, $double_preg, $others_preg );
        } elseif ( $quote_style === ENT_COMPAT || $quote_style === 'double' ) {
            $translation = array_merge( $double, $others );
            $translation_preg = array_merge( $double_preg, $others_preg );
        } elseif ( $quote_style === 'single' ) {
            $translation = array_merge( $single, $others );
            $translation_preg = array_merge( $single_preg, $others_preg );
        } elseif ( $quote_style === ENT_NOQUOTES ) {
            $translation = $others;
            $translation_preg = $others_preg;
        }

        // Remove zero padding on numeric entities
        $string = preg_replace( array_keys( $translation_preg ), array_values( $translation_preg ), $string );

        // Replace characters according to translation table
        return strtr( $string, $translation );
    }

    /**
     * Escaping for HTML blocks. Stolen from WP
     *
     * @since 1.6
     *
     * @param string $text
     * @return string
     */
    public static function esc_html( $text ) {
        $safe_text = $this->check_invalid_utf8( $text );
        $safe_text = $this->specialchars( $safe_text, ENT_QUOTES );

        return Filters::apply_filters( 'esc_html', $safe_text, $text );
    }

    /**
     * Escaping for HTML attributes.  Stolen from WP
     *
     * @since 1.6
     *
     * @param string $text
     * @return string
     */
    public static function esc_attr( $text ) {
        $safe_text = $this->check_invalid_utf8( $text );
        $safe_text = $this->specialchars( $safe_text, ENT_QUOTES );

        return Filters::apply_filters( 'esc_attr', $safe_text, $text );
    }

    /**
     * Checks and cleans a URL before printing it. Stolen from WP.
     *
     * A number of characters are removed from the URL. If the URL is for displaying
     * (the default behaviour) ampersands are also replaced.
     *
     * @since 1.6
     *
     * @param string $url The URL to be cleaned.
     * @param string $context 'display' or something else. Use sanitize_url() for database or redirection usage.
     * @param array $protocols Optional. Array of allowed protocols, defaults to global $allowedprotocols
     * @return string The cleaned $url
     */
    public static function esc_url( $url, $context = 'display', $protocols = array() ) {
        // make sure there's only one 'http://' at the beginning (prevents pasting a URL right after the default 'http://')
        $url = str_replace(
            array( 'http://http://', 'http://https://' ),
            array( 'http://',        'https://'        ),
            $url
        );

        if ( '' == $url )
            return $url;

        // make sure there's a protocol, add http:// if not
        if ( ! $http->get_protocol( $url ) )
            $url = 'http://'.$url;

        $original_url = $url;

        // force scheme and domain to lowercase - see issues 591 and 1630
        // We're not using parse_url() here because its opposite, http_build_url(), requires PECL. Plus, who doesn't love a neat Regexp? :)
        if( preg_match( '!^([a-zA-Z0-9\+\.-]+:)(//)?(.*?@)?([^/#?]+)(.*)$!', $url, $matches ) ) {
            list( $all, $scheme, $slashes, $userinfo, $domain, $rest ) = $matches;
            $scheme = strtolower( $scheme );
            // Domain to lowercase. On URN eg "urn:example:animal:ferret:nose" don't lowercase anything else
            if( $slashes == '//' )
                $domain = strtolower( $domain );
            $url = $scheme . $slashes . $userinfo . $domain . $rest;
        }

        $url = preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url );
        // Previous regexp in YOURLS was '|[^a-z0-9-~+_.?\[\]\^#=!&;,/:%@$\|*`\'<>"()\\x80-\\xff\{\}]|i'
        // TODO: check if that was it too destructive
        $strip = array( '%0d', '%0a', '%0D', '%0A' );
        $url = $this->deep_replace( $strip, $url );
        $url = str_replace( ';//', '://', $url );

        // Replace ampersands and single quotes only when displaying.
        if ( 'display' == $context ) {
            $kses = new KSES();
            $url = $kses->normalize_entities( $url );
            $url = str_replace( '&amp;', '&#038;', $url );
            $url = str_replace( "'", '&#039;', $url );
        }

        if ( ! is_array( $protocols ) or ! $protocols ) {
            global $allowedprotocols;
            $protocols = Filters::apply_filter( 'esc_url_protocols', $allowedprotocols );
            // Note: $allowedprotocols is also globally filterable in functions-kses.php/kses_init()
        }

        if ( !$http->is_allowed_protocol( $url, $protocols ) )
            return '';

        // I didn't use KSES function kses_bad_protocol() because it doesn't work the way I liked (returns //blah from illegal://blah)

        $url = substr( $url, 0, 1999 );

        return Filters::apply_filter( 'esc_url', $url, $original_url, $context );
    }

    /**
     * Escape single quotes, htmlspecialchar " < > &, and fix line endings. Stolen from WP.
     *
     * Escapes text strings for echoing in JS. It is intended to be used for inline JS
     * (in a tag attribute, for example onclick="..."). Note that the strings have to
     * be in single quotes. The filter 'js_escape' is also applied here.
     *
     * @since 1.6
     *
     * @param string $text The text to be escaped.
     * @return string Escaped text.
     */
    public static function esc_js( $text ) {
        $safe_text = $this->check_invalid_utf8( $text );
        $safe_text = $this->specialchars( $safe_text, ENT_COMPAT );
        $safe_text = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes( $safe_text ) );
        $safe_text = str_replace( "\r", '', $safe_text );
        $safe_text = str_replace( "\n", '\\n', addslashes( $safe_text ) );

        return Filters::apply_filters( 'esc_js', $safe_text, $text );
    }

    /**
     * Escaping for textarea values. Stolen from WP.
     *
     * @since 1.6
     *
     * @param string $text
     * @return string
     */
    public static function esc_textarea( $text ) {
        $safe_text = $this->htmlspecialchars( $text, ENT_QUOTES );

        return Filters::apply_filters( 'esc_textarea', $safe_text, $text );
    }


    /**
     * PHP emulation of JS's encodeURI
     *
     * @link https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/encodeURI
     * @param $url
     * @return string
     */
    public static function encodeURI( $url ) {
        // Decode URL all the way
        $result = $this->rawurldecode_while_encoded( $url );
        // Encode once
        $result = strtr( rawurlencode( $result ), array (
            '%3B' => ';', '%2C' => ',', '%2F' => '/', '%3F' => '?', '%3A' => ':', '%40' => '@',
            '%26' => '&', '%3D' => '=', '%2B' => '+', '%24' => '$', '%21' => '!', '%2A' => '*',
            '%27' => '\'', '%28' => '(', '%29' => ')', '%23' => '#',
        ) );
        // @TODO:
        // Known limit: this will most likely break IDN URLs such as http://www.acad�mie-fran�aise.fr/
        // To fully support IDN URLs, advocate use of a plugin.
        return Filters::apply_filter( 'encodeURI', $result, $url );
    }

    /**
     * Adds backslashes before letters and before a number at the start of a string. Stolen from WP.
     *
     * @since 1.6
     *
     * @param string $string Value to which backslashes will be added.
     * @return string String with backslashes inserted.
     */
    public static function backslashit( $string ) {
        $string = preg_replace( '/^([0-9])/', '\\\\\\\\\1', $string) ;
        $string = preg_replace( '/([a-z])/i', '\\\\\1', $string );

        return $string;
    }

    /**
     * Check if a string seems to be urlencoded
     *
     * We use rawurlencode instead of urlencode to avoid messing with '+'
     *
     * @since 1.7
     * @param string $string
     * @return bool
     */
    public static function is_rawurlencoded( $string ) {
        return rawurldecode( $string ) != $string;
    }

    /**
     * rawurldecode a string till it's not encoded anymore
     *
     * Deals with multiple encoding (eg "%2521" => "%21" => "!").
     * See https://github.com/YOURLS/YOURLS/issues/1303
     *
     * @since 1.7
     * @param string $string
     * @return string
     */
    public static function rawurldecode_while_encoded( $string ) {
        $string = rawurldecode( $string );
        if( $this->is_rawurlencoded( $string ) ) {
            $string = $this->rawurldecode_while_encoded( $string );
        }

        return $string;
    }
    /**
     * Sort of sprintf() where '%stuff%' is replaced with $array['stuff']
     *
     * Pass this function two arguments:
     *  - a format string with tokens: "hello %hello% my name is %name%"
     *  - an associative array: array( 'name' => 'Ozh', 'hello' => 'World', 'unused' => 'whatever' )
     * The function will then replace every %token% with $array['token']
     *
     * The idea is to avoid using sprintf like this:
     * $str = sprintf( 'hello %s my %s %s is %s and %s is %s', $who, $attr, $name, $blah, $where_am_i, $which_one_is_it, etc...)
     *
     * @TODO:
     * - find a better name for this function? Not entirely pleased with it at the moment
     * - add an option to convert return value to a one liner? (for ajax responses?)
     *
     * @since 1.7
     * @param string $format   format string with %tokens%
     * @param array $tokens    array of 'tokens' => 'values'
     * @param array $default   optional, defaults to '', string to replace unfound tokens
     * @return string          the formatted string
     */
    public static function replace_string_tokens( $format, array $tokens, $default = '' ) {
        preg_match_all( '/%([a-zA-Z0-9-_]+)%/', $format, $matches );

        foreach( (array)$matches[1] as $token ) {
            if( !isset( $tokens[ $token ] ) ) {
                $tokens[ $token ] = $default;
            }
            $format = str_replace( "%$token%", $tokens[ $token ], $format );
        }

        return $format;
    }

    /**
     * Generate random string of (int)$length length and type $type (see function for details)
     *
     */
    public static function rnd_string( $length = 5, $type = 0, $charlist = '' ) {
        $str = '';
        $length = intval( $length );

        // define possible characters
        switch ( $type ) {

            // custom char list, or comply to charset as defined in config
            case '0':
                $possible = $charlist ? $charlist : get_shorturl_charset() ;
                break;

            // no vowels to make no offending word, no 0/1/o/l to avoid confusion between letters & digits. Perfect for passwords.
            case '1':
                $possible = "23456789bcdfghjkmnpqrstvwxyz";
                break;

            // Same, with lower + upper
            case '2':
                $possible = "23456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ";
                break;

            // all letters, lowercase
            case '3':
                $possible = "abcdefghijklmnopqrstuvwxyz";
                break;

            // all letters, lowercase + uppercase
            case '4':
                $possible = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
                break;

            // all digits & letters lowercase
            case '5':
                $possible = "0123456789abcdefghijklmnopqrstuvwxyz";
                break;

            // all digits & letters lowercase + uppercase
            case '6':
                $possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
                break;

        }

        $str = substr( str_shuffle( $possible ), 0, $length );

        return Filters::apply_filter( 'rnd_string', $str, $length, $type, $charlist );
    }


    /**
     * Navigates through an array and encodes the values to be used in a URL. Stolen from WP, used in add_query_arg()
     *
     */
    public static function urlencode_deep( $value ) {
        $value = is_array( $value ) ? array_map( 'urlencode_deep', $value ) : urlencode( $value );

        return $value;
    }

    /**
     * Serialize data if needed. Stolen from WordPress
     *
     * @since 1.4
     * @param mixed $data Data that might be serialized.
     * @return mixed A scalar data
     */
    public static function maybe_serialize( $data ) {
        if ( is_array( $data ) || is_object( $data ) )
            return serialize( $data );

        if ( is_serialized( $data, false ) )
            return serialize( $data );

        return $data;
    }

    /**
     * Check value to find if it was serialized. Stolen from WordPress
     *
     * @since 1.4
     * @param mixed $data Value to check to see if was serialized.
     * @param bool $strict Optional. Whether to be strict about the end of the string. Defaults true.
     * @return bool False if not serialized and true if it was.
     */
    public static function is_serialized( $data, $strict = true ) {
        // if it isn't a string, it isn't serialized
        if ( ! is_string( $data ) )
            return false;
        $data = trim( $data );
        if ( 'N;' == $data )
            return true;
        $length = strlen( $data );
        if ( $length < 4 )
            return false;
        if ( ':' !== $data[1] )
            return false;
        if ( $strict ) {
            $lastc = $data[ $length - 1 ];
            if ( ';' !== $lastc && '}' !== $lastc )
                return false;
        } else {
            $semicolon = strpos( $data, ';' );
            $brace	 = strpos( $data, '}' );
            // Either ; or } must exist.
            if ( false === $semicolon && false === $brace )
                return false;
            // But neither must be in the first X characters.
            if ( false !== $semicolon && $semicolon < 3 )
                return false;
            if ( false !== $brace && $brace < 4 )
                return false;
        }
        $token = $data[0];
        switch ( $token ) {
            case 's' :
                if ( $strict ) {
                    if ( '"' !== $data[ $length - 2 ] )
                        return false;
                } elseif ( false === strpos( $data, '"' ) ) {
                    return false;
                }
            // or else fall through
            case 'a' :
            case 'O' :
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b' :
            case 'i' :
            case 'd' :
                $end = $strict ? '$' : '';

                return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
        }

        return false;
    }

    /**
     * Unserialize value only if it was serialized. Stolen from WP
     *
     * @since 1.4
     * @param string $original Maybe unserialized original, if is needed.
     * @return mixed Unserialized data can be any type.
     */
    public static function maybe_unserialize( $original ) {
        if ( is_serialized( $original ) ) // don't attempt to unserialize data that wasn't serialized going in
            return @unserialize( $original );
        return $original;
    }

    /**
     * Make an optimized regexp pattern from a string of characters
     *
     */
    public static function make_regexp_pattern( $string ) {
        $pattern = preg_quote( $string );
        // TODO: replace char sequences by smart sequences such as 0-9, a-z, A-Z ... ?
        return $pattern;
    }

    /**
     * Return salted string
     *
     */
    public static function salt( $string ) {
        $salt = defined( 'YOURLS_COOKIEKEY' ) ? YOURLS_COOKIEKEY : md5( __FILE__ ) ;

        return Filters::apply_filter( 'salt', md5( $string . $salt ), $string );
    }
}

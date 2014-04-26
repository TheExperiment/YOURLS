<?php

/**
 * Headers Manager
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\HTTP;

use Requests\Exception\HTTP;

class Header {

    protected static $codes = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        226 => 'IM Used',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        510 => 'Not Extended',
        511 => 'Network Authentication Required'
    );

    /**
     * Set HTTP header
     *
     */
    public function __construct( $name, $value ) {
        if( !headers_sent() ) {
            $value = Filters::apply_filters( $name . '_header', $value );
            header( $name . ( $value ? ': ' . $value : '' ) );
        }
    }

    /**
     * Return a HTTP status code
     *
     */
    public static function get_http_status( $code ) {
        $code = intval( $code );

        try {
            return $this->codes[ $code ];
        } catch (OutOfRangeException $exception) {
            throw new UnknownStatus();
        }
    }

    /**
     * Send a filterable content type header
     *
     * @since 1.7
     * @param string $type content type ('text/html', 'application/json', ...)
     * @return bool whether header was sent
     */
    public static function content_type( $type ) {
        $charset = Filters::apply_filters( 'content_type_header_charset', 'utf-8', $type );
        self::__construct( 'Content-Type', $type . '; charset=' . $charset );
    }

    /**
     * Set HTTP status header
     *
     */
    public static function status( $code ) {
        if( headers_sent() )

            return;
    
        try {
            http_response_code( $code ); // @TODO callback and check relevances
        } catch (Exception $e) {
            $protocol = $_SERVER['SERVER_PROTOCOL'];
            if ( 'HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol )
                $protocol = 'HTTP/1.0';

            $code = intval( $code );
            $desc = get_http_status( $code );
            @header ("$protocol $code $desc"); // This causes problems on IIS and some FastCGI setups
        }
        Filters::do_action( 'status_header', $code );
    }
    
}

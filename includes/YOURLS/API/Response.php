<?php

/**
 * Response Wrapper
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\API;

class Response {

    private $response = array(
        'simple'      => 'Need either XML or JSON format for stats',
        'message'     => 'OK',
        'status_code' => 200
    );

    protected static $formats = array(
        'json',
        'xml',
        'simple'
    );

    public $format = 'json';

    public function __construct( array $response ) {
        $this->response = array_merge( $this->response, $response );
    }

    public function __set( $name, $value ) {
        $this->response[ $name ] = $value;
    }

    private function __get( $name ) {
        return $this->response[ $name ];
    }

    public function __isset( $name ) {
        isset( $this->response[ $name ] );
    }

    public function __unset( $name ) {
        unset( $this->response[ $name ] );
    }

    public function __toString() {
        Header::status( $response[ 'status_code' ] );
        Filters::apply_filter( 'api_output', $this->format, $this->$format() );
    }

    public function xml() {
        unset( $this->simple );
        $xml = new SimpleXMLElement( '<response/>' );
        self::array_to_xml( $response, $xml );
        Header::type( 'application/xml' );

        return $xml->asXML();
    }

    public static function array_to_xml( $array, &$xml ) {
        foreach( $array as $key => $value ) {
            if( is_array( $value ) ) {
                $subnode = $xml->addChild( $key );
                self::array_to_xml( $value, $subnode );
            } else {
                $xml->addChild( $key , $value );
            }
        }
    }

    public function json() {
        unset( $this->simple );
        Header::type( 'application/json' );

        return json_encode( $response );
    }

    public function simple() {
        Header::type( 'text/plain' );

        return $this->simple;
    }

}

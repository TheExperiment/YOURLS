<?php

/**
 * Answer Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\API;

class Answer {

    private $answer = array(
        'simple'      => 'Need either XML or JSON format for stats',
        'message'     => 'OK',
        'status_code' => 200
    );

    protected static $formats = array(
        'json',
        'xml',
        'simple',
    );

    protected static $format;

    public function __construct( array $answer ) {
        $this->answer = array_merge( $this->answer, $answer );
    }

    public function __set( $name, $value ) {
        $this->answer[ $name ] = $value;
    }

    private function __get( $name ) {
        return $this->answer[ $name ];
    }

    public function __isset( $name ) {
        isset( $this->answer[ $name ] );
    }

    public function __unset( $name ) {
        unset( $this->answer[ $name ] );
    }

    public function __toString() {
        Header::status( $answer[ 'status_code' ] );
        Filters::apply_filter( 'api_output', $this->format, $this->$format() );
    }

    public function xml() {
        unset( $this->simple );
        $xml = new SimpleXMLElement('<response/>');
        self::array_to_xml( $answer, $xml );
        Header::type( 'application/xml' );

        return $xml->asXML();
    }

    public function format( $format ) {
        if( !in_array( $format, $formats ) ) {
            throw new APIExeption( 'Unknown format' );
        }
        $this->format = $format;
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

        return json_encode( $answer );
    }

    public function simple() {
        Header::type( 'text/plain' );

        return $this->answer[ 'simple' ];
    }

}

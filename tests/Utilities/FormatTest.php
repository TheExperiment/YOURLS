<?php

/**
 * YOURLS Unit Tests
 * Format Test
 *
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Utilities;

class FormatTest extends \PHPUnit_Framework_TestCase {

    public $charset = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    public function random_string_provider() {
        return array(
            array(
                substr( str_shuffle( $this->charset ), 0, rand( 1, strlen( $this->charset ) ) ),
                substr( str_shuffle( $this->charset ), 0, rand( 1, strlen( $this->charset ) ) )
            )
        );
    }

    /**
     * @covers YOURLS\Utilities\Format::int2string
     * @dataProvider random_string_provider
     */
    public function test_int2string() {
        $this->assertInternalType(
            'string',
            Format::string2int( rand( 1, strlen( $this->charset ) ), $this->charset )
        );
    }
    
 
    /**
     * @covers YOURLS\Utilities\Format::string2int
     * @dataProvider random_string_provider
     */
    public function test_string2int( $a, $b ) {
        $this->assertInternalType( 'int', Format::string2int( $a, $this->charset ) );
        $this->assertNotEquals( 
            Format::string2int( $a, $this->charset ),
            Format::string2int( $b, $this->charset )
        );
    }

    /**
     * @covers YOURLS\Utilities\Format::string2htmlid
     * @dataProvider random_string_provider
     */
    public function test_string2htmlid( $a, $b ) {
        $this->assertNotEquals(
            Format::string2htmlid( $a ),
            Format::string2htmlid( $b ) 
        );
    }

}

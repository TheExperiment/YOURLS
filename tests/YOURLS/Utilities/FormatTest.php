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

    /**
     * @covers YOURLS\Utilities\Format::int2string
     */
    public function test_int2string() {
        $this->assertEquals( 'lz', Format::int2string( 1337, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) );
    }

    /**
     * @covers YOURLS\Utilities\Format::string2int
     */
    public function test_string2int() {
        $this->assertEquals( 12730, Format::string2int( '3jk', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ' ) );
    }

    /**
     * @covers YOURLS\Utilities\Format::string2htmlid
     */
    public function test_string2htmlid() {
        $this->assertEquals( 'y1667525113', Format::string2htmlid( '3jkdfw f166aé 165è(z' ) );
    }

    /**
     * @covers YOURLS\Utilities\Format::sanitize_string
     */
    public function test_sanitize_string() {
        $this->markTestSkipped();
        $this->assertEquals( 'y1667525113', Format::sanitize_string( '3jkdfw f166aé 165è(z' ) );
    }

    /**
     * @covers YOURLS\Utilities\Format::sanitize_title
     */
    public function test_sanitize_title() {
        $this->assertEquals( 'Vized@sd&400;g "ofi ed799 (', Format::sanitize_title( 'Vized@sd&400;g "ofi ed799 (' ) );
    }

}

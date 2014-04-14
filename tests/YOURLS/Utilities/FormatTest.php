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
    public function testInt2string() {
        $this->assertEquals( '115', Format::int2string( 1337, '0123456789abcdefghijklmnopqrstuvwxyz' ) );
    }
    
}

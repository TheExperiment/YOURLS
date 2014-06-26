<?php

/**
 * ClientData
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Network\Client;

interface Data {

    private $value;

    public function set();
    public function sanitize();

}

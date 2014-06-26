<?php

/**
 * EndPoint
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\API;

interface EndPoint extends Response {

    public $params;
    public function __construct();

}

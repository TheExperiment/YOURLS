<?php

/**
 * YOURLS Unit Tests Bootstrap
 *
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

$loader = require __DIR__ . "/../includes/vendor/autoload.php";
$loader->addPsr4( 'YOURLS\\', __DIR__ );

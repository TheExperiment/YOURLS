<?php
/*
Plugin Name: GeoIP
Plugin URI: http://yourls.org
Description: Get localization and add a beautiful flag for every clicks
Version: 2.0
Author: YOURLS
Author URI: http://yourls.org
*/

// Load libraries (ie GeoIP)
require_once __DIR__ . '/vendor/autoload.php';

// Use GeoIP Reader
use GeoIp2\Database\Reader;

/**
 * Get Country ISO code from IP
 *
 * Use GeoIPv2 to get localization of the given IP address.
 *
 * @param mixed $ip The given IP
 * @return mixed
 */
function yp_geoip( $ip ){
    $reader = new Reader( __DIR__ . '/database/GeoLite2-Country.mmdb' );
    $record = $reader->countriy( $ip );

    return $record->country->isoCode;
}

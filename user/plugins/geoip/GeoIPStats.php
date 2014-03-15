<?php

/**
 * GeoIPStats short summary.
 *
 * GeoIPStats description.
 *
 * @version 1.0
 * @author Léo
 */
class GeoIPStats {
    
    /**
     * Converts an IP to a 2 letter country code, using GeoIP database if available in includes/geo/
     *
     * @since 1.4
     * @param string $ip IP or, if empty string, will be current user IP
     * @param string $defaut Default string to return if IP doesn't resolve to a country (malformed, private IP...)
     * @return string 2 letter country code (eg 'US') or $default
     */
    public function geo_ip_to_countrycode( $ip = '', $default = '' ) {
        // Allow plugins to short-circuit the Geo IP API
        $location = Filters::apply_filter( 'shunt_geo_ip_to_countrycode', false, $ip, $default ); // at this point $ip can be '', check if your plugin hooks in here
        if ( false !== $location )
            return $location;

        if ( $ip == '' )
            $ip = get_IP();

        // Use IPv4 or IPv6 DB & functions
        if( false === filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            $db   = 'GeoIP.dat';
            $func = 'geoip_country_code_by_addr';
        } else {
            $db   = 'GeoIPv6.dat';
            $func = 'geoip_country_code_by_addr_v6';
        }

        if ( !file_exists( YOURLS_INC . '/geo/' . $db ) || !file_exists( YOURLS_INC .'/geo/geoip.inc' ) )
            return $default;

        require_once( YOURLS_INC . '/geo/geoip.inc' );
        $gi = geoip_open( YOURLS_INC . '/geo/' . $db, GEOIP_STANDARD );
        try {
            $location = call_user_func( $func, $gi, $ip );
        }
        catch ( Exception $e ) {
            $location = '';
        }
        geoip_close( $gi );

        if( '' == $location )
            $location = $default;

        return Filters::apply_filter( 'geo_ip_to_countrycode', $location, $ip, $default );
    }

    /**
     * Converts a 2 letter country code to long name (ie AU -> Australia)
     *
     */
    public function geo_countrycode_to_countryname( $code ) {
        // Allow plugins to short-circuit the Geo IP API
        $country = Filters::apply_filter( 'shunt_geo_countrycode_to_countryname', false, $code );
        if ( false !== $country )
            return $country;

        // Load the Geo class if not already done
        if( !class_exists( 'GeoIP', false ) ) {
            $temp = geo_ip_to_countrycode( '127.0.0.1' );
        }

        if( class_exists( 'GeoIP', false ) ) {
            $geo  = new GeoIP;
            $id   = $geo->GEOIP_COUNTRY_CODE_TO_NUMBER[ $code ];
            $long = $geo->GEOIP_COUNTRY_NAMES[ $id ];

            return $long;
        } else {
            return false;
        }
    }

    /**
     * Return flag URL from 2 letter country code
     *
     */
    public function geo_get_flag( $code ) {
        return Filters::apply_filter( 'geo_get_flag', 'flag-' . strtolower( $code ), $code );
    }


}

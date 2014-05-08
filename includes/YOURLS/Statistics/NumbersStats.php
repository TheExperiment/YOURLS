<?php

/**
 * Info Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Statistics;

/**
 * Summary of Info
 */
class Info implements Statistics {

    /**
     * Build a list of all daily values between d1/m1/y1 to d2/m2/y2.
     *
     */
    public function build_list_of_days( $dates ) {
        /* Say we have an array like:
        $dates = array (
            2009 => array (
                '08' => array (
                    29 => 15,
                    30 => 5,
                ),
                '09' => array (
                    '02' => 3,
                    '03' => 5,
                    '04' => 2,
                    '05' => 99,
                )
            )
        );
         */

        // Get first & last years from our range. In our example: 2009 & 2009
        $first_year = key( $dates );
        $_keys      = array_keys( $dates );
        $last_year  = end( $_keys );
        reset( $dates );

        // Get first & last months from our range. In our example: 08 & 09
        $first_month = key( $dates[ $first_year ] );
        $_keys       = array_keys( $dates[ $last_year ] );
        $last_month  = end( $_keys );
        reset( $dates );

        // Get first & last days from our range. In our example: 29 & 05
        $first_day = key( $dates[ $first_year ][ $first_month ] );
        $_keys     = array_keys( $dates[ $last_year ][ $last_month ] );
        $last_day  = end( $_keys );

        unset( $_keys );

        // Now build a list of all years (2009), month (08 & 09) and days (all from 2009-08-29 to 2009-09-05)
        $list_of_years  = array();
        $list_of_months = array();
        $list_of_days   = array();
        for ( $year = $first_year; $year <= $last_year; $year++ ) {
            $_year = sprintf( '%04d', $year );
            $list_of_years[ $_year ] = $_year;
            $current_first_month = ( $year == $first_year ? $first_month : '01' );
            $current_last_month  = ( $year == $last_year ? $last_month : '12' );
            for ( $month = $current_first_month; $month <= $current_last_month; $month++ ) {
                $_month = sprintf( '%02d', $month );
                $list_of_months[ $_month ] = $_month;
                $current_first_day = ( $year == $first_year && $month == $first_month ? $first_day : '01' );
                $current_last_day  = ( $year == $last_year && $month == $last_month ? $last_day : days_in_month( $month, $year) );
                for ( $day = $current_first_day; $day <= $current_last_day; $day++ ) {
                    $day = sprintf( '%02d', $day );
                    $key = date( 'M d, Y', mktime( 0, 0, 0, $_month, $day, $_year ) );
                    $list_of_days[ $key ] = isset( $dates[$_year][$_month][$day] ) ? $dates[$_year][$_month][$day] : 0;
                }
            }
        }

        return array(
            'list_of_days'   => $list_of_days,
            'list_of_months' => $list_of_months,
            'list_of_years'  => $list_of_years,
        );
    }


    /**
     * Get max value from date array of 'Aug 12, 2012' = '1337'
     *
     */
    public function get_best_day( $list_of_days ) {
        $max = max( $list_of_days );
        foreach( $list_of_days as $k=>$v ) {
            if ( $v == $max )
                return array( 'day' => $k, 'max' => $max );
        }
    }

}

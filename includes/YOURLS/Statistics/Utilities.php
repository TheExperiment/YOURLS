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
class Utilities {

    /**
     * Echoes an image tag of Google Charts map from sorted array of 'country_code' => 'number of visits' (sort by DESC)
     *
     */
    public function countries_map( $countries, $id = null ) {

        Filters::do_action( 'pre_stats_countries_map' );

        // if $id is null then assign a random string
        if( $id === null )
            $id = uniqid ( 'stats_map_' );

        $data = array_merge( array( 'Country' => 'Hits' ), $countries );
        $data = google_array_to_data_table( $data );

        $options = array(
            'backgroundColor' => "white",
            'colorAxis'       => "{colors:['A8D0ED','99C4E4','8AB8DB','7BACD2','6BA1C9','5C95C0','4D89B7','3E7DAE','2E72A5','1F669C']}",
            'width'           => "665",
            'height'          => "400",
            'theme'           => 'maximized'
        );
        $options = Filters::apply_filter( 'stats_countries_map_options', $options );

        $map = google_viz_code( 'GeoChart', $data, $options, $id );

        echo Filters::apply_filter( 'stats_countries_map', $map, $countries, $options, $id );
    }

    /**
     * Echoes an image tag of Google Charts pie from sorted array of 'data' => 'value' (sort by DESC). Optional $limit = (integer) limit list of X first countries, sorted by most visits
     *
     */
    public function pie( $data, $limit = 10, $size = '340x220', $id = null ) {

        Filters::do_action( 'pre_stats_pie' );

        // if $id is null then assign a random string
        if( $id === null )
            $id = uniqid ( 'stats_pie_' );

        // Trim array: $limit first item + the sum of all others
        if ( count( $data ) > $limit ) {
            $i= 0;
            $trim_data = array( 'Others' => 0 );
            foreach( $data as $item=>$value ) {
                $i++;
                if( $i <= $limit ) {
                    $trim_data[$item] = $value;
                } else {
                    $trim_data['Others'] += $value;
                }
            }
            $data = $trim_data;
        }

        // Scale items
        $_data = scale_data( $data );

        list($width, $height) = explode( 'x', $size );

        $options = array(
            'theme'  => 'maximized',
            'width'   => $width,
            'height'   => $height,
            'colors'    => "['A8D0ED','99C4E4','8AB8DB','7BACD2','6BA1C9','5C95C0','4D89B7','3E7DAE','2E72A5','1F669C']",
            'legend'     => 'none',
            'chartArea'   => '{top: "5%", height: "90%"}',
            'pieSliceText' => 'label',
        );
        $options = Filters::apply_filter( 'stats_pie_options', $options );

        $script_data = array_merge( array( 'Country' => 'Value' ), $_data );
        $script_data = google_array_to_data_table( $script_data );

        $pie = google_viz_code( 'PieChart', $script_data, $options, $id );

        echo Filters::apply_filter( 'stats_pie', $pie, $data, $limit, $size, $options, $id );
    }

    /**
     * Echoes an image tag of Google Charts line graph from array of values (eg 'number of clicks').
     *
     * $legend1_list & legend2_list are values used for the 2 x-axis labels. $id is an HTML/JS id
     *
     */
    public function line( $values, $id = null ) {

        Filters::do_action( 'pre_stats_line' );

        // if $id is null then assign a random string
        if( $id === null )
            $id = uniqid( 'stats_line_' );

        // If we have only 1 day of data, prepend a fake day with 0 hits for a prettier graph
        if ( count( $values ) == 1 )
            array_unshift( $values, 0 );

        // Keep only a subset of values to keep graph smooth
        $values = array_granularity( $values, 30 );

        $data = array_merge( array( 'Time' => 'Hits' ), $values );
        $data = google_array_to_data_table( $data );

        $options = array(
            "legend"      => "none",
            "pointSize"   => "3",
            "theme"       => "maximized",
            "curveType"   => "function",
            "width"       => 430,
            "height"	  => 220,
            "hAxis"       => "{minTextSpacing: 80, maxTextLines: 1, maxAlternation: 1}",
            "vAxis"       => "{minValue: -0.5, format: '#'}",
            "colors"	  => "['#2a85b3']",
        );
        $options = Filters::apply_filter( 'stats_line_options', $options );

        $lineChart = google_viz_code( 'LineChart', $data, $options, $id );

        echo Filters::apply_filter( 'stats_line', $lineChart, $values, $options, $id );
    }

    /**
     * Return favicon URL
     *
     */
    public function get_favicon_url( $url ) {
        return match_current_protocol( 'http://www.google.com/s2/u/0/favicons?domain=' . get_domain( $url, false ) );
    }

    /**
     * Scale array of data from 0 to 100 max
     *
     */
    public function scale_data( $data ) {
        $max = max( $data );
        if( $max > 100 ) {
            foreach( $data as $k=>$v ) {
                $data[$k] = intval( $v / $max * 100 );
            }
        }

        return $data;
    }

    /**
     * Tweak granularity of array $array: keep only $grain values. This make less accurate but less messy graphs when too much values. See http://code.google.com/apis/chart/formats.html#granularity
     *
     */
    public function array_granularity( $array, $grain = 100, $preserve_max = true ) {
        if ( count( $array ) > $grain ) {
            $max = max( $array );
            $step = intval( count( $array ) / $grain );
            $i = 0;
            // Loop through each item and unset except every $step (optional preserve the max value)
            foreach( $array as $k=>$v ) {
                $i++;
                if ( $i % $step != 0 ) {
                    if ( $preserve_max == false ) {
                        unset( $array[$k] );
                    } else {
                        if ( $v < $max )
                            unset( $array[$k] );
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Transform data array to data table for Google API
     *
     */
    public function google_array_to_data_table( $data ){
        $str  = "var data = google.visualization.arrayToDataTable([";
        foreach( $data as $label => $values ){
            if( !is_array( $values ) ) {
                $values = array( $values );
            }
            $str .= "['$label',";
            foreach( $values as $value ){
                if( !is_numeric( $value ) && strpos( $value, '[' ) !== 0 && strpos( $value, '{' ) !== 0 ) {
                    $value = "'$value'";
                }
                $str .= "$value";
            }
            $str .= "],";
        }
        $str = substr( $str, 0, -1 ); // remove the trailing comma/return, reappend the return
        $str .= "]);"; // wrap it up

        return $str;
    }

    /**
     * Return javascript code that will display the Google Chart
     *
     */
    public function google_viz_code( $graph_type, $data, $options, $id ) {
        $function_name = 'graph' . $id;
        $code  = "<script id=\"$function_name\" type=\"text/javascript\">";
        $code .= "function $function_name() { ";

        $code .= "$data";

        $code .= "var options = {";
        foreach( $options as $field => $value ) {
            if( !is_numeric( $value ) && strpos( $value, '[' ) !== 0 && strpos( $value, '{' ) !== 0 ) {
                $value = "\"$value\"";
            }
            $code .= "'$field': $value,";
        }
        $code  = substr( $code, 0, -1 ); // remove the trailing comma/return, reappend the return
        $code .= "};new google.visualization.$graph_type( document.getElementById('visualization_$id') ).draw( data, options );}";
        $code .= "google.setOnLoadCallback( $function_name );";
        $code .= "</script>";
        $code .= "<div id=\"visualization_$id\"></div>";

        return $code;
    }

}

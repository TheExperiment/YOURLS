<?php

/**
 * Info Wrapper
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Statistics;

/**
 * Summary of Info
 */
class NumbersStats implements Statistics {

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

    public function __toString() {
        yourls_do_action( 'pre_yourls_info_stats', $keyword );
        if ( $list_of_days ) { ?>

            <ul id="stats_lines" class="toggle_display stat_line">
                <?php
                if( $do_24 == true )
                    echo '<li><a href="#stat_line_24">' . yourls__( 'Last 24 hours' ) . '</a>';
                if( $do_7 == true )
                    echo '<li><a href="#stat_line_7">' . yourls__( 'Last 7 days' ) . '</a>';
                if( $do_30 == true )
                    echo '<li><a href="#stat_line_30">' . yourls__( 'Last 30 days' ) . '</a>';
                if( $do_all == true )
                    echo '<li><a href="#stat_line_all">' . yourls__( 'All time' ) . '</a>';
                ?>
            </ul>
            <?php
            // Generate, and display if applicable, each needed graph
            foreach( $graphs as $graph => $graphtitle ) {
                if( ${'do_'.$graph} == true ) {
                    $display = ( ${'display_'.$graph} === true ? 'display:block' : 'display:none' );
                    echo "<div id='stat_line_$graph' class='stats_line line' style='$display'>";
                    echo '<h3>' . yourls_s( 'Number of hits : %s' , $graphtitle ) . '</h3>';
                    switch( $graph ) {
                        case '24':
                            yourls_stats_line( $last_24h, "stat_line_$graph" );
                            break;

                        case '7':
                        case '30':
                            $slice = array_slice( $list_of_days, intval( $graph ) * -1 );
                            yourls_stats_line( $slice, "stat_line_$graph" );
                            unset( $slice );
                            break;

                        case 'all':
                            yourls_stats_line( $list_of_days, "stat_line_$graph" );
                            break;
                    }
                    echo "</div>";
                }
            } ?>
                                <details>
                    <summary><?php yourls_e( 'More details' ); ?></summary>
<?php
            yourls_html_htag( yourls__( 'Historical click count' ), 3 );

            $ago = round( (date('U') - strtotime($timestamp)) / (24* 60 * 60 ) );
            if( $ago <= 1 ) {
                $daysago = '';
            } else {
                $daysago = ' (' . sprintf( yourls_n( 'about 1 day ago', 'about %s days ago', $ago ), $ago ) . ')';
            }
            ?>
            <p><?php echo /* //translators: eg Short URL created on March 23rd 1972 */ yourls_s( 'Short URL created on %s', yourls_date_i18n( "F j, Y @ g:i a", ( strtotime( $timestamp ) + YOURLS_HOURS_OFFSET * 3600 ) ) ) . $daysago; ?></p>
            <div class="wrap_unfloat">
                <ul class="stat_line" id="historical_clicks">
                <?php
                foreach( $graphs as $graph => $graphtitle ) {
                    if ( ${'do_'.$graph} ) {
                        $link = "<a href='#stat_line_$graph'>$graphtitle</a>";
                    } else {
                        $link = $graphtitle;
                    }
                    $stat = '';
                    if( ${'do_'.$graph} ) {
                        switch( $graph ) {
                            case '7':
                            case '30':
                                $stat = yourls_s( '%s per day', round( ( ${'hits_'.$graph} / intval( $graph ) ) * 100 ) / 100 );
                                break;
                            case '24':
                                $stat = yourls_s( '%s per hour', round( ( ${'hits_'.$graph} / 24 ) * 100 ) / 100 );
                                break;
                            case 'all':
                                if( $ago > 0 )
                                    $stat = yourls_s( '%s per day', round( ( ${'hits_'.$graph} / $ago ) * 100 ) / 100 );
                        }
                    }
                    $hits = sprintf( yourls_n( '%s hit', '%s hits', ${'hits_'.$graph} ), ${'hits_'.$graph} );
                    echo "<li><span class='historical_link'>$link</span> <span class='historical_count'>$hits</span> $stat</li>";
                }
                ?>
                </ul>
            </div>

                <?php yourls_html_htag( yourls__( 'Best day' ), 3 );
                $best = yourls_stats_get_best_day( $list_of_days );
                $best_time['day']   = date( "d", strtotime( $best['day'] ) );
                $best_time['month'] = date( "m", strtotime( $best['day'] ) );
                $best_time['year']  = date( "Y", strtotime( $best['day'] ) );
                ?>
                <p><?php echo sprintf( /* //translators: eg. 43 hits on January 1, 1970 */ yourls_n( '<strong>%1$s</strong> hit on %2$s', '<strong>%1$s</strong> hits on %2$s', $best['max'] ), $best['max'],  yourls_date_i18n( "F j, Y", strtotime( $best['day'] ) ) ); ?>.</p>
                    <ul id="details-clicks">
                        <?php
                        foreach( $dates as $year=>$months ) {
                            $css_year = ( $year == $best_time['year'] ? 'best_year' : '' );
                            if( count( $list_of_years ) > 1 ) {
                                $li = "<a href='' class='details' id='more_year$year'>" . yourls_s( 'Year %s', $year ) . '</a>';
                                $display = 'none';
                            } else {
                                $li = yourls_s( 'Year %s', $year );
                                $display = 'block';
                            }
                            echo "<li><span class='$css_year'>$li</span>";
                            echo "<ul style='display:$display' id='details_year$year'>";
                            foreach( $months as $month=>$days ) {
                                $css_month = ( ( $month == $best_time['month'] && ( $css_year == 'best_year' ) ) ? 'best_month' : '' );
                                $monthname = yourls_date_i18n( "F", mktime( 0, 0, 0, $month, 1 ) );
                                if( count( $list_of_months ) > 1 ) {
                                    $li = "<a href='' class='details' id='more_month$year$month'>$monthname</a>";
                                    $display = 'none';
                                } else {
                                    $li = "$monthname";
                                    $display = 'block';
                                }
                                echo "<li><span class='$css_month'>$li</span>";
                                echo "<ul style='display:$display' id='details_month$year$month'>";
                                foreach( $days as $day=>$hits ) {
                                    $class = ( $hits == $best['max'] ? 'class="bestday"' : '' );
                                    echo "<li $class>$day: " . sprintf( yourls_n( '1 hit', '%s hits', $hits ), $hits ) ."</li>";
                                }
                                echo "</ul>";
                            }
                            echo "</ul>";
                        }
                        ?>
                    </ul>
                </details>

        <?php yourls_do_action( 'post_yourls_info_stats', $keyword ); ?>

    </div>

    <?php
    }

}

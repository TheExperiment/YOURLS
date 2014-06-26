<php
$table = YOURLS_DB_TABLE_LOG;
$referrers = array();
$direct = $notdirect = 0;
$countries = array();
$dates = array();
$list_of_days = array();
$list_of_months = array();
$list_of_years = array();
$last_24h = array();

// Define keyword query range : either a single keyword or a list of keywords
if( $aggregate ) {
    $keyword_list = yourls_get_longurl_keywords( $longurl );
    $keyword_range = "IN ( '" . join( "', '", $keyword_list ) . "' )"; // IN ( 'blah', 'bleh', 'bloh' )
} else {
    $keyword_range = sprintf( "= '%s'", yourls_escape( $keyword ) );
}

// *** Referrers ***
$query = "SELECT `referrer`, COUNT(*) AS `count` FROM `$table` WHERE `shorturl` $keyword_range GROUP BY `referrer`;";
$rows = $ydb->get_results( yourls_apply_filter( 'stat_query_referrer', $query ) );

// Loop through all results and build list of referrers, countries and hits per day
foreach( (array)$rows as $row ) {
    if ( $row->referrer == 'direct' ) {
        $direct = $row->count;
        continue;
    }

    $host = yourls_get_domain( $row->referrer );
    if( !array_key_exists( $host, $referrers ) )
        $referrers[$host] = array( );
    if( !array_key_exists( $row->referrer, $referrers[$host] ) ) {
        $referrers[$host][$row->referrer] = $row->count;
        $notdirect += $row->count;
    } else {
        $referrers[$host][$row->referrer] += $row->count;
        $notdirect += $row->count;
    }
}

// Sort referrers. $referrer_sort is a array of most frequent domains
arsort( $referrers );
$referrer_sort = array();
$number_of_sites = count( array_keys( $referrers ) );
foreach( $referrers as $site => $urls ) {
    if( count($urls) > 1 || $number_of_sites == 1 )
        $referrer_sort[$site] = array_sum( $urls );
}
arsort($referrer_sort);

// *** Countries ***
$query = "SELECT `country_code`, COUNT(*) AS `count` FROM `$table` WHERE `shorturl` $keyword_range GROUP BY `country_code`;";
$rows = $ydb->get_results( yourls_apply_filter( 'stat_query_country', $query ) );

// Loop through all results and build list of countries and hits
foreach( (array)$rows as $row ) {
    if ("$row->country_code")
        $countries["$row->country_code"] = $row->count;
}

// Sort countries, most frequent first
if ( $countries )
    arsort( $countries );

// *** Dates : array of $dates[$year][$month][$day] = number of clicks ***
$query = "SELECT
    DATE_FORMAT(`click_time`, '%Y') AS `year`,
    DATE_FORMAT(`click_time`, '%m') AS `month`,
    DATE_FORMAT(`click_time`, '%d') AS `day`,
    COUNT(*) AS `count`
FROM `$table`
WHERE `shorturl` $keyword_range
GROUP BY `year`, `month`, `day`;";
$rows = $ydb->get_results( yourls_apply_filter( 'stat_query_dates', $query ) );

// Loop through all results and fill blanks
foreach( (array)$rows as $row ) {
    if( !array_key_exists($row->year, $dates ) )
        $dates[$row->year] = array();
    if( !array_key_exists( $row->month, $dates[$row->year] ) )
        $dates[$row->year][$row->month] = array();
    if( !array_key_exists( $row->day, $dates[$row->year][$row->month] ) )
        $dates[$row->year][$row->month][$row->day] = $row->count;
    else
        $dates[$row->year][$row->month][$row->day] += $row->count;
}

// Sort dates, chronologically from [2007][12][24] to [2009][02][19]
ksort( $dates );
foreach( $dates as $year=>$months ) {
    ksort( $dates[$year] );
    foreach( $months as $month=>$day ) {
        ksort( $dates[$year][$month] );
    }
}

// Get $list_of_days, $list_of_months, $list_of_years
reset( $dates );
if( $dates ) {
    extract( yourls_build_list_of_days( $dates ) );
}

// *** Last 24 hours : array of $last_24h[ $hour ] = number of click ***
$query = "SELECT
    DATE_FORMAT(`click_time`, '%H %p') AS `time`,
    COUNT(*) AS `count`
FROM `$table`
WHERE `shorturl` $keyword_range AND `click_time` > (CURRENT_TIMESTAMP - INTERVAL 1 DAY)
GROUP BY `time`;";
$rows = $ydb->get_results( yourls_apply_filter( 'stat_query_last24h', $query ) );

$_last_24h = array();
foreach( (array)$rows as $row ) {
    if ( $row->time )
        $_last_24h[ "$row->time" ] = $row->count;
}

$now = intval( date('U') );
for ($i = 23; $i >= 0; $i--) {
    $h = date('H A', $now - ($i * 60 * 60) );
    // If the $last_24h doesn't have all the hours, insert missing hours with value 0
    $last_24h[ $h ] = array_key_exists( $h, $_last_24h ) ? $_last_24h[ $h ] : 0 ;
}
unset( $_last_24h );

// *** Queries all done, phew ***

// Filter all this junk if applicable. Be warned, some are possibly huge datasets.
$referrers      = yourls_apply_filter( 'pre_yourls_info_referrers', $referrers );
$referrer_sort  = yourls_apply_filter( 'pre_yourls_info_referrer_sort', $referrer_sort );
$direct         = yourls_apply_filter( 'pre_yourls_info_direct', $direct );
$notdirect      = yourls_apply_filter( 'pre_yourls_info_notdirect', $notdirect );
$dates          = yourls_apply_filter( 'pre_yourls_info_dates', $dates );
$list_of_days   = yourls_apply_filter( 'pre_yourls_info_list_of_days', $list_of_days );
$list_of_months = yourls_apply_filter( 'pre_yourls_info_list_of_months', $list_of_months );
$list_of_years  = yourls_apply_filter( 'pre_yourls_info_list_of_years', $list_of_years );
$last_24h       = yourls_apply_filter( 'pre_yourls_info_last_24h', $last_24h );
$countries      = yourls_apply_filter( 'pre_yourls_info_countries', $countries );

// I can haz debug data
/**
echo "<pre>";
echo "referrers: "; print_r( $referrers );
echo "referrer sort: "; print_r( $referrer_sort );
echo "direct: $direct\n";
echo "notdirect: $notdirect\n";
echo "dates: "; print_r( $dates );
echo "list of days: "; print_r( $list_of_days );
echo "list_of_months: "; print_r( $list_of_months );
echo "list_of_years: "; print_r( $list_of_years );
echo "last_24h: "; print_r( $last_24h );
echo "countries: "; print_r( $countries );
die();
/**/

// Day graph
if ( $list_of_days ) {
    $graphs = array(
        '24' => yourls__( 'Last 24 hours' ),
        '7'  => yourls__( 'Last 7 days' ),
        '30' => yourls__( 'Last 30 days' ),
        'all'=> yourls__( 'All time' )
    );

    // Which graph to generate ?
    $do_all = $do_30 = $do_7 = $do_24 = false;
    $hits_all = array_sum( $list_of_days );
    $hits_30  = array_sum( array_slice( $list_of_days, -30 ) );
    $hits_7   = array_sum( array_slice( $list_of_days, -7 ) );
    $hits_24  = array_sum( $last_24h );
    if( $hits_all > 0 )
        $do_all = true; // graph for all days range
    if( $hits_30 > 0 && count( array_slice( $list_of_days, -30 ) ) == 30 )
        $do_30 = true; // graph for last 30 days
    if( $hits_7 > 0 && count( array_slice( $list_of_days, -7 ) ) == 7 )
        $do_7 = true; // graph for last 7 days
    if( $hits_24 > 0 )
        $do_24 = true; // graph for last 24 hours

    // Which graph to display ?
    $display_all = $display_30 = $display_7 = $display_24 = false;
    if( $do_24 ) {
        $display_24 = true;
    } elseif ( $do_7 ) {
        $display_7 = true;
    } elseif ( $do_30 ) {
        $display_30 = true;
    } elseif ( $do_all ) {
        $display_all = true;
    }
}

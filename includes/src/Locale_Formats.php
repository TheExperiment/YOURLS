<?php

/**
 * Locale_Formats Wrapper
 * 
 * @since 1.6
 * @copyright 2009-2014 YOURLS - MIT
 */

/**
 * Class that loads the calendar locale.
 *
 * @since 1.6
 */
class Locale_Formats {
    /**
     * Stores the translated strings for the full weekday names.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    var $weekday;

    /**
     * Stores the translated strings for the one character weekday names.
     *
     * There is a hack to make sure that Tuesday and Thursday, as well
     * as Sunday and Saturday, don't conflict. See init() method for more.
     *
     * @see YOURLS_Locale_Formats::init() for how to handle the hack.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    var $weekday_initial;

    /**
     * Stores the translated strings for the abbreviated weekday names.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    var $weekday_abbrev;

    /**
     * Stores the translated strings for the full month names.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    var $month;

    /**
     * Stores the translated strings for the abbreviated month names.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    var $month_abbrev;

    /**
     * Stores the translated strings for 'am' and 'pm'.
     *
     * Also the capitalized versions.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    var $meridiem;

    /**
     * Stores the translated number format
     *
     * @since 1.6
     * @var array
     * @access private
     */
    var $number_format;

    /**
     * The text direction of the locale language.
     *
     * Default is left to right 'ltr'.
     *
     * @since 1.6
     * @var string
     * @access private
     */
    var $text_direction = 'ltr';

    /**
     * Sets up the translated strings and object properties.
     *
     * The method creates the translatable strings for various
     * calendar elements. Which allows for specifying locale
     * specific calendar names and text direction.
     *
     * @since 1.6
     * @access private
     */
    function init() {
        // The Weekdays
        $this->weekday[0] = /* //translators: weekday */ yourls__( 'Sunday' );
        $this->weekday[1] = /* //translators: weekday */ yourls__( 'Monday' );
        $this->weekday[2] = /* //translators: weekday */ yourls__( 'Tuesday' );
        $this->weekday[3] = /* //translators: weekday */ yourls__( 'Wednesday' );
        $this->weekday[4] = /* //translators: weekday */ yourls__( 'Thursday' );
        $this->weekday[5] = /* //translators: weekday */ yourls__( 'Friday' );
        $this->weekday[6] = /* //translators: weekday */ yourls__( 'Saturday' );

        // The first letter of each day. The _%day%_initial suffix is a hack to make
        // sure the day initials are unique.
        $this->weekday_initial[yourls__( 'Sunday' )]    = /* //translators: one-letter abbreviation of the weekday */ yourls__( 'S_Sunday_initial' );
        $this->weekday_initial[yourls__( 'Monday' )]    = /* //translators: one-letter abbreviation of the weekday */ yourls__( 'M_Monday_initial' );
        $this->weekday_initial[yourls__( 'Tuesday' )]   = /* //translators: one-letter abbreviation of the weekday */ yourls__( 'T_Tuesday_initial' );
        $this->weekday_initial[yourls__( 'Wednesday' )] = /* //translators: one-letter abbreviation of the weekday */ yourls__( 'W_Wednesday_initial' );
        $this->weekday_initial[yourls__( 'Thursday' )]  = /* //translators: one-letter abbreviation of the weekday */ yourls__( 'T_Thursday_initial' );
        $this->weekday_initial[yourls__( 'Friday' )]    = /* //translators: one-letter abbreviation of the weekday */ yourls__( 'F_Friday_initial' );
        $this->weekday_initial[yourls__( 'Saturday' )]  = /* //translators: one-letter abbreviation of the weekday */ yourls__( 'S_Saturday_initial' );

        foreach ($this->weekday_initial as $weekday_ => $weekday_initial_) {
            $this->weekday_initial[$weekday_] = preg_replace('/_.+_initial$/', '', $weekday_initial_);
        }

        // Abbreviations for each day.
        $this->weekday_abbrev[ yourls__( 'Sunday' ) ]    = /* //translators: three-letter abbreviation of the weekday */ yourls__( 'Sun' );
        $this->weekday_abbrev[ yourls__( 'Monday' ) ]    = /* //translators: three-letter abbreviation of the weekday */ yourls__( 'Mon' );
        $this->weekday_abbrev[ yourls__( 'Tuesday' ) ]   = /* //translators: three-letter abbreviation of the weekday */ yourls__( 'Tue' );
        $this->weekday_abbrev[ yourls__( 'Wednesday' ) ] = /* //translators: three-letter abbreviation of the weekday */ yourls__( 'Wed' );
        $this->weekday_abbrev[ yourls__( 'Thursday' ) ]  = /* //translators: three-letter abbreviation of the weekday */ yourls__( 'Thu' );
        $this->weekday_abbrev[ yourls__( 'Friday' ) ]    = /* //translators: three-letter abbreviation of the weekday */ yourls__( 'Fri' );
        $this->weekday_abbrev[ yourls__( 'Saturday' ) ]  = /* //translators: three-letter abbreviation of the weekday */ yourls__( 'Sat' );

        // The Months
        $this->month['01'] = /* //translators: month name */ yourls__( 'January' );
        $this->month['02'] = /* //translators: month name */ yourls__( 'February' );
        $this->month['03'] = /* //translators: month name */ yourls__( 'March' );
        $this->month['04'] = /* //translators: month name */ yourls__( 'April' );
        $this->month['05'] = /* //translators: month name */ yourls__( 'May' );
        $this->month['06'] = /* //translators: month name */ yourls__( 'June' );
        $this->month['07'] = /* //translators: month name */ yourls__( 'July' );
        $this->month['08'] = /* //translators: month name */ yourls__( 'August' );
        $this->month['09'] = /* //translators: month name */ yourls__( 'September' );
        $this->month['10'] = /* //translators: month name */ yourls__( 'October' );
        $this->month['11'] = /* //translators: month name */ yourls__( 'November' );
        $this->month['12'] = /* //translators: month name */ yourls__( 'December' );

        // Abbreviations for each month. Uses the same hack as above to get around the
        // 'May' duplication.
        $this->month_abbrev[ yourls__( 'January' ) ]   = /* //translators: three-letter abbreviation of the month */ yourls__( 'Jan_January_abbreviation' );
        $this->month_abbrev[ yourls__( 'February' ) ]  = /* //translators: three-letter abbreviation of the month */ yourls__( 'Feb_February_abbreviation' );
        $this->month_abbrev[ yourls__( 'March' ) ]     = /* //translators: three-letter abbreviation of the month */ yourls__( 'Mar_March_abbreviation' );
        $this->month_abbrev[ yourls__( 'April' ) ]     = /* //translators: three-letter abbreviation of the month */ yourls__( 'Apr_April_abbreviation' );
        $this->month_abbrev[ yourls__( 'May' ) ]       = /* //translators: three-letter abbreviation of the month */ yourls__( 'May_May_abbreviation' );
        $this->month_abbrev[ yourls__( 'June' ) ]      = /* //translators: three-letter abbreviation of the month */ yourls__( 'Jun_June_abbreviation' );
        $this->month_abbrev[ yourls__( 'July' ) ]      = /* //translators: three-letter abbreviation of the month */ yourls__( 'Jul_July_abbreviation' );
        $this->month_abbrev[ yourls__( 'August' ) ]    = /* //translators: three-letter abbreviation of the month */ yourls__( 'Aug_August_abbreviation' );
        $this->month_abbrev[ yourls__( 'September' ) ] = /* //translators: three-letter abbreviation of the month */ yourls__( 'Sep_September_abbreviation' );
        $this->month_abbrev[ yourls__( 'October' ) ]   = /* //translators: three-letter abbreviation of the month */ yourls__( 'Oct_October_abbreviation' );
        $this->month_abbrev[ yourls__( 'November' ) ]  = /* //translators: three-letter abbreviation of the month */ yourls__( 'Nov_November_abbreviation' );
        $this->month_abbrev[ yourls__( 'December' ) ]  = /* //translators: three-letter abbreviation of the month */ yourls__( 'Dec_December_abbreviation' );

        foreach ($this->month_abbrev as $month_ => $month_abbrev_) {
            $this->month_abbrev[$month_] = preg_replace('/_.+_abbreviation$/', '', $month_abbrev_);
        }

        // The Meridiems
        $this->meridiem['am'] = yourls__( 'am' );
        $this->meridiem['pm'] = yourls__( 'pm' );
        $this->meridiem['AM'] = yourls__( 'AM' );
        $this->meridiem['PM'] = yourls__( 'PM' );

        // Numbers formatting
        // See http://php.net/number_format

        /* //translators: $thousands_sep argument for http://php.net/number_format, default is , */
        $trans = yourls__( 'number_format_thousands_sep' );
        $this->number_format['thousands_sep'] = ('number_format_thousands_sep' == $trans) ? ',' : $trans;

        /* //translators: $dec_point argument for http://php.net/number_format, default is . */
        $trans = yourls__( 'number_format_decimal_point' );
        $this->number_format['decimal_point'] = ('number_format_decimal_point' == $trans) ? '.' : $trans;

        // Set text direction.
        if ( isset( $GLOBALS['text_direction'] ) )
            $this->text_direction = $GLOBALS['text_direction'];
        /* //translators: 'rtl' or 'ltr'. This sets the text direction for YOURLS. */
        elseif ( 'rtl' == yourls_x( 'ltr', 'text direction' ) )
            $this->text_direction = 'rtl';
    }

    /**
     * Retrieve the full translated weekday word.
     *
     * Week starts on translated Sunday and can be fetched
     * by using 0 (zero). So the week starts with 0 (zero)
     * and ends on Saturday with is fetched by using 6 (six).
     *
     * @since 1.6
     * @access public
     *
     * @param int $weekday_number 0 for Sunday through 6 Saturday
     * @return string Full translated weekday
     */
    function get_weekday( $weekday_number ) {
        return $this->weekday[ $weekday_number ];
    }

    /**
     * Retrieve the translated weekday initial.
     *
     * The weekday initial is retrieved by the translated
     * full weekday word. When translating the weekday initial
     * pay attention to make sure that the starting letter does
     * not conflict.
     *
     * @since 1.6
     * @access public
     *
     * @param string $weekday_name
     * @return string
     */
    function get_weekday_initial( $weekday_name ) {
        return $this->weekday_initial[ $weekday_name ];
    }

    /**
     * Retrieve the translated weekday abbreviation.
     *
     * The weekday abbreviation is retrieved by the translated
     * full weekday word.
     *
     * @since 1.6
     * @access public
     *
     * @param string $weekday_name Full translated weekday word
     * @return string Translated weekday abbreviation
     */
    function get_weekday_abbrev( $weekday_name ) {
        return $this->weekday_abbrev[ $weekday_name ];
    }

    /**
     * Retrieve the full translated month by month number.
     *
     * The $month_number parameter has to be a string
     * because it must have the '0' in front of any number
     * that is less than 10. Starts from '01' and ends at
     * '12'.
     *
     * You can use an integer instead and it will add the
     * '0' before the numbers less than 10 for you.
     *
     * @since 1.6
     * @access public
     *
     * @param string|int $month_number '01' through '12'
     * @return string Translated full month name
     */
    function get_month( $month_number ) {
        return $this->month[ sprintf( '%02s', $month_number ) ];		
    }

    /**
     * Retrieve translated version of month abbreviation string.
     *
     * The $month_name parameter is expected to be the translated or
     * translatable version of the month.
     *
     * @since 1.6
     * @access public
     *
     * @param string $month_name Translated month to get abbreviated version
     * @return string Translated abbreviated month
     */
    function get_month_abbrev( $month_name ) {
        return $this->month_abbrev[ $month_name ];
    }

    /**
     * Retrieve translated version of meridiem string.
     *
     * The $meridiem parameter is expected to not be translated.
     *
     * @since 1.6
     * @access public
     *
     * @param string $meridiem Either 'am', 'pm', 'AM', or 'PM'. Not translated version.
     * @return string Translated version
     */
    function get_meridiem( $meridiem ) {
        return $this->meridiem[ $meridiem ];
    }

    /**
     * Global variables are deprecated. For backwards compatibility only.
     *
     * @deprecated For backwards compatibility only.
     * @access private
     *
     * @since 1.6
     */
    function register_globals() {
        $GLOBALS['weekday']         = $this->weekday;
        $GLOBALS['weekday_initial'] = $this->weekday_initial;
        $GLOBALS['weekday_abbrev']  = $this->weekday_abbrev;
        $GLOBALS['month']           = $this->month;
        $GLOBALS['month_abbrev']    = $this->month_abbrev;
    }

    /**
     * Constructor which calls helper methods to set up object variables
     *
     * @uses YOURLS_Locale_Formats::init()
     * @uses YOURLS_Locale_Formats::register_globals()
     * @since 1.6
     *
     * @return YOURLS_Locale_Formats
     */
    function __construct() {
        $this->init();
        $this->register_globals();
    }

    /**
     * Checks if current locale is RTL.
     *
     * @since 1.6
     * @return bool Whether locale is RTL.
     */
    function is_rtl() {
        return 'rtl' == $this->text_direction;
    }
}

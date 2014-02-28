<?php

/**
 * Locale_Formats Wrapper
 *
 * @since 1.6
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Internationalization;

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
    public $weekday;

    /**
     * Stores the translated strings for the one character weekday names.
     *
     * There is a hack to make sure that Tuesday and Thursday, as well
     * as Sunday and Saturday, don't conflict. See init() method for more.
     *
     * @see Locale_Formats::init() for how to handle the hack.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    public $weekday_initial;

    /**
     * Stores the translated strings for the abbreviated weekday names.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    public $weekday_abbrev;

    /**
     * Stores the translated strings for the full month names.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    public $month;

    /**
     * Stores the translated strings for the abbreviated month names.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    public $month_abbrev;

    /**
     * Stores the translated strings for 'am' and 'pm'.
     *
     * Also the capitalized versions.
     *
     * @since 1.6
     * @var array
     * @access private
     */
    public $meridiem;

    /**
     * Stores the translated number format
     *
     * @since 1.6
     * @var array
     * @access private
     */
    public $number_format;

    /**
     * The text direction of the locale language.
     *
     * Default is left to right 'ltr'.
     *
     * @since 1.6
     * @var string
     * @access private
     */
    public $text_direction = 'ltr';

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
    public function init() {
        // The Weekdays
        $this->weekday[0] = /* //translators: weekday */ _( 'Sunday' );
        $this->weekday[1] = /* //translators: weekday */ _( 'Monday' );
        $this->weekday[2] = /* //translators: weekday */ _( 'Tuesday' );
        $this->weekday[3] = /* //translators: weekday */ _( 'Wednesday' );
        $this->weekday[4] = /* //translators: weekday */ _( 'Thursday' );
        $this->weekday[5] = /* //translators: weekday */ _( 'Friday' );
        $this->weekday[6] = /* //translators: weekday */ _( 'Saturday' );

        // The first letter of each day. The _%day%_initial suffix is a hack to make
        // sure the day initials are unique.
        $this->weekday_initial[_( 'Sunday' )]    = /* //translators: one-letter abbreviation of the weekday */ _( 'S_Sunday_initial' );
        $this->weekday_initial[_( 'Monday' )]    = /* //translators: one-letter abbreviation of the weekday */ _( 'M_Monday_initial' );
        $this->weekday_initial[_( 'Tuesday' )]   = /* //translators: one-letter abbreviation of the weekday */ _( 'T_Tuesday_initial' );
        $this->weekday_initial[_( 'Wednesday' )] = /* //translators: one-letter abbreviation of the weekday */ _( 'W_Wednesday_initial' );
        $this->weekday_initial[_( 'Thursday' )]  = /* //translators: one-letter abbreviation of the weekday */ _( 'T_Thursday_initial' );
        $this->weekday_initial[_( 'Friday' )]    = /* //translators: one-letter abbreviation of the weekday */ _( 'F_Friday_initial' );
        $this->weekday_initial[_( 'Saturday' )]  = /* //translators: one-letter abbreviation of the weekday */ _( 'S_Saturday_initial' );

        foreach ($this->weekday_initial as $weekday_ => $weekday_initial_) {
            $this->weekday_initial[$weekday_] = preg_replace('/_.+_initial$/', '', $weekday_initial_);
        }

        // Abbreviations for each day.
        $this->weekday_abbrev[ _( 'Sunday' ) ]    = /* //translators: three-letter abbreviation of the weekday */ _( 'Sun' );
        $this->weekday_abbrev[ _( 'Monday' ) ]    = /* //translators: three-letter abbreviation of the weekday */ _( 'Mon' );
        $this->weekday_abbrev[ _( 'Tuesday' ) ]   = /* //translators: three-letter abbreviation of the weekday */ _( 'Tue' );
        $this->weekday_abbrev[ _( 'Wednesday' ) ] = /* //translators: three-letter abbreviation of the weekday */ _( 'Wed' );
        $this->weekday_abbrev[ _( 'Thursday' ) ]  = /* //translators: three-letter abbreviation of the weekday */ _( 'Thu' );
        $this->weekday_abbrev[ _( 'Friday' ) ]    = /* //translators: three-letter abbreviation of the weekday */ _( 'Fri' );
        $this->weekday_abbrev[ _( 'Saturday' ) ]  = /* //translators: three-letter abbreviation of the weekday */ _( 'Sat' );

        // The Months
        $this->month['01'] = /* //translators: month name */ _( 'January' );
        $this->month['02'] = /* //translators: month name */ _( 'February' );
        $this->month['03'] = /* //translators: month name */ _( 'March' );
        $this->month['04'] = /* //translators: month name */ _( 'April' );
        $this->month['05'] = /* //translators: month name */ _( 'May' );
        $this->month['06'] = /* //translators: month name */ _( 'June' );
        $this->month['07'] = /* //translators: month name */ _( 'July' );
        $this->month['08'] = /* //translators: month name */ _( 'August' );
        $this->month['09'] = /* //translators: month name */ _( 'September' );
        $this->month['10'] = /* //translators: month name */ _( 'October' );
        $this->month['11'] = /* //translators: month name */ _( 'November' );
        $this->month['12'] = /* //translators: month name */ _( 'December' );

        // Abbreviations for each month. Uses the same hack as above to get around the
        // 'May' duplication.
        $this->month_abbrev[ _( 'January' ) ]   = /* //translators: three-letter abbreviation of the month */ _( 'Jan_January_abbreviation' );
        $this->month_abbrev[ _( 'February' ) ]  = /* //translators: three-letter abbreviation of the month */ _( 'Feb_February_abbreviation' );
        $this->month_abbrev[ _( 'March' ) ]     = /* //translators: three-letter abbreviation of the month */ _( 'Mar_March_abbreviation' );
        $this->month_abbrev[ _( 'April' ) ]     = /* //translators: three-letter abbreviation of the month */ _( 'Apr_April_abbreviation' );
        $this->month_abbrev[ _( 'May' ) ]       = /* //translators: three-letter abbreviation of the month */ _( 'May_May_abbreviation' );
        $this->month_abbrev[ _( 'June' ) ]      = /* //translators: three-letter abbreviation of the month */ _( 'Jun_June_abbreviation' );
        $this->month_abbrev[ _( 'July' ) ]      = /* //translators: three-letter abbreviation of the month */ _( 'Jul_July_abbreviation' );
        $this->month_abbrev[ _( 'August' ) ]    = /* //translators: three-letter abbreviation of the month */ _( 'Aug_August_abbreviation' );
        $this->month_abbrev[ _( 'September' ) ] = /* //translators: three-letter abbreviation of the month */ _( 'Sep_September_abbreviation' );
        $this->month_abbrev[ _( 'October' ) ]   = /* //translators: three-letter abbreviation of the month */ _( 'Oct_October_abbreviation' );
        $this->month_abbrev[ _( 'November' ) ]  = /* //translators: three-letter abbreviation of the month */ _( 'Nov_November_abbreviation' );
        $this->month_abbrev[ _( 'December' ) ]  = /* //translators: three-letter abbreviation of the month */ _( 'Dec_December_abbreviation' );

        foreach ($this->month_abbrev as $month_ => $month_abbrev_) {
            $this->month_abbrev[$month_] = preg_replace('/_.+_abbreviation$/', '', $month_abbrev_);
        }

        // The Meridiems
        $this->meridiem['am'] = _( 'am' );
        $this->meridiem['pm'] = _( 'pm' );
        $this->meridiem['AM'] = _( 'AM' );
        $this->meridiem['PM'] = _( 'PM' );

        // Numbers formatting
        // See http://php.net/number_format

        /* //translators: $thousands_sep argument for http://php.net/number_format, default is , */
        $trans = _( 'number_format_thousands_sep' );
        $this->number_format['thousands_sep'] = ('number_format_thousands_sep' == $trans) ? ',' : $trans;

        /* //translators: $dec_point argument for http://php.net/number_format, default is . */
        $trans = _( 'number_format_decimal_point' );
        $this->number_format['decimal_point'] = ('number_format_decimal_point' == $trans) ? '.' : $trans;

        // Set text direction.
        if ( isset( $GLOBALS['text_direction'] ) )
            $this->text_direction = $GLOBALS['text_direction'];
        /* //translators: 'rtl' or 'ltr'. This sets the text direction for YOURLS. */
        elseif ( 'rtl' == x( 'ltr', 'text direction' ) )
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
    public function get_weekday( $weekday_number ) {
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
    public function get_weekday_initial( $weekday_name ) {
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
    public function get_weekday_abbrev( $weekday_name ) {
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
    public function get_month( $month_number ) {
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
    public function get_month_abbrev( $month_name ) {
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
    public function get_meridiem( $meridiem ) {
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
    public function register_globals() {
        $GLOBALS['weekday']         = $this->weekday;
        $GLOBALS['weekday_initial'] = $this->weekday_initial;
        $GLOBALS['weekday_abbrev']  = $this->weekday_abbrev;
        $GLOBALS['month']           = $this->month;
        $GLOBALS['month_abbrev']    = $this->month_abbrev;
    }

    /**
     * Constructor which calls helper methods to set up object variables
     *
     * @uses Locale_Formats::init()
     * @uses Locale_Formats::register_globals()
     * @since 1.6
     *
     * @return Locale_Formats
     */
    public function __construct() {
        $this->init();
        $this->register_globals();
    }

    /**
     * Checks if current locale is RTL.
     *
     * @since 1.6
     * @return bool Whether locale is RTL.
     */
    public function is_rtl() {
        return 'rtl' == $this->text_direction;
    }
}

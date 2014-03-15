<?php

/**
 * Localization Wrapper
 *
 * @since 2.0
 * @version 2.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Internationalization;

use POMO\MO;
use POMO\Translations\NOOPTranslations;

/**
 * YOURLS Translation API
 *
 * YOURLS modification of a small subset from WordPress' Translation API implementation.
 * GPL License
 */
class Localization {

    /**
     * Gets the current locale.
     *
     * If the locale is set, then it will filter the locale in the 'get_locale' filter
     * hook and return the value.
     *
     * If the locale is not set already, then the YOURLS_LANG constant is used if it is
     * defined. Then it is filtered through the 'get_locale' filter hook and the value
     * for the locale global set and the locale is returned.
     *
     * The process to get the locale should only be done once, but the locale will
     * always be filtered using the 'get_locale' hook.
     *
     * @since 1.6
     * @uses Filters::apply_filters() Calls 'get_locale' hook on locale value.
     * @uses $locale Gets the locale stored in the global.
     *
     * @return string The locale of the blog or from the 'get_locale' hook.
     */
    public function get_locale() {
        global $locale;

        if ( !isset( $locale ) ) {
            // YOURLS_LANG is defined in config.
            if ( defined( 'YOURLS_LANG' ) )
                $locale = YOURLS_LANG;
        }

        if ( !$locale )
            $locale = '';

        return Filters::apply_filters( 'get_locale', $locale );
    }

    /**
     * Retrieves the translation of $text. If there is no translation, or
     * the domain isn't loaded, the original text is returned.
     *
     * @see _() Don't use translate() directly, use _()
     * @since 1.6
     * @uses Filters::apply_filters() Calls 'translate' on domain translated text
     *		with the untranslated text as second parameter.
     *
     * @param string $text Text to translate.
     * @param string $domain Domain to retrieve the translated text.
     * @return string Translated text
     */
    public function translate( $text, $domain = 'default' ) {
        $translations = $this->get_translations_for_domain( $domain );

        return Filters::apply_filters( 'translate', $translations->translate( $text ), $text, $domain );
    }

    /**
     * Retrieves the translation of $text with a given $context. If there is no translation, or
     * the domain isn't loaded, the original text is returned.
     *
     * Quite a few times, there will be collisions with similar translatable text
     * found in more than two places but with different translated context.
     *
     * By including the context in the pot file translators can translate the two
     * strings differently.
     *
     * @since 1.6
     * @param string $text Text to translate.
     * @param string $context Context.
     * @param string $domain Domain to retrieve the translated text.
     * @return string Translated text
     */
    public function translate_with_context( $text, $context, $domain = 'default' ) {
        $translations = $this->get_translations_for_domain( $domain );

        return Filters::apply_filters( 'translate_with_context', $translations->translate( $text, $context ), $text, $context, $domain );
    }

    /**
     * Retrieves the translation of $text. If there is no translation, or
     * the domain isn't loaded, the original text is returned.
     *
     * @see translate() An alias of translate()
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $domain Optional. Domain to retrieve the translated text
     * @return string Translated text
     */
    public function _( $text, $domain = 'default' ) {
        return $this->translate( $text, $domain );
    }

    /**
     * Return a translated sprintf() string (mix _() and sprintf() in one func)
     *
     * Instead of doing sprintf( _( 'string %s' ), $arg ) you can simply use:
     * s( 'string %s', $arg )
     * This function accepts an arbitrary number of arguments:
     * - first one will be the string to translate, eg "hello %s my name is %s"
     * - following ones will be the sprintf arguments, eg "world" and "Ozh"
     * - if there are more arguments passed than needed, the last one will be used as the translation domain
     * This function will not accept a textdomain argument: do not use in plugins or outside YOURLS core.
     *
     * @see sprintf()
     * @since 1.6
     *
     * @param string $pattern Text to translate
     * @param string $arg1, $arg2... Optional: sprintf tokens, and translation domain
     * @return string Translated text
     */
    public function s( $pattern ) {
        // Get pattern and pattern arguments
        $args = func_get_args();
        // If s() called by se(), all arguments are wrapped in the same array key
        if( count( $args ) == 1 && is_array( $args[0] ) ) {
            $args = $args[0];
        }
        $pattern = $args[0];

        // get list of sprintf tokens (%s and such)
        $num_of_tokens = substr_count( $pattern, '%' ) - 2 * substr_count( $pattern, '%%' );

        $domain = 'default';
        // More arguments passed than needed for the sprintf? The last one will be the domain
        if( $num_of_tokens < ( count( $args ) - 1 ) ) {
            $domain = array_pop( $args );
        }

        // Translate text
        $args[0] = _( $pattern, $domain );

        return call_user_func_array( 'sprintf', $args );
    }

    /**
     * Echo a translated sprintf() string (mix _() and sprintf() in one func)
     *
     * Instead of doing printf( _( 'string %s' ), $arg ) you can simply use:
     * se( 'string %s', $arg )
     * This function accepts an arbitrary number of arguments:
     * - first one will be the string to translate, eg "hello %s my name is %s"
     * - following ones will be the sprintf arguments, eg "world" and "Ozh"
     * - if there are more arguments passed than needed, the last one will be used as the translation domain
     *
     * @see s()
     * @see sprintf()
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $arg1, $arg2... Optional: sprintf tokens, and translation domain
     * @return string Translated text
     */
    public function se( $pattern ) {
        echo $this->s( func_get_args() );
    }


    /**
     * Retrieves the translation of $text and escapes it for safe use in an attribute.
     * If there is no translation, or the domain isn't loaded, the original text is returned.
     *
     * @see translate() An alias of translate()
     * @see esc_attr()
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $domain Optional. Domain to retrieve the translated text
     * @return string Translated text
     */
    public function esc_attr__( $text, $domain = 'default' ) {
        return esc_attr( $this->translate( $text, $domain ) );
    }

    /**
     * Retrieves the translation of $text and escapes it for safe use in HTML output.
     * If there is no translation, or the domain isn't loaded, the original text is returned.
     *
     * @see translate() An alias of translate()
     * @see esc_html()
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $domain Optional. Domain to retrieve the translated text
     * @return string Translated text
     */
    public function esc_html__( $text, $domain = 'default' ) {
        return esc_html( $this->translate( $text, $domain ) );
    }

    /**
     * Displays the returned translated text from translate().
     *
     * @see translate() Echoes returned translate() string
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $domain Optional. Domain to retrieve the translated text
     */
    public function e( $text, $domain = 'default' ) {
        echo $this->translate( $text, $domain );
    }

    /**
     * Displays translated text that has been escaped for safe use in an attribute.
     *
     * @see translate() Echoes returned translate() string
     * @see esc_attr()
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $domain Optional. Domain to retrieve the translated text
     */
    public function esc_attr_e( $text, $domain = 'default' ) {
        echo esc_attr( $this->translate( $text, $domain ) );
    }

    /**
     * Displays translated text that has been escaped for safe use in HTML output.
     *
     * @see translate() Echoes returned translate() string
     * @see esc_html()
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $domain Optional. Domain to retrieve the translated text
     */
    public function esc_html_e( $text, $domain = 'default' ) {
        echo esc_html( $this->translate( $text, $domain ) );
    }

    /**
     * Retrieve translated string with gettext context
     *
     * Quite a few times, there will be collisions with similar translatable text
     * found in more than two places but with different translated context.
     *
     * By including the context in the pot file translators can translate the two
     * strings differently.
     *
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $context Context information for the translators
     * @param string $domain Optional. Domain to retrieve the translated text
     * @return string Translated context string without pipe
     */
    public function x( $text, $context, $domain = 'default' ) {
        return $this->translate_with_context( $text, $context, $domain );
    }

    /**
     * Displays translated string with gettext context
     *
     * @see x()
     * @since 1.6
     *
     * @param string $text Text to translate
     * @param string $context Context information for the translators
     * @param string $domain Optional. Domain to retrieve the translated text
     * @return string Translated context string without pipe
     */
    public function ex( $text, $context, $domain = 'default' ) {
        echo $this->x( $text, $context, $domain );
    }


    /**
     * Return translated text, with context, that has been escaped for safe use in an attribute
     *
     * @see translate() Return returned translate() string
     * @see esc_attr()
     * @see x()
     * @since 1.6
     *
     * @param string   $single
     * @param string   $context
     * @param string   $domain Optional. Domain to retrieve the translated text
     * @internal param string $text Text to translate
     * @return string
     */
    public function esc_attr_x( $single, $context, $domain = 'default' ) {
        return esc_attr( $this->translate_with_context( $single, $context, $domain ) );
    }

    /**
     * Return translated text, with context, that has been escaped for safe use in HTML output
     *
     * @see translate() Return returned translate() string
     * @see esc_attr()
     * @see x()
     * @since 1.6
     *
     * @param string   $single
     * @param string   $context
     * @param string   $domain Optional. Domain to retrieve the translated text
     * @internal param string $text Text to translate
     * @return string
     */
    public function esc_html_x( $single, $context, $domain = 'default' ) {
        return esc_html( $this->translate_with_context( $single, $context, $domain ) );
    }

    /**
     * Retrieve the plural or single form based on the amount.
     *
     * If the domain is not set in the $l10n list, then a comparison will be made
     * and either $plural or $single parameters returned.
     *
     * If the domain does exist, then the parameters $single, $plural, and $number
     * will first be passed to the domain's ngettext method. Then it will be passed
     * to the 'translate_n' filter hook along with the same parameters. The expected
     * type will be a string.
     *
     * @since 1.6
     * @uses $l10n Gets list of domain translated string (gettext_reader) objects
     * @uses Filters::apply_filters() Calls 'translate_n' hook on domains text returned,
     *		along with $single, $plural, and $number parameters. Expected to return string.
     *
     * @param string $single The text that will be used if $number is 1
     * @param string $plural The text that will be used if $number is not 1
     * @param int $number The number to compare against to use either $single or $plural
     * @param string $domain Optional. The domain identifier the text should be retrieved in
     * @return string Either $single or $plural translated text
     */
    public function n( $single, $plural, $number, $domain = 'default' ) {
        $translations = $this->get_translations_for_domain( $domain );
        $translation = $translations->translate_plural( $single, $plural, $number );

        return Filters::apply_filters( 'translate_n', $translation, $single, $plural, $number, $domain );
    }

    /**
     * A hybrid of n() and x(). It supports contexts and plurals.
     *
     * @since 1.6
     * @see n()
     * @see x()
     *
     */
    public function nx($single, $plural, $number, $context, $domain = 'default') {
        $translations = $this->get_translations_for_domain( $domain );
        $translation = $translations->translate_plural( $single, $plural, $number, $context );

        return Filters::apply_filters( 'translate_nx', $translation, $single, $plural, $number, $context, $domain );
    }

    /**
     * Register plural strings in POT file, but don't translate them.
     *
     * Used when you want to keep structures with translatable plural strings and
     * use them later.
     *
     * Example:
     *  $messages = array(
     *  	'post' => n_noop('%s post', '%s posts'),
     *  	'page' => n_noop('%s pages', '%s pages')
     *  );
     *  ...
     *  $message = $messages[$type];
     *  $usable_text = sprintf( translate_nooped_plural( $message, $count ), $count );
     *
     * @since 1.6
     * @param string $singular Single form to be i18ned
     * @param string $plural Plural form to be i18ned
     * @param string $domain Optional. The domain identifier the text will be retrieved in
     * @return array array($singular, $plural)
     */
    public function n_noop( $singular, $plural, $domain = null ) {
        return array(
            0 => $singular,
            1 => $plural,
            'singular' => $singular,
            'plural' => $plural,
            'context' => null,
            'domain' => $domain
        );
    }

    /**
     * Register plural strings with context in POT file, but don't translate them.
     *
     * @since 1.6
     * @see n_noop()
     */
    public function nx_noop( $singular, $plural, $context, $domain = null ) {
        return array(
            0 => $singular,
            1 => $plural,
            2 => $context,
            'singular' => $singular,
            'plural' => $plural,
            'context' => $context,
            'domain' => $domain
        );
    }

    /**
     * Translate the result of n_noop() or nx_noop()
     *
     * @since 1.6
     * @param array $nooped_plural Array with singular, plural and context keys, usually the result of n_noop() or nx_noop()
     * @param int $count Number of objects
     * @param string $domain Optional. The domain identifier the text should be retrieved in. If $nooped_plural contains
     * 	a domain passed to n_noop() or nx_noop(), it will override this value.
     * @return string
     */
    public function translate_nooped_plural( $nooped_plural, $count, $domain = 'default' ) {
        if ( $nooped_plural['domain'] )
            $domain = $nooped_plural['domain'];

        if ( $nooped_plural['context'] )
            return $this->nx( $nooped_plural['singular'], $nooped_plural['plural'], $count, $nooped_plural['context'], $domain );
        else
            return $this->n( $nooped_plural['singular'], $nooped_plural['plural'], $count, $domain );
    }

    /**
     * Loads a MO file into the domain $domain.
     *
     * If the domain already exists, the translations will be merged. If both
     * sets have the same string, the translation from the original value will be taken.
     *
     * On success, the .mo file will be placed in the $l10n global by $domain
     * and will be a MO object.
     *
     * @since 1.6
     * @uses $l10n Gets list of domain translated string objects
     *
     * @param string $domain Unique identifier for retrieving translated strings
     * @param string $mofile Path to the .mo file
     * @return bool True on success, false on failure
     */
    public function load_textdomain( $domain, $mofile ) {
        global $l10n;

        $plugin_override = Filters::apply_filters( 'override_load_textdomain', false, $domain, $mofile );

        if ( true == $plugin_override ) {
            return true;
        }

        Filters::do_action( 'load_textdomain', $domain, $mofile );

        $mofile = Filters::apply_filters( 'load_textdomain_mofile', $mofile, $domain );

        if ( !is_readable( $mofile ) ) {
            trigger_error( 'Cannot read file ' . str_replace( YOURLS_ABSPATH.'/', '', $mofile ) . '.'
                        . ' Make sure there is a language file installed. More info: http://yourls.org/translations' );

            return false;
        }

        $mo = new MO();
        if ( !$mo->import_from_file( $mofile ) )
            return false;

        if ( isset( $l10n[$domain] ) )
            $mo->merge_with( $l10n[$domain] );

        $l10n[$domain] = &$mo;

        return true;
    }

    /**
     * Unloads translations for a domain
     *
     * @since 1.6
     * @param string $domain Textdomain to be unloaded
     * @return bool Whether textdomain was unloaded
     */
    public function unload_textdomain( $domain ) {
        global $l10n;

        $plugin_override = Filters::apply_filters( 'override_unload_textdomain', false, $domain );

        if ( $plugin_override )
            return true;

        Filters::do_action( 'unload_textdomain', $domain );

        if ( isset( $l10n[$domain] ) ) {
            unset( $l10n[$domain] );

            return true;
        }

        return false;
    }

    /**
     * Loads default translated strings based on locale.
     *
     * Loads the .mo file in YOURLS_LANG_DIR constant path from YOURLS root. The
     * translated (.mo) file is named based on the locale.
     *
     * @since 1.6
     * @return bool True on success, false on failure
     */
    public function load_default_textdomain() {
        $locale = $this->get_locale();

        if( !empty( $locale ) )

            return $this->load_textdomain( 'default', YOURLS_LANG_DIR . "/$locale.mo" );
    }

    /**
     * Returns the Translations instance for a domain. If there isn't one,
     * returns empty Translations instance.
     *
     * @param string $domain
     * @return object A Translation instance
     */
    public function get_translations_for_domain( $domain ) {
        global $l10n;
        if ( !isset( $l10n[$domain] ) ) {
            $l10n[$domain] = new NOOPTranslations;
        }

        return $l10n[$domain];
    }

    /**
     * Whether there are translations for the domain
     *
     * @since 1.6
     * @param string $domain
     * @return bool Whether there are translations
     */
    public function is_textdomain_loaded( $domain ) {
        global $l10n;

        return isset( $l10n[$domain] );
    }

    /**
     * Translates role name. Unused.
     *
     * Unused function for the moment, we'll see when there are roles.
     * From the WP source: Since the role names are in the database and
     * not in the source there are dummy gettext calls to get them into the POT
     * file and this function properly translates them back.
     *
     * @since 1.6
     */
    public function translate_user_role( $name ) {
        return $this->translate_with_context( $name, 'User role' );
    }

    /**
     * Get all available languages (*.mo files) in a given directory. The default directory is YOURLS_LANG_DIR.
     *
     * @since 1.6
     *
     * @param string $dir A directory in which to search for language files. The default directory is YOURLS_LANG_DIR.
     * @return array Array of language codes or an empty array if no languages are present. Language codes are formed by stripping the .mo extension from the language file names.
     */
    public function get_available_languages( $dir = null ) {
        $languages = array();

        $dir = is_null( $dir) ? YOURLS_LANG_DIR : $dir;

        foreach( (array) glob( $dir . '/*.mo' ) as $lang_file ) {
            $languages[] = basename( $lang_file, '.mo' );
        }

        return Filters::apply_filters( 'get_available_languages', $languages );
    }

    /**
     * Return integer number to format based on the locale.
     *
     * @since 1.6
     *
     * @param int $number The number to convert based on locale.
     * @param int $decimals Precision of the number of decimal places.
     * @return string Converted number in string format.
     */
    public function number_format_i18n( $number, $decimals = 0 ) {
        global $locale_formats;
        if( !isset( $locale_formats ) )
            $locale_formats = new Locale_Formats();

        $formatted = number_format( $number, abs( intval( $decimals ) ), $locale_formats->number_format['decimal_point'], $locale_formats->number_format['thousands_sep'] );

        return Filters::apply_filters( 'number_format_i18n', $formatted );
    }

    /**
     * Return the date in localized format, based on timestamp.
     *
     * If the locale specifies the locale month and weekday, then the locale will
     * take over the format for the date. If it isn't, then the date format string
     * will be used instead.
     *
     * @since 1.6
     *
     * @param string   $dateformatstring Format to display the date.
     * @param bool|int $unixtimestamp    Optional. Unix timestamp.
     * @param bool     $gmt              Optional, default is false. Whether to convert to GMT for time.
     * @return string The date, translated if locale specifies it.
     */
    public function date_i18n( $dateformatstring, $unixtimestamp = false, $gmt = false ) {
        global $locale_formats;
        if( !isset( $locale_formats ) )
            $locale_formats = new Locale_Formats();

        $i = $unixtimestamp;

        if ( false === $i ) {
            if ( ! $gmt )
                $i = $this->current_time( 'timestamp' );
            else
                $i = time();
            // we should not let date() interfere with our
            // specially computed timestamp
            $gmt = true;
        }

        // store original value for language with untypical grammars
        // see http://core.trac.wordpress.org/ticket/9396
        $req_format = $dateformatstring;

        $datefunc = $gmt? 'gmdate' : 'date';

        if ( ( !empty( $locale_formats->month ) ) && ( !empty( $locale_formats->weekday ) ) ) {
            $datemonth            = $locale_formats->get_month( $datefunc( 'm', $i ) );
            $datemonth_abbrev     = $locale_formats->get_month_abbrev( $datemonth );
            $dateweekday          = $locale_formats->get_weekday( $datefunc( 'w', $i ) );
            $dateweekday_abbrev   = $locale_formats->get_weekday_abbrev( $dateweekday );
            $datemeridiem         = $locale_formats->get_meridiem( $datefunc( 'a', $i ) );
            $datemeridiem_capital = $locale_formats->get_meridiem( $datefunc( 'A', $i ) );

            $dateformatstring = ' '.$dateformatstring;
            $dateformatstring = preg_replace( "/([^\\\])D/", "\\1" . backslashit( $dateweekday_abbrev ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])F/", "\\1" . backslashit( $datemonth ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])l/", "\\1" . backslashit( $dateweekday ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])M/", "\\1" . backslashit( $datemonth_abbrev ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])a/", "\\1" . backslashit( $datemeridiem ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])A/", "\\1" . backslashit( $datemeridiem_capital ), $dateformatstring );

            $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
        }
        $timezone_formats = array( 'P', 'I', 'O', 'T', 'Z', 'e' );
        $timezone_formats_re = implode( '|', $timezone_formats );
        if ( preg_match( "/$timezone_formats_re/", $dateformatstring ) ) {

            // TODO: implement a timezone option
            $timezone_string = get_option( 'timezone_string' );
            if ( $timezone_string ) {
                $timezone_object = timezone_open( $timezone_string );
                $date_object = date_create( null, $timezone_object );
                foreach( $timezone_formats as $timezone_format ) {
                    if ( false !== strpos( $dateformatstring, $timezone_format ) ) {
                        $formatted = date_format( $date_object, $timezone_format );
                        $dateformatstring = ' '.$dateformatstring;
                        $dateformatstring = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $dateformatstring );
                        $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
                    }
                }
            }
        }
        $j = @$datefunc( $dateformatstring, $i );
        // allow plugins to redo this entirely for languages with untypical grammars
        $j = Filters::apply_filters('date_i18n', $j, $req_format, $i, $gmt);

        return $j;
    }

    /**
     * Retrieve the current time based on specified type. Stolen from WP.
     *
     * The 'mysql' type will return the time in the format for MySQL DATETIME field.
     * The 'timestamp' type will return the current timestamp.
     *
     * If $gmt is set to either '1' or 'true', then both types will use GMT time.
     * if $gmt is false, the output is adjusted with the GMT offset in the WordPress option.
     *
     * @since 1.6
     *
     * @param string $type Either 'mysql' or 'timestamp'.
     * @param int|bool $gmt Optional. Whether to use GMT timezone. Default is false.
     * @return int|string String if $type is 'gmt', int if $type is 'timestamp'.
     */
    public function current_time( $type, $gmt = 0 ) {
        switch ( $type ) {
            case 'mysql':
                return ( $gmt ) ? gmdate( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s', time() + HOURS_OFFSET * 3600 );
                break;
            case 'timestamp':
                return ( $gmt ) ? time() : time() + HOURS_OFFSET * 3600;
                break;
        }
    }


    /**
     * Loads a custom translation file (for a plugin, a theme, a public interface...)
     *
     * The .mo file should be named based on the domain with a dash, and then the locale exactly,
     * eg 'myplugin-pt_BR.mo'
     *
     * @since 1.6
     *
     * @param string $domain Unique identifier (the "domain") for retrieving translated strings
     * @param string $path Full path to directory containing MO files.
     * @return bool True on success, false on failure
     */
    public function load_custom_textdomain( $domain, $path ) {
        $locale = Filters::apply_filters( 'load_custom_textdomain', $this->get_locale(), $domain );
        $mofile = trim( $path, '/' ) . '/'. $domain . '-' . $locale . '.mo';

        return $this->load_textdomain( $domain, $mofile );
    }

    /**
     * Checks if current locale is RTL. Stolen from WP.
     *
     * @since 1.6
     * @return bool Whether locale is RTL.
     */
    public function is_rtl() {
        global $locale_formats;
        if( !isset( $locale_formats ) )
            $locale_formats = new Locale_Formats();

        return $locale_formats->is_rtl();
    }

    /**
     * Return translated weekday abbreviation (3 letters, eg 'Fri' for 'Friday')
     *
     * The $weekday var can be a textual string ('Friday'), a integer (0 to 6) or an empty string
     * If $weekday is an empty string, the function returns an array of all translated weekday abbrev
     *
     * @since 1.6
     * @param mixed $weekday A full textual weekday, eg "Friday", or an integer (0 = Sunday, 1 = Monday, .. 6 = Saturday)
     * @return mixed Translated weekday abbreviation, eg "Ven" (abbrev of "Vendredi") for "Friday" or 5, or array of all weekday abbrev
     */
    public function l10n_weekday_abbrev( $weekday = '' ){
        global $locale_formats;
        if( !isset( $locale_formats ) )
            $locale_formats = new Locale_Formats();

        if( $weekday === '' )

            return $locale_formats->weekday_abbrev;

        if( is_int( $weekday ) ) {
            $day = $locale_formats->weekday[ $weekday ];

            return $locale_formats->weekday_abbrev[ $day ];
        } else {
            return $locale_formats->weekday_abbrev[ _( $weekday ) ];
        }
    }

    /**
     * Return translated weekday initial (1 letter, eg 'F' for 'Friday')
     *
     * The $weekday var can be a textual string ('Friday'), a integer (0 to 6) or an empty string
     * If $weekday is an empty string, the function returns an array of all translated weekday initials
     *
     * @since 1.6
     * @param mixed $weekday A full textual weekday, eg "Friday", an integer (0 = Sunday, 1 = Monday, .. 6 = Saturday) or empty string
     * @return mixed Translated weekday initial, eg "V" (initial of "Vendredi") for "Friday" or 5, or array of all weekday initials
     */
    public function l10n_weekday_initial( $weekday = '' ){
        global $locale_formats;
        if( !isset( $locale_formats ) )
            $locale_formats = new Locale_Formats();

        if( $weekday === '' )

            return $locale_formats->weekday_initial;

        if( is_int( $weekday ) ) {
            $weekday = $locale_formats->weekday[ $weekday ];

            return $locale_formats->weekday_initial[ $weekday ];
        } else {
            return $locale_formats->weekday_initial[ _( $weekday ) ];
        }
    }

    /**
     * Return translated month abbrevation (3 letters, eg 'Nov' for 'November')
     *
     * The $month var can be a textual string ('November'), a integer (1 to 12), a two digits strings ('01' to '12), or an empty string
     * If $month is an empty string, the function returns an array of all translated abbrev months ('January' => 'Jan', ...)
     *
     * @since 1.6
     * @param mixed $month Empty string, a full textual weekday, eg "November", or an integer (1 = January, .., 12 = December)
     * @return mixed Translated month abbrev (eg "Nov"), or array of all translated abbrev months
     */
    public function l10n_month_abbrev( $month = '' ){
        global $locale_formats;
        if( !isset( $locale_formats ) )
            $locale_formats = new Locale_Formats();

        if( $month === '' )

            return $locale_formats->month_abbrev;

        if( intval( $month ) > 0 ) {
            $month = $locale_formats->month[ $month ];

            return $locale_formats->month_abbrev[ $month ];
        } else {
            return $locale_formats->month_abbrev[ _( $month ) ];
        }
    }

    /**
     * Return array of all translated months
     *
     * @since 1.6
     * @return array Array of all translated months
     */
    public function l10n_months(){
        global $locale_formats;
        if( !isset( $locale_formats ) )
            $locale_formats = new Locale_Formats();

        return $locale_formats->month;
    }
}

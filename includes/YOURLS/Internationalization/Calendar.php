<?php

/**
 * Calendar Engine
 *
 * @since 1.6
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Internationalization;

/**
 * Class that loads the calendar locale.
 *
 * @since 1.6
 * @todo https://github.com/briannesbitt/Carbon/issues/32
 */
class Calendar extends Carbon\Carbon {

    /**
     * Translator helper.
     * @var Localization
     */
    private $l10n;

    public function __construct() {
        $this->l10n = new Localization();
    }

    /**
     * Names of days of the week, localized.
     */
    public function init_days() {
        self::$days = array(
            self::SUNDAY    => $this->l10n->_( 'Sunday' ),
            self::MONDAY    => $this->l10n->_( 'Monday' ),
            self::TUESDAY   => $this->l10n->_( 'Tuesday' ),
            self::WEDNESDAY => $this->l10n->_( 'Wednesday' ),
            self::THURSDAY  => $this->l10n->_( 'Thursday' ),
            self::FRIDAY    => $this->l10n->_( 'Friday' ),
            self::SATURDAY  => $this->l10n->_( 'Saturday' )
        );
    }
}

<?php

/**
 * Statistics Wrapper
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */
namespace YOURLS\Statistics;

/**
 * Base of statistics module
 *
 * This interface is the requirement to add a module at the statistics page.
 *
 */
interface Statistics {

    public $name;
    public $description;

    public function fetch_data();
    public function __toString();

}

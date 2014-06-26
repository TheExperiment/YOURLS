<?php

/**
 * HTTP Client
 *
 * @since 2.0
 * @version 2.0.0-alpha
 * @copyright 2009-2014 YOURLS
 * @license MIT
 */

namespace YOURLS\Network;

/**
 * YOURLS Client
 *
 * Typically a user which make a request to YOURLS.
 * We fetch some basic information about this user
 * to add data in logs or follow YOURLS activity.
 *
 * @since 2.0
 */
class Client {

    /**
     * IP address
     * @var string
     */
    private $ip;

    /**
     * User Agent
     * @var string
     */
    private $ua;

    /**
     * URI requested
     * @var \URL
     */
    private $request;

    /**
     * Construct a new YOURLS Client
     *
     * The client is set up by his IP, UA, and URI
     *
     * @since 2.0
     */
    public function __construct() {
        $this->ip = new ClientData\IP();
        $this->ua = new ClientData\UserAgent();
        $this->request = new ClientData\Request();
    }

    /**
     * Return an info about the client
     * @return string Client information
     */
    public function __get( $name ){
        return $this->$name;
    }

}

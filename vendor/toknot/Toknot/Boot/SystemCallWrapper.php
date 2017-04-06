<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 * @since 4.0
 * @filesource
 * @package Toknot.Boot
 */

namespace Toknot\Boot;

/**
 * SystemCallWrapper
 *
 * @author chopin
 */
interface SystemCallWrapper {

    /**
     * invoke request method
     */
    public function call();

    /**
     * wrapper contruct
     */
    public function init();

    /**
     * get wrapper instance
     */
    public static function getInstance();

    /**
     * register the wrapper
     */
    public static function register();
}

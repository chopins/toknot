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

namespace Toknot\Includes;

/**
 * SystemCallWrapper
 *
 * @author chopin
 */
interface SystemCallWrapper {

    const __class = __CLASS__;

    /**
     * invoke request method
     */
    public function call();

    /**
     * wrapper contruct
     */
    public function init($path = '');

    /**
     * get wrapper instance
     */
    public static function getInstance($kernel);

    public function response($runResult);

    public function returnResponse($runResult);

    public function getArg($key);
}

<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Exception;

/**
 *  UndefinedPropertyException
 *
 * @author chopin
 */
class UndefinedPropertyException extends BaseException {

    public function __construct($obj, $name) {
        $class = get_class($obj);
        $message = "undefined property $class::\${$name}";
        parent::__construct($message);
    }

}

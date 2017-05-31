<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

/**
 * MethodHelper
 *
 */
class MethodHelper {

    private $prevObj = null;

    public function __construct($obj = null) {
        $this->prevObj = $obj;
    }

    public function __call($name, $arg = []) {
        if ($this->prevObj) {
            return array($this->prevObj, $name);
        }
        return $name;
    }

    public static function __callStatic($name, $arg = []) {
        return $name;
    }

    public function __get($name) {
        return $name;
    }

}

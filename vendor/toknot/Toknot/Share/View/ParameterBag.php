<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share\View;

use Toknot\Boot\Object;

/**
 * ParamterBags
 *
 */
class ParameterBag extends Object {

    public function __get($name) {
        $this->iteratorArray[$name];
    }

    public function __set($name, $value) {
        $this->iteratorArray[$name] = $value;
    }

    public function __isset($name) {
        return array_key_exists($name, $this->iteratorArray);
    }

    public function offsetGet($offset) {
        return self::coalesce($this->iteratorArray, $offset);
    }
}

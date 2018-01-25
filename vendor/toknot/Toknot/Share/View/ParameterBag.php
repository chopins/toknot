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
use Toknot\Boot\Tookit;

/**
 * ParamterBags
 *
 */
class ParameterBag extends Object {

    public function __get($name) {
        return $this->get($name);
    }

    public function __set($name, $value) {
        $this->set($name, $value);
    }

    public function __isset($name) {
        return array_key_exists($name, $this->iteratorArray);
    }

    public function offsetSet($offset, $value) {
        $this->set($offset, $value);
    }

    public function offsetGet($offset) {
        return $this->get($offset);
    }

    public function set($name, $value) {
        if (is_array($value)) {
            $p = new ParameterBag();
            foreach ($value as $k => $v) {
                $p->set($k, $v);
            }
            $this->iteratorArray[$name] = $p;
        } else {
            $this->iteratorArray[$name] = $value;
        }
    }

    public function get($name) {
        return Tookit::coalesce($this->iteratorArray, $name);
    }

}

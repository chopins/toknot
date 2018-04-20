<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Includes\Object;

class SymbolHelper {

    private $obj = null;

    public function __construct($obj) {
        $this->obj = $obj;
    }

    public function __get($name) {
        $ref = new \Toknot\Boot\ReflectionMethod($this->obj, $name);
        if ($ref->isStatic()) {
            return [$ref->class, $name];
        } elseif (is_string($this->obj) && !$ref->isStatic()) {
            $obj = $this->obj :: single();
            return [$obj, $name];
        }
        return [$this->obj, $name];
    }

}

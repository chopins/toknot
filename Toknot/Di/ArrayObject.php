<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\DataObject;
use \ArrayAccess;
use \Serializable;


class ArrayObject extends DataObject implements ArrayAccess, Serializable {

    public function length() {
        return $this->count();
    }

    public function first() {
        list($f) = $this->interatorArray;
        return $f;
    }

    public function slice($offset, $length = NULL, $preserve_keys = false) {
        $array = array_slice($this->interatorArray, $offset, $length, $preserve_keys);
        $return = array();
        foreach ($array as $value) {
            if ($this->$value instanceof $this->propertieClassName) {
                $return[$value] = $this->$value->covertToArray();
            } else {
                $return[$value] = $this->$value;
            }
        }
        return $return;
    }

    public function keys() {
        return $this->interatorArray;
    }

    public function offsetExists($offset) {
        return isset($this->interatorArray[$offset]);
    }

    public function offsetGet($offset) {
        return $this->interatorArray[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->setPropertie($offset, $value);
    }

    public function offsetUnset($offset) {
        unset($this->interatorArray[$offset]);
    }

    public function covertToArray() {
        $return = array();
        foreach ($this->interatorArray as $value) {
            if ($this->$value instanceof $this->propertieClassName) {
                $return[$value] = $this->$value->covertToArray();
            } else {
                $return[$value] = $this->$value;
            }
        }
        return $return;
    }

    public function serialize() {
        $array = $this->covertToArray();
        return serialize($array);
    }

    public function unserialize($serialized) {
        $array = unserialize($serialized);
        $this->importPropertie($array);
    }

}
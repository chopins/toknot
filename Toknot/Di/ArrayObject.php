<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\Object;
use \ArrayAccess;
use \Serializable;
use Toknot\Di\StringObject;
use \RuntimeException;
use \InvalidArgumentException;

class ArrayObject extends Object implements ArrayAccess, Serializable {

    public function __construct(array $value = array()) {
        $this->importPropertie($value);
    }

    public function importPropertie($array) {
        foreach ($array as $key => $value) {
            $this->setPropertie($key, $value);
        }
    }

    public function replace_recursive(ArrayObject $arrayObj) {
        $args = func_get_args();
        foreach ($args as $key => $arrObj) {
            if (!$arrObj instanceof ArrayObject) {
                throw new InvalidArgumentException("Passed parameter of $key is not ArrayObject");
            }
        }
        foreach ($args as $arrObj) {
            foreach ($arrObj as $key => $var) {
                if (isset($this->interatorArray[$key]) 
                        && $var instanceof ArrayObject 
                        && $this->interatorArray[$key] instanceof ArrayObject) {
                        $this->interatorArray[$key]->replace_recursive($var);
                } else {
                    $this->interatorArray[$key] = $var;
                }
            }
        }
    }

    public function setPropertie($propertie, $value) {
        if (is_string($value)) {
            $this->interatorArray[$propertie] = new StringObject($value);
        } elseif (is_array($value)) {
            $this->interatorArray[$propertie] = new ArrayObject($value);
        } else {
            $this->interatorArray[$propertie] = $value;
        }
    }

    public function __get($propertie) {
        if (isset($this->interatorArray[$propertie])) {
            return $this->interatorArray[$propertie];
        } else {
            throw new RuntimeException("propertie $propertie undefined of ArrayObject");
        }
    }

    public function __isset($name) {
        $set = isset($this->interatorArray[$name]);
        if (!$set) {
            return isset($this->$name);
        }
        return $set;
    }

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

    public function transformToArray() {
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

    public function rewind() {
        reset($this->interatorArray);
    }

}
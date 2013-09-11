<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Di;

use Toknot\Di\Object;
use \ArrayAccess;
use \Serializable;
use \RuntimeException;
use \InvalidArgumentException;

class ArrayObject extends Object implements ArrayAccess, Serializable {

    /**
     * transfrom array type to ArrayObject type of object
     * 
     * @param array $value Option
     */
    public function __construct(array $value = array()) {
        $this->importPropertie($value);
    }

    /**
     * import propertie form a array, array key is propertie name
     * 
     * @param array $array
     */
    public function importPropertie($array) {
        foreach ($array as $key => $value) {
            $this->setPropertie($key, $value);
        }
    }

    /**
     * merge a ArrayObject or more ArrayObject recursively, the method will merge
     * child ArrayObject, if have same key, the later value will overwrite the original
     * value, no matter whether is same numeric key, it not like PHP function
     * {@see array_merge_recursive}, but if the value is array or ArrayObject ,the
     * value be merge recursively
     * 
     * @param \Toknot\Di\ArrayObject $arrayObj
     * @throws InvalidArgumentException
     */
    public function replace_recursive(ArrayObject $arrayObj) {
        $args = func_get_args();
        foreach ($args as $key => $arrObj) {
            if (!$arrObj instanceof ArrayObject) {
                throw new InvalidArgumentException("Passed parameter of $key is not ArrayObject");
            }
        }
        foreach ($args as $arrObj) {
            foreach ($arrObj as $key => $var) {
                if (isset($this->interatorArray[$key])) {
                    if ($var instanceof ArrayObject &&
                            $this->interatorArray[$key] instanceof ArrayObject) {

                        $this->interatorArray[$key]->replace_recursive($var);
                        continue;
                    } elseif (is_array($var) &&
                            $this->interatorArray[$key] instanceof ArrayObject) {
                        $this->interatorArray[$key]->replace_recursive(new ArrayObject($var));
                        continue;
                    }
                }
                $this->interatorArray[$key] = $var;
            }
        }
    }

    public function setPropertie($propertie, $value) {
        if (is_array($value)) {
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
            if ($this->$value instanceof ArrayObject) {
                $return[$value] = $this->$value->transformToArray();
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
        foreach ($this->interatorArray as $key => $value) {
            if ($this->$key instanceof ArrayObject) {
                $return[$key] = $value->transformToArray();
            } else {
                $return[$key] = $value;
            }
        }
        return $return;
    }

    public function serialize() {
        $array = $this->transformToArray();
        return serialize($array);
    }

    public function unserialize($serialized) {
        $array = unserialize($serialized);
        $this->importPropertie($array);
    }

    public function rewind() {
        reset($this->interatorArray);
        $this->resetCount();
    }
}

function _l(array $array) {
    return new ArrayObject($array);
}
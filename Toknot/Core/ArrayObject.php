<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Core;

use Toknot\Core\Object;
use \ArrayAccess;
use \Serializable;
use \InvalidArgumentException;

class ArrayObject extends Object implements ArrayAccess, Serializable {

    /**
     * transfrom array type to ArrayObject type of object
     * 
     * @param array $value Option
     */
    public function __init(array $value = array()) {
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
        $className = get_called_class();
        foreach ($args as $key => $arrObj) {
            if (!$arrObj instanceof $className) {
                throw new InvalidArgumentException("Passed parameter of $key is not ArrayObject");
            }
        }
        foreach ($args as $arrObj) {
            foreach ($arrObj as $key => $var) {
                if (isset($this->interatorArray[$key])) {
                    if ($var instanceof $className &&
                            $this->interatorArray[$key] instanceof $className) {

                        $this->interatorArray[$key]->replace_recursive($var);
                        continue;
                    } elseif (is_array($var) &&
                            $this->interatorArray[$key] instanceof $className) {
                        $this->interatorArray[$key]->replace_recursive(new $className($var));
                        continue;
                    }
                }
                $this->interatorArray[$key] = $var;
                $this->$key = $var;
            }
        }
    }

    public function setPropertie($propertie, $value) {
        $className = get_called_class();
        if (is_array($value)) {
            $this->interatorArray[$propertie] = new $className($value);
            $this->$propertie = new $className($value);
        } else {
            $this->interatorArray[$propertie] = $value;
            $this->$propertie = $value;
        }
    }

    public function getPropertie($propertie) {
        if (isset($this->interatorArray[$propertie])) {
            return $this->interatorArray[$propertie];
        }
        parent::getPropertie($propertie);
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
        $className = get_called_class();
        foreach ($array as $value) {
            if ($this->$value instanceof $className) {
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
        $className = get_called_class();
        foreach ($this->interatorArray as $key => $value) {
            if ($this->$key instanceof $className) {
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

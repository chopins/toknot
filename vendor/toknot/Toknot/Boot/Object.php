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

namespace Toknot\Boot;

abstract class Object implements \Countable, \Iterator, \ArrayAccess {

    use ObjectHelper;

    private static $singletonInstanceStorage = [];
    private $iteratorKey = null;
    protected $iteratorArray = [];
    private $objHash = '';
    private static $lastObjHash = [];

    /**
     * when construct param same anywhere return same instance of the class
     * 
     * @final
     * @return $this
     * @access public
     * @static
     * @param mix $_    The class construct params is option and any number
     */
    final public static function single() {
        $argv = func_get_args();
        $data = func_num_args() > 0 ? self::paramsHash($argv) : '';
        $className = get_called_class();
        $hash = md5($data . $className);

        //if no param return last obj
        if (self::_has($className) && empty($data)) {
            return self::$singletonInstanceStorage[$className][self::$lastObjHash[$className]];
        }

        if (self::_has($className) && self::_hasHash($className, $hash)) {
            return self::$singletonInstanceStorage[$className][$hash];
        }

        if (empty(self::$singletonInstanceStorage[$className])) {
            self::$singletonInstanceStorage[$className] = [];
        }

        $argc = count($argv);
        $attach = ['data' => $data];

        if ($argc > 0) {
            $attach = self::constructArgs($className, $argv);
        } else {
            $attach = new $className;
        }

        $attach->objHash = $hash;
        self::$lastObjHash[$className] = $hash;
        self::$singletonInstanceStorage[$className][$hash] = $attach;
        return $attach;
    }

    final public function getObjHash() {
        return $this->objHash;
    }

    final static public function paramsHash($param) {
        array_walk_recursive($param, function(&$item, $i) {
            if (is_object($item)) {
                $item = spl_object_hash($item);
            } elseif (is_resource($item)) {
                ob_start();
                var_dump($item);
                $item = ob_get_clean();
            }
        });
        return sha1(serialize($param));
    }

    final public static function setObjHash($class, $id) {
        self::$lastObjHash[$class] = $id;
    }

    final private static function _hasHash($className, $hash) {
        return isset(self::$singletonInstanceStorage[$className][$hash]);
    }

    final private static function _has($className) {
        return isset(self::$singletonInstanceStorage[$className]);
    }

    final public function setIteratorArray(array $data = []) {
        $this->iteratorArray = $data;
    }

    final public function getIteratorArray() {
        return $this->iteratorArray;
    }

    public function count() {
        return count($this->iteratorArray);
    }

    public function current() {
        return current($this->iteratorArray);
    }

    public function next() {
        next($this->iteratorArray);
    }

    public function key() {
        $this->iteratorKey = key($this->iteratorArray);
        return $this->iteratorKey;
    }

    public function valid() {
        $this->iteratorKey = $this->key();
        return array_key_exists($this->iteratorKey, $this->iteratorArray);
    }

    public function rewind() {
        reset($this->iteratorArray);
    }

    public function offsetExists($offset) {
        return array_key_exists($offset, $this->iteratorArray);
    }

    public function offsetGet($offset) {
        return $this->iteratorArray[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->iteratorArray[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->iteratorArray[$offset]);
    }

    public static function __set_state($properties) {
        $obj = new static();
        $obj->iteratorArray = $properties;
        return $obj;
    }

    public function __clone() {
        if (is_object($this->iteratorArray)) {
            $this->iteratorArray = clone $this->iteratorArray;
        }
    }

    public function __toString() {
        return get_called_class() . '(#' . spl_object_hash($this) . ')';
    }

}

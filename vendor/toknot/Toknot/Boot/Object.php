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

abstract class Object implements \Countable, \Iterator, \ArrayAccess, \Serializable {

    use ObjectHelper;

    private static $singletonInstanceStorage = [];
    private $iteratorKey = null;
    protected $iteratorArray = [];

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

        $data = func_num_args() > 0 ? md5(serialize($argv)) : '';
        $className = get_called_class();

        if (self::_has($className) && self::_argvSame($data, $className)) {
            return self::$singletonInstanceStorage[$className]['obj'];
        }

        $argc = count($argv);
        $attach = ['data' => $data];

        if ($argc > 0) {
            $attach['obj'] = self::constructArgs($argc, $argv, $className);
        } else {
            $attach['obj'] = new $className;
        }
        self::$singletonInstanceStorage[$className] = $attach;

        return $attach['obj'];
    }

    final private static function _has($className) {
        return isset(self::$singletonInstanceStorage[$className]);
    }

    final private static function _argvSame($data, $className) {
        return empty($data) || $data == self::$singletonInstanceStorage[$className]['data'];
    }

    /**
     * use factory dynamic create instance when the class is any name and any params
     *
     * @param int $argc
     * @param array $args
     * @param string $className
     * @static
     * @access public
     * @final
     * @return $this
     */
    final public static function constructArgs($argc, array $args, $className) {
        switch ($argc) {
            case 0:
                return new $className;
            case 1:
                return new $className($args[0]);
            case 2:
                return new $className($args[0], $args[1]);
            case 3:
                return new $className($args[0], $args[1], $args[2]);
            case 4:
                return new $className($args[0], $args[1], $args[2], $args[3]);
            case 5:
                return new $className($args[0], $args[1], $args[2], $args[3], $args[4]);
            default:
                $ref = new \ReflectionClass($className);
                return $ref->newInstanceArgs($args);
        }
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

    public function serialize() {
        return serialize($this->iteratorArray);
    }

    public function unserialize($serialized) {
        $this->iteratorArray = unserialize($serialized);
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

}

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
                $argStr = self::argStr($argc);
                $ins = null;
                eval("\$ins = new {$className}($argStr);");
                return $ins;
        }
    }

    /**
     * 
     * @param callable $callable
     * @param array $argv
     * @return mixed
     */
    final public static function callFunc($callable, $argv = []) {
        $argc = count($argv);
        if (is_array($callable)) {
            return self::callMethod($argc, $callable[1], $argv, $callable[0]);
        }
        switch ($argc) {
            case 0:
                return $callable();
            case 1:
                return $callable($argv[0]);
            case 2:
                return $callable($argv[0], $argv[1]);
            case 3:
                return $callable($argv[0], $argv[1], $argv[2]);
            case 4:
                return $callable($argv[0], $argv[1], $argv[2], $argv[3]);
            case 5:
                return $callable($argv[0], $argv[1], $argv[2], $argv[3], $argv[4]);
            default:
                $argStr = self::argStr($argc);
                $ins = null;
                eval("\$ins = $callable($argStr);");
                return $ins;
        }
    }

    /**
     * dynamic call a static method of a class and pass any params
     * 
     * @param int $argc
     * @param string $method
     * @param array $argv
     * @param string $className
     * @return mix
     */
    final public static function invokeStatic($argc, $method, $argv, $className) {
        switch ($argc) {
            case 0:
                return $className::$method();
            case 1:
                return $className::$method($argv[0]);
            case 2:
                return $className::$method($argv[0], $argv[1]);
            case 3:
                return $className::$method($argv[0], $argv[1], $argv[2]);
            case 4:
                return $className::$method($argv[0], $argv[1], $argv[2], $argv[3]);
            case 5:
                return $className::$method($argv[0], $argv[1], $argv[2], $argv[3], $argv[4]);
            default:
                $argStr = self::argStr($argc);
                $ins = null;
                eval("\$ins = {$className}::$method($argStr);");
                return $ins;
        }
    }

    /**
     * dynamic call a method of a class use any params
     * 
     * @param int $argc
     * @param string $method
     * @param array $argv
     * @return mix
     */
    final public function invokeMethod($argc, $method, $argv) {
        return self::callMethod($argc, $method, $argv, $this);
    }

    final public static function callMethod($argc, $method, $argv, $obj) {
        switch ($argc) {
            case 0:
                return $obj->$method();
            case 1:
                return $obj->$method($argv[0]);
            case 2:
                return $obj->$method($argv[0], $argv[1]);
            case 3:
                return $obj->$method($argv[0], $argv[1], $argv[2]);
            case 4:
                return $obj->$method($argv[0], $argv[1], $argv[2], $argv[3]);
            case 5:
                return $obj->$method($argv[0], $argv[1], $argv[2], $argv[3], $argv[4]);
            default:
                $argStr = self::argStr($argc);
                $ins = null;
                eval("\$ins = \$obj->$method($argStr);");
                return $ins;
        }
    }

    final public static function argStr($argc) {
        return trim(vsprintf(str_repeat('$argv[%d],', $argc), range(0, $argc - 1)), ',');
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

    final public function __isReadonlyProperty($name) {
        $ref = new \ReflectionObject($this);
        $doc = $ref->getProperty($name)->getDocComment();
        if (preg_match('/^[\s]*\*[\s]*@readonly[\s]*$/m', $doc)) {
            return true;
        }
        return false;
    }

}

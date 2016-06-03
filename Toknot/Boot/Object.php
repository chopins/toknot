<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2015 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use \ReflectionObject;
use \ReflectionMethod;
use \ReflectionProperty;
use \Iterator;
use \Countable;
use \SplObjectStorage;
use \BadMethodCallException;
use \Toknot\Exception\BadPropertyGetException;
use \Toknot\Exception\BadClassCallException;

abstract class Object implements Iterator, Countable {

    /**
     * For iterator of propertie list
     *
     * @var array
     * @access protected
     */
    protected $interatorArray = [];

    /**
     * whether object instance propertie change status
     *
     * @var bool
     * @access private
     */
    private $propertieChange = false;

    /**
     * Object instance of the child class when singleton mode
     *
     * @var object
     * @access private
     */
    private static $singletonInstanceStorage = [];
    private static $thisInstance = [];
    private $counter = 0;
    private $countNumber = 0;
    private $extendsClass = null;
    private static $currentCallClass = 'Object';

    /**
     *  late extends method of parent class
     *  <code>
     *   class A {
     *      public am() {}
     *   }
     *   class B extends Object {}
     *   $aobj = new A;
     *   $bobj = new B($aobj);
     *   $bobj->am();
     *
     *  </code>
     *
     */
    final public function __construct(...$argv) {
        $this->currentCallClass = get_called_class();
        self::$thisInstance[self::$currentCallClass] = $this;
        $this->extendsClass = new SplObjectStorage();
        if (count($argv) > 0) {
            foreach ($argv as $param) {
                if (is_object($param)) {
                    $this->extendsClass->attach($arg);
                }
            }
        }
        $this->invokeMethod('__init', $argv);
    }

    /**
     * __call be invoked when self class is not public function and outer call
     * so fist call parent class of method, if self class call not exists method
     */
    final public function __call(string $name, array $arguments = []) {
        if ($this->extendsClass->count()) {
            foreach ($this->extendsClass as $obj) {
                if (method_exists($obj, $name)) {
                    return $obj->invokeMethod($name, $arguments);
                }
            }
        }
        return $this->__callMethod($name, $arguments);
    }

    protected function __init() {

    }

    protected function __callMethod(string $name, array $arguments = []) {
        throw new BadMethodCallException("Call undefined Method $name in object {self::$currentCallClass}");
    }

    final public function __get($name) {
        try {
            return $this->getPropertie($name);
        } catch (BadPropertyGetException $e) {
            if ($this->extendsClass->count()) {
                foreach ($this->extendsClass as $obj) {
                    if (property_exists($obj, $name)) {
                        return $obj->$name;
                    }
                }
            }
            throw $e;
        }
    }

    public function getPropertie($name) {
        throw new BadPropertyGetException(self::$currentCallClass, $name);
    }

    /**
     * provide singleton pattern for Object child class
     *
     * @param mixed $_ options,  instance of  for construct parameters
     * @static
     * @access public
     * @final
     * @return object
     */
    final protected static function &__singleton(...$argv) {
        $className = get_called_class();
        if (isset(self::$singletonInstanceStorage[$className]) && is_object(self::$singletonInstanceStorage[$className]) && self::$singletonInstanceStorage[$className] instanceof $className) {
            return self::$singletonInstanceStorage[$className];
        }
        $argc = count($argv);
        if ($argc > 0) {
            self::$singletonInstanceStorage[$className] = self::constructArgs($argc, $argv, $className);
        } else {
            self::$singletonInstanceStorage[$className] = new $className;
        }
        return self::$singletonInstanceStorage[$className];
    }

    public static function singleton(...$argv) {
        return static::invokeStaticMethod('__singleton', $argv);
    }

    /**
     * get singletion instance
     *
     * @static
     * @access public
     * @final
     * @return object|null
     */
    final public static function getInstance() {
        $className = get_called_class();
        if (isset(self::$singletonInstanceStorage[$className])) {
            return self::$singletonInstanceStorage[$className];
        } else {
            return null;
        }
    }

    /**
     * Get recent instance of current class
     *
     * @static
     * @access public
     * @final
     * @return object|null
     */
    final public static function getClassInstance() {
        $className = get_called_class();
        if (isset(self::$thisInstance[$className])) {
            return self::$thisInstance[$className];
        }
        throw new BadClassCallException($className);

    }

    /**
     * Creates a new class instance without invoking the constructor.
     *
     * @return object
     */
    public static function newInstanceWithoutConstruct():object {
        $className = get_called_class();
        $ser = sprintf('O:%d:"%s":0:{}', strlen($className), $className);
        return unserialize($ser);
    }

    /**
     * create instance of class with use static method
     *
     * @static
     * @access public
     * @final
     * @return object
     */
    final public static function getNewInstance() {
        return new static;
    }

    /**
     * new instance of class
     *
     * @param int $argc
     * @param array $args
     * @param string $className
     * @static
     * @access public
     * @final
     * @return object
     */
    final public static function constructArgs($argc, array $args, $className) {
        if ($argc === 1) {
            return new $className($args[0]);
        } elseif ($argc === 2) {
            return new $className($args[0], $args[1]);
        } elseif ($argc === 3) {
            return new $className($args[0], $args[1], $args[2]);
        } elseif ($argc === 4) {
            return new $className($args[0], $args[1], $args[2], $args[3]);
        } elseif ($argc === 5) {
            return new $className($args[0], $args[1], $args[2], $args[3], $args[4]);
        } elseif ($argc === 6) {
            return new $className($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        } else {
            $argStr = '';
            foreach ($args as $k => $v) {
                $argStr .= "\$args[$k],";
            }
            $argStr = rtrim($argStr, ',');
            $ins = null;
            eval("\$ins = new {$className}($argStr);");
            return $ins;
        }
    }

    /**
     * call un-static method use static method invoke when the un-static-method name prefixed S char
     *
     * @param string $name
     * @param array $arguments
     * @static
     * @access public
     * @final
     * @return mix
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $name, array $arguments = []) {
        $className = get_called_class();
        if (substr($name, 0, 1) === 'S') {
            $that = self::getInstance();
            $methodName = substr($name, 1);
            if (!method_exists($className, $methodName)) {
                throw new \BadMethodCallException("Call to undefined method $className::$name()");
            }
            return $that->invokeMethod($methodName, $arguments);
        }
        if (!method_exists($className, $name)) {
            throw new \BadMethodCallException("Call to undefined method $className::$name()");
        }
        return self::invokeStaticMethod($name, $arguments);
    }

    /**
     * @param
     *
     */
    final public static function invokeStaticMethod(string $methodName, array $args = []) {
        $argc = count($args);
        if ($argc === 0) {
            return static::$methodName();
        } elseif ($argc === 1) {
            return static::$methodName($args[0]);
        } elseif ($argc === 2) {
            return static::$methodName($args[0], $args[1]);
        } elseif ($argc === 3) {
            return static::$methodName($args[0], $args[1], $args[2]);
        } elseif ($argc === 4) {
            return static::$methodName($args[0], $args[1], $args[2], $args[3]);
        } elseif ($argc === 5) {
            return static::$methodName($args[0], $args[1], $args[2], $args[3], $args[4]);
        } elseif ($argc === 6) {
            return static::$methodName($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        } else {
            $argStr = '';
            foreach ($args as $k => $v) {
                $argStr .= "\$args[$k],";
            }
            $argStr = rtrim($argStr, ',');
            $ret = null;
            eval("\$ret = static::{$methodName}($argStr);");
            return $ret;
        }
    }

    /**
     * invoke method
     *
     * @param string $methodName
     * @param array $args
     * @access public
     * @final
     * @return mix
     */
    final public function invokeMethod(string $methodName, array $args = array()) {
        $argc = count($args);
        if ($argc === 0) {
            return $this->$methodName();
        } elseif ($argc === 1) {
            return $this->$methodName($args[0]);
        } elseif ($argc === 2) {
            return $this->$methodName($args[0], $args[1]);
        } elseif ($argc === 3) {
            return $this->$methodName($args[0], $args[1], $args[2]);
        } elseif ($argc === 4) {
            return $this->$methodName($args[0], $args[1], $args[2], $args[3]);
        } elseif ($argc === 5) {
            return $this->$methodName($args[0], $args[1], $args[2], $args[3], $args[4]);
        } elseif ($argc === 6) {
            return $this->$methodName($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
        } else {
            $argStr = '';
            foreach ($args as $k => $v) {
                $argStr .= "\$args[$k],";
            }
            $argStr = rtrim($argStr, ',');
            $ret = null;
            eval("\$ret = \$this->{$methodName}($argStr);");
            return $ret;
        }
    }

    /**
     * __set
     * set propertie value and save changed status and baned child cover __set method
     *
     * @param mixed $propertie
     * @param mixed $value
     * @final
     * @access public
     * @return void
     */
    final public function __set($propertie, $value) {
        $this->propertieChange = true;
        $this->setPropertie($propertie, $value);
    }

    /**
     *
     * @param string $propertie
     * @param mixed $value
     * @access protected
     * @return void
     */
    protected function setPropertie($propertie, $value) {
        //$this->$propertie = $value;
    }

    /**
     * isChange
     * check class propertie whether change default value or set new propertie and it value;
     *
     * @final
     * @access public
     * @return boolean
     */
    final public function isChange() {
        if ($this->propertieChange)
            return true;
        $ref = new ReflectionObject($this);
        $list = $ref->getDefaultProperties();
        $staticList = $ref->getStaticProperties();
        foreach ($list as $key => $value) {
            if (isset($staticList[$key])) {
                if (self::$$key != $value)
                    return true;
            } else {
                if ($this->$key != $value)
                    return true;
            }
        }
        return false;
    }

    final public function getDocComment($method = null) {
        if (is_null($method)) {
            $ref = new ReflectionObject($this);
            return $ref->getDocComment();
        } else {
            try {
                $mRef = new ReflectionMethod($this, $method);
                return $mRef->getDocComment();
            } catch (ReflectionException $e) {
                try {
                    $pRef = new ReflectionProperty($this, $method);
                    return $pRef->getDocComment();
                } catch (ReflectionException $e) {
                    return false;
                }
            }
        }
    }

    /**
     *
     * @return object
     */
    public function __invoke() {
        return $this;
    }

    /**
     * @return string current called class name
     */
    public function __toString() {
        return get_called_class();
    }

    public function rewind() {
        $ref = new ReflectionObject($this);
        $propertiesList = $ref->getProperties();
        $constantsList = $ref->getConstants();
        $this->interatorArray = array_merge($constantsList, $propertiesList);
        reset($this->interatorArray);
        $this->resetCount();
    }

    protected function resetCount() {
        $this->counter = 0;
        $this->countNumber = count($this->interatorArray);
    }

    public function current() {
        return current($this->interatorArray);
    }

    public function key() {
        return key($this->interatorArray);
    }

    public function next() {
        $this->counter++;
        next($this->interatorArray);
    }

    public function valid() {
        if ($this->countNumber == 0) {
            return false;
        }
        if ($this->countNumber <= $this->counter) {
            return false;
        }
        return true;
    }

    public function count() {
        $this->rewind();
        return $this->countNumber;
    }

}

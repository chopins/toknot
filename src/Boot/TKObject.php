<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 chopin xiao (xiao@toknot.com)
 */

namespace Toknot\Boot;

use Closure;

class TKObject {

    private static $instanceStore = [];
    private static $bindInokeClosure = null;
    private static $bindStaticClosure = null;
    private static $bindInsInoke = [];
    private static $bindInsStatic = [];

    /**
     * 
     * @return $this
     */
    public static function instance() {
        $class = get_called_class();
        $hash = md5($class);
        if (isset(self::$instanceStore[$hash]) && self::$instanceStore[$hash] instanceof $class) {
            return self::$instanceStore[$hash];
        }
        self::$instanceStore[$hash] = new static;
        return self::$instanceStore[$hash];
    }

    public static function argStr($keys) {
        return '$params[' . join('], $params[', $keys) . ']';
    }

    public function invoke($name, array $params = []) {
        if (self::$bindInokeClosure === null) {
            self::$bindClosure = function($name, $params) {
                $argc = count($params);
                switch ($argc) {
                    case 0:
                        return $this->$name();
                    case 1:
                        return $this->$name($params[0]);
                    case 2:
                        return $this->$name($params[0], $params[1]);
                    case 3:
                        return $this->$name($params[0], $params[1], $params[2]);
                    case 4:
                        return $this->$name($params[0], $params[1], $params[2], $params[3]);
                    case 5:
                        return $this->$name($params[0], $params[1], $params[2], $params[4], $params[5]);
                    default:
                        $ret = null;
                        $argStr = self::argStr(array_keys($params));
                        eval("$ret = \$this->{$name}($argStr)");
                        return $ret;
                }
            };
        }
        $class = spl_object_hash($this);
        if(empty(self::$bindInsInoke[$class])) {
            self::$bindInsInoke[$class] = Closure::bind(self::$bindInokeClosure, $this, get_called_class());
        }
        $c = self::$bindInsInoke[$class];
        return $c($name, $params);
    }

    public function invokeMethod($name, ...$params) {
        return $this->invoke($name, $params);
    }

    public static function invokeStaticMethod($name, ...$params) {
        return self::invokeStatic($name, $params);
    }

    public static function invokeStatic($name, array $params = []) {
        if (self::$bindStaticClosure === null) {
            self::$bindStaticClosure = function($name, $params) {
                $argc = count($params);
                switch ($argc) {
                    case 0:
                        return self::$name();
                    case 1:
                        return self::$name($params[0]);
                    case 2:
                        return self::$name($params[0], $params[1]);
                    case 3:
                        return self::$name($params[0], $params[1], $params[2]);
                    case 4:
                        return self::$name($params[0], $params[1], $params[2], $params[3]);
                    case 5:
                        return self::$name($params[0], $params[1], $params[2], $params[4], $params[5]);
                    default:
                        $ret = null;
                        $argStr = self::argStr($argc);
                        eval("$ret = self::{$name}($argStr)");
                        return $ret;
                }
            };
        }
        $class = get_called_class();
        if(empty(self::$bindInsStatic[$class])) {
            self::$bindInsStatic[$class] = Closure::bind(self::$bindStaticClosure, null, $class);
        }
        $c = self::$bindInsStatic;
        return $c($name, $params);
    }

    public function __call($name, $params = []) {
        throw new \BadMethodCallException('Call to undefined method ' . get_called_class() . "::$name()");
    }

    public static function __callStatic($name, $param = []) {
        throw new \BadMethodCallException('Call to undefined method ' . get_called_class() . "::$name()");
    }

}

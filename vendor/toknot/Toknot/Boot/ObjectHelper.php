<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

use Toknot\Exception\BaseException;

/**
 * ObjectHelper
 *
 */
trait ObjectHelper {

    /**
     * 
     * @param callable $callable
     * @param array $argv
     * @return mixed
     */
    public static function callFunc($callable, $argv = []) {
        if (is_array($callable)) {
            return self::callMethod($callable[0], $callable[1], $argv);
        }
        $argc = count($argv);
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
                $argstr = $this->argStr($argc);
                eval("\$res = $callable($argstr);");
                return $res;
        }
    }

    public static function __class() {
        return get_called_class();
    }

    public static function __method() {
        return new MethodHelper();
    }

    public function __callable() {
        return new MethodHelper($this);
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
    public static function invokeStatic($className, $method, $argv = []) {
        $argc = count($argv);
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
                $argstr = $this->argStr($argc);
                eval("\$res = $className::$method($argstr);");
                return $res;
        }
    }

    /**
     * use factory dynamic create instance when the class is any name and any params
     *
     * @param array $className
     * @param string $args
     * @static
     * @access public
     * @final
     * @return $this
     */
    public static function constructArgs($className, array $args = []) {
        $argc = count($args);
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
                $argstr = $this->argStr($argc);
                eval("\$res = new $className($argstr);");
                return $res;
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
    public function invokeMethod($method, $argv = []) {
        return self::callMethod($this, $method, $argv);
    }

    public static function callMethod($obj, $method, $argv = []) {
        $argc = count($argv);
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
                $argstr = $this->argStr($argc);
                eval("\$res = \$obj->$method($argstr);");
                return $res;
        }
    }

    public static function argStr($argc) {
        return trim(vsprintf(str_repeat('$argv[%d],', $argc), range(0, $argc - 1)), ',');
    }

    protected function __isReadonlyProperty($ref, $name) {
        $doc = $ref->getProperty($name)->getDocComment();
        if (preg_match('/^[\s]*\*[\s]*@readonly[\s]*$/m', $doc)) {
            return true;
        }
        return false;
    }

    public function __get($name) {
        $ref = new \ReflectionObject($this);
        $has = $ref->hasProperty($name);
        if ($has && $this->__isReadonlyProperty($ref, $name)) {
            return $this->{$name};
        }

        throw BaseException::undefinedProperty($this, $name);
    }

    public function autoConfigProperty($propertys, $cfg) {
        foreach ($propertys as $pro => $confg) {
            if ($cfg->has($confg)) {
                $value = $cfg->find($confg);
                $this->$pro = $value;
            }
        }
    }

}

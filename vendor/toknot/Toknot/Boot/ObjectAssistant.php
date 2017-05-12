<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Boot;

/**
 * ObjectAssistant
 *
 */
trait ObjectAssistant {

    /**
     * 
     * @param callable $callable
     * @param array $argv
     * @return mixed
     */
    public static function callFunc($callable, $argv = []) {
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
                $argstr = $this->argStr($argc);
                eval("\$res = $callable($argstr);");
                return $res;
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
    public static function invokeStatic($argc, $method, $argv, $className) {
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
     * dynamic call a method of a class use any params
     * 
     * @param int $argc
     * @param string $method
     * @param array $argv
     * @return mix
     */
    public function invokeMethod($argc, $method, $argv) {
        return self::callMethod($argc, $method, $argv, $this);
    }

    public static function callMethod($argc, $method, $argv, $obj) {
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
                eval("\$res = $obj->$method($argstr);");
                return $res;
        }
    }

    public static function argStr($argc) {
        return trim(vsprintf(str_repeat('$argv[%d],', $argc), range(0, $argc - 1)), ',');
    }

    public function __isReadonlyProperty($name) {
        $ref = new \ReflectionObject($this);
        $doc = $ref->getProperty($name)->getDocComment();
        if (preg_match('/^[\s]*\*[\s]*@readonly[\s]*$/m', $doc)) {
            return true;
        }
        return false;
    }

    public function __get($name) {
        if ($this->__isReadonlyProperty($name)) {
            return $this->{$name};
        }
        throw BaseException::undefineProperty($this, $name);
    }

    public function autoConfigProperty($propertys, $cfg) {
        foreach ($propertys as $pro => $confg) {
            $value = $cfg->find($confg);
            if ($value) {
                $this->$pro = $value;
            }
        }
    }

}

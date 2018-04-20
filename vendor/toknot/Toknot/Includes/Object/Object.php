<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2018 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Includes\Object;

class Object {

    private static $singletonInstanceStorage = [];
    
    //Object::S()->method
    public static function S() {
        return new SymbolHelper(get_called_class());
    }

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

    final private static function _hasHash($className, $hash) {
        return isset(self::$singletonInstanceStorage[$className][$hash]);
    }

    final private static function _has($className) {
        return isset(self::$singletonInstanceStorage[$className]);
    }

}

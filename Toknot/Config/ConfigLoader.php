<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2013 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Config;

use Toknot\Di\Object;
use Toknot\Di\ArrayObject;
use Toknot\Di\DataCacheControl;

final class ConfigLoader extends Object {

    private static $_CFG = null;
    public static $cacheFile = '';

    protected function __construct() {
        $file = __DIR__ . '/default.ini';
        self::$_CFG = new ArrayObject;
        self::loadCfg($file);
    }

    public static function singleton() {
        return parent::__singleton();
    }

    public static function CFG() {
        return self::$_CFG;
    }

    /**
     * detach 
     * 
     * @param array $array 
     * @static
     * @access private
     * @return array
     */
    private static function detach(array $array) {
        return new ArrayObject($array);
    }

    /**
     * load configuration file
     * 
     * @access public
     * @return void
     */
    public static function loadCfg($file) {
        if (file_exists($file)) {
            $cacheControl = new DataCacheControl(self::$cacheFile, filemtime($file));
            $cache = $cacheControl->get();
            if ($cache === false) {
                $userConfig = parse_ini_file($file, true);
                $userConfig = self::detach($userConfig);
                self::$_CFG->replace_recursive($userConfig);
                $cacheData = self::$_CFG->transformToArray();
                $cacheControl->save($cacheData);
            } else {
                self::$_CFG = self::detach($cache);
            }
        }
        return self::$_CFG;
    }

}


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
    public static $cacheDir = '';

    protected function __init() {
        $file = __DIR__ . '/default.ini';
        self::$_CFG = new ArrayObject;
        self::importCfg($file);
    }

    /**
     * Get ConfigLoader instance
     * 
     * @return Toknot\Config\ConfigLoader
     */
    public static function singleton() {
        return parent::__singleton();
    }

    /**
     * Get app configure data
     * 
     * @return Toknot\Di\ArrayObject
     */
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
     * import application configuration file
     * 
     * @access public
     * @return void
     */
    public static function importCfg($file) {
        if (file_exists($file)) {
 
            if (self::$cacheDir) {
                $cacheFile = self::$cacheDir . DIRECTORY_SEPARATOR . basename($file) . '.cache';
                $cacheControl = new DataCacheControl($cacheFile, filemtime($file));
                $cache = $cacheControl->get();
            } else {
                $cache = false;
            }
            if ($cache === false) {
                $userConfig = parse_ini_file($file, true);
                $userConfig = self::detach($userConfig);
                self::$_CFG->replace_recursive($userConfig);
                if (self::$cacheDir) {
                    $cacheData = self::$_CFG->transformToArray();
                    $cacheControl->save($cacheData);
                }
            } else {
                self::$_CFG = self::detach($cache);
            }
        }
        return self::$_CFG;
    }

    /**
     * Load  ini file
     * 
     * @param string $file
     * @return array
     */
    public static function loadCfg($file) {
        if (self::$cacheDir) {
            $cacheFile = self::$cacheDir . DIRECTORY_SEPARATOR . basename($file) . '.cache';
            $cacheControl = new DataCacheControl($cacheFile, filemtime($file));
            $cache = $cacheControl->get();
            if ($cache === false) {
                $arr = parse_ini_file($file, true);
                $cacheControl->save($arr);
                return $arr;
            }
            return $cache;
        }
    }

}
